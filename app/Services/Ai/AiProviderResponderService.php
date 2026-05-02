<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AiProviderResponderService
{
    /**
     * Intents in this list never send enriched context to external LLM providers.
     * We keep deterministic tool replies for privacy-sensitive flows.
     *
     * @var array<int, string>
     */
    private const PRIVACY_BLOCKED_INTENTS = [
        'order_tracking',
    ];

    private const DEFAULT_ESTIMATED_COST_PER_ATTEMPT_IDR = 350;

    public function __construct(
        private readonly StoreKnowledgeService $storeKnowledge,
        private readonly CustomerVoiceInsightService $customerVoiceInsight,
        private readonly AiPromptLearningService $promptLearning,
    ) {}

    public function enhanceReply(string $intent, string $message, string $toolReply, array $suggestions = [], array $dataContext = []): ?array
    {
        if (! $this->isExternalAiEnabled()) {
            return null;
        }

        $primaryProvider = $this->normalizeProvider((string) config('services.ai.provider', 'rule_based'));
        if ($primaryProvider === 'rule_based') {
            return null;
        }

        $primaryModel = $this->resolveFastModel($primaryProvider);
        $attempts = [];

        if ($this->shouldSkipExternalForPrivacy($intent, $dataContext)) {
            return [
                'reply' => null,
                'provider' => 'rule_based',
                'model' => 'rule_based',
                'fallback_used' => false,
                'status' => 'privacy_guard_skipped',
                'attempts' => $attempts,
                'budget' => $this->buildBudgetSnapshot(),
            ];
        }

        if (! $this->hasBudgetForAttempt()) {
            return [
                'reply' => null,
                'provider' => $primaryProvider,
                'model' => $primaryModel,
                'fallback_used' => false,
                'status' => 'budget_exhausted',
                'attempts' => $attempts,
                'budget' => $this->buildBudgetSnapshot(),
            ];
        }

        try {
            $systemPrompt = $this->buildSystemPrompt($intent);
            $userPrompt = $this->buildUserPrompt($intent, $message, $toolReply, $suggestions, $dataContext);
        } catch (Throwable $exception) {
            report($exception);

            $attempts[] = $this->buildAttempt($primaryProvider, $primaryModel, false, $exception);

            return [
                'reply' => null,
                'provider' => $primaryProvider,
                'model' => $primaryModel,
                'fallback_used' => false,
                'status' => 'prompt_build_failed',
                'attempts' => $attempts,
                'budget' => $this->buildBudgetSnapshot(),
            ];
        }

        try {
            $reply = $this->requestCompletion($primaryProvider, $primaryModel, $systemPrompt, $userPrompt);
            $this->recordEstimatedCostForAttempt();
            $attempts[] = $this->buildAttempt($primaryProvider, $primaryModel, true);

            return [
                'reply' => $reply,
                'provider' => $primaryProvider,
                'model' => $primaryModel,
                'fallback_used' => false,
                'status' => 'primary_success',
                'attempts' => $attempts,
                'budget' => $this->buildBudgetSnapshot(),
            ];
        } catch (Throwable $exception) {
            report($exception);

            if ($this->shouldCountCostOnFailure($exception)) {
                $this->recordEstimatedCostForAttempt();
            }

            $attempts[] = $this->buildAttempt($primaryProvider, $primaryModel, false, $exception);
        }

        [$fallbackProvider, $fallbackModel] = $this->resolveFallbackTarget($primaryProvider, $primaryModel);

        if ($fallbackProvider === $primaryProvider && $fallbackModel === $primaryModel) {
            return [
                'reply' => null,
                'provider' => $primaryProvider,
                'model' => $primaryModel,
                'fallback_used' => false,
                'status' => 'fallback_unavailable',
                'attempts' => $attempts,
                'budget' => $this->buildBudgetSnapshot(),
            ];
        }

        if (! $this->hasBudgetForAttempt()) {
            return [
                'reply' => null,
                'provider' => $fallbackProvider,
                'model' => $fallbackModel,
                'fallback_used' => false,
                'status' => 'fallback_budget_exhausted',
                'attempts' => $attempts,
                'budget' => $this->buildBudgetSnapshot(),
            ];
        }

        try {
            $reply = $this->requestCompletion($fallbackProvider, $fallbackModel, $systemPrompt, $userPrompt);
            $this->recordEstimatedCostForAttempt();
            $attempts[] = $this->buildAttempt($fallbackProvider, $fallbackModel, true);

            return [
                'reply' => $reply,
                'provider' => $fallbackProvider,
                'model' => $fallbackModel,
                'fallback_used' => true,
                'status' => 'fallback_success',
                'attempts' => $attempts,
                'budget' => $this->buildBudgetSnapshot(),
            ];
        } catch (Throwable $exception) {
            report($exception);

            if ($this->shouldCountCostOnFailure($exception)) {
                $this->recordEstimatedCostForAttempt();
            }

            $attempts[] = $this->buildAttempt($fallbackProvider, $fallbackModel, false, $exception);

            return [
                'reply' => null,
                'provider' => $fallbackProvider,
                'model' => $fallbackModel,
                'fallback_used' => false,
                'status' => 'fallback_failed',
                'attempts' => $attempts,
                'budget' => $this->buildBudgetSnapshot(),
            ];
        }
    }

    private function buildAttempt(string $provider, string $model, bool $isSuccess, ?Throwable $exception = null): array
    {
        return [
            'provider' => $provider,
            'model' => $model,
            'success' => $isSuccess,
            'error' => $isSuccess ? null : $this->compactErrorMessage($exception),
        ];
    }

    private function compactErrorMessage(?Throwable $exception): string
    {
        if (! $exception) {
            return 'provider_request_failed';
        }

        $message = trim($exception->getMessage());
        if ($message === '') {
            $message = class_basename($exception);
        }

        return strlen($message) > 160
            ? substr($message, 0, 160)
            : $message;
    }

    private function isExternalAiEnabled(): bool
    {
        $assistantEnabled = config('services.ai.assistant_enabled', true);

        return filter_var($assistantEnabled, FILTER_VALIDATE_BOOLEAN) !== false;
    }

    private function resolveFastModel(string $provider): string
    {
        $configuredFastModel = trim((string) config('services.ai.model_fast', ''));

        if ($configuredFastModel !== '') {
            return $configuredFastModel;
        }

        return $this->resolveDefaultModelForProvider($provider);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveFallbackTarget(string $primaryProvider, string $primaryModel): array
    {
        $configuredFallbackModel = trim((string) config('services.ai.model_fallback', ''));

        if ($configuredFallbackModel !== '') {
            $detectedFallbackProvider = $this->detectProviderFromModel($configuredFallbackModel);

            return [
                $detectedFallbackProvider ?? $this->resolveOppositeProvider($primaryProvider),
                $configuredFallbackModel,
            ];
        }

        $fallbackProvider = $this->resolveOppositeProvider($primaryProvider);
        $fallbackModel = $this->resolveDefaultModelForProvider($fallbackProvider);

        if ($fallbackModel === $primaryModel && $fallbackProvider === $primaryProvider) {
            return [$primaryProvider, $primaryModel];
        }

        return [$fallbackProvider, $fallbackModel];
    }

    private function resolveOppositeProvider(string $provider): string
    {
        return $provider === 'gemini' ? 'deepseek' : 'gemini';
    }

    private function detectProviderFromModel(string $model): ?string
    {
        $normalizedModel = strtolower(trim($model));

        if ($normalizedModel === '') {
            return null;
        }

        if (str_contains($normalizedModel, 'gemini')) {
            return 'gemini';
        }

        if (str_contains($normalizedModel, 'deepseek')) {
            return 'deepseek';
        }

        return null;
    }

    private function normalizeProvider(string $provider): string
    {
        $normalizedProvider = strtolower(trim($provider));

        return match ($normalizedProvider) {
            'gemini' => 'gemini',
            'deepseek' => 'deepseek',
            default => 'rule_based',
        };
    }

    private function resolveDefaultModelForProvider(string $provider): string
    {
        return $provider === 'gemini'
            ? 'gemini-2.5-flash'
            : 'deepseek-chat';
    }

    private function requestCompletion(string $provider, string $model, string $systemPrompt, string $userPrompt): string
    {
        return match ($provider) {
            'gemini' => $this->requestGeminiCompletion($model, $systemPrompt, $userPrompt),
            'deepseek' => $this->requestDeepSeekCompletion($model, $systemPrompt, $userPrompt),
            default => throw new RuntimeException('Provider AI tidak didukung: ' . $provider),
        };
    }

    private function requestGeminiCompletion(string $model, string $systemPrompt, string $userPrompt): string
    {
        $apiKey = trim((string) config('services.ai.gemini_api_key', ''));

        if ($apiKey === '') {
            throw new RuntimeException('AI_GEMINI_API_KEY belum diisi.');
        }

        $isThinkingModel = $this->isGeminiThinkingModel($model);

        $generationConfig = [
            'temperature' => 0.3,
            'maxOutputTokens' => $this->maxOutputTokens(),
        ];

        // Thinking models (gemini-2.5-*) need a thinkingConfig to control
        // the internal reasoning budget. Without this, the model may allocate
        // too many tokens to thinking and leave too few for the actual reply.
        if ($isThinkingModel) {
            $generationConfig['thinkingConfig'] = [
                'thinkingBudget' => $this->thinkingBudget(),
            ];
        }

        $response = Http::withoutVerifying()
            ->acceptJson()
            ->asJson()
            ->timeout($this->requestTimeout())
            ->withQueryParameters(['key' => $apiKey])
            ->post('https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent', [
                'system_instruction' => [
                    'parts' => [
                        ['text' => $systemPrompt],
                    ],
                ],
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $userPrompt],
                        ],
                    ],
                ],
                'generationConfig' => $generationConfig,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gemini request gagal dengan status HTTP ' . $response->status() . '.');
        }

        $reply = $this->extractGeminiReplyText($response->json(), $isThinkingModel);

        if ($reply === '') {
            throw new RuntimeException('Gemini tidak mengembalikan teks jawaban.');
        }

        return $reply;
    }

    /**
     * Extract the actual reply text from a Gemini API response.
     *
     * Thinking models (gemini-2.5-flash, gemini-2.5-pro, etc.) return multiple
     * parts in the response. The first part(s) contain internal reasoning with
     * "thought": true, and the LAST non-thought part contains the actual answer.
     *
     * Non-thinking models return a single part in parts[0].
     */
    private function extractGeminiReplyText(array $responseJson, bool $isThinkingModel): string
    {
        $parts = data_get($responseJson, 'candidates.0.content.parts', []);

        if (! is_array($parts) || count($parts) === 0) {
            return '';
        }

        // For non-thinking models (or thinkingBudget=0), simply take the
        // first non-empty part's text.
        if (! $isThinkingModel) {
            foreach ($parts as $part) {
                $text = trim((string) ($part['text'] ?? ''));
                if ($text !== '') {
                    return $text;
                }
            }

            return '';
        }

        // For thinking models, iterate from the END to find the last
        // non-thought part. This is the actual response text.
        for ($i = count($parts) - 1; $i >= 0; $i--) {
            $part = $parts[$i];

            // Skip parts that are marked as internal "thought" reasoning.
            if (! empty($part['thought'])) {
                continue;
            }

            $text = trim((string) ($part['text'] ?? ''));
            if ($text !== '') {
                return $text;
            }
        }

        // Fallback #1: last part regardless of thought flag.
        $lastPart = end($parts);
        $lastText = trim((string) ($lastPart['text'] ?? ''));
        if ($lastText !== '') {
            return $lastText;
        }

        // Fallback #2 (token exhaustion edge case): concatenate ALL parts
        // to surface whatever content the model did manage to produce,
        // rather than silently returning an empty string.
        $allText = implode('', array_map(
            static fn(array $p): string => (string) ($p['text'] ?? ''),
            $parts,
        ));

        return trim($allText);
    }

    /**
     * Determine if a Gemini model is a "thinking" model that returns
     * multi-part responses with internal reasoning.
     */
    private function isGeminiThinkingModel(string $model): bool
    {
        $normalizedModel = strtolower(trim($model));

        // Gemini 2.5 series are thinking models by default.
        return str_contains($normalizedModel, 'gemini-2.5')
            || str_contains($normalizedModel, 'gemini-2.5-flash')
            || str_contains($normalizedModel, 'gemini-2.5-pro');
    }

    private function requestDeepSeekCompletion(string $model, string $systemPrompt, string $userPrompt): string
    {
        $apiKey = trim((string) config('services.ai.deepseek_api_key', ''));

        if ($apiKey === '') {
            throw new RuntimeException('AI_DEEPSEEK_API_KEY belum diisi.');
        }

        $response = Http::withoutVerifying()
            ->acceptJson()
            ->asJson()
            ->timeout($this->requestTimeout())
            ->withToken($apiKey)
            ->post('https://api.deepseek.com/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt,
                    ],
                ],
                'temperature' => 0.3,
                'max_tokens' => $this->maxOutputTokens(),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('DeepSeek request gagal dengan status HTTP ' . $response->status() . '.');
        }

        $reply = trim((string) data_get($response->json(), 'choices.0.message.content', ''));

        if ($reply === '') {
            throw new RuntimeException('DeepSeek tidak mengembalikan teks jawaban.');
        }

        return $reply;
    }

    /**
     * Build a COMPREHENSIVE system prompt with full store knowledge.
     */
    private function buildSystemPrompt(string $intent): string
    {
        $storeContext = $this->storeKnowledge->buildKnowledgeContext();
        $websiteNavigationSummary = $this->storeKnowledge->buildWebsiteNavigationSummary();
        $customerVoiceContext = $this->customerVoiceInsight->buildCustomerVoiceContext();
        $adaptivePromptContext = $this->promptLearning->buildPromptAdjustmentContext();
        $catalogSummary = $this->storeKnowledge->buildProductCatalogSummary();

        $personality = implode("\n", [
            '# IDENTITAS KAMU (SANGAT RAHASIA, JANGAN PERNAH SEBUT KAMU AI/BOT)',
            'Mulai sekarang, kamu adalah staf ahli dan Customer Success andalan di Toko HS Electric.',
            'Nama panggilanmu tidak perlu spesifik, anggap saja kamu representatif toko yang sangat pro, ramah, dan asik diajak ngobrol.',
            'Kamu BUKAN chatbot keliling. Kamu adalah manusia ahli kelistrikan yang siap bantu pembeli dengan gaya bahasa kasual namun sangat profesional.',
            '',
            '# GAYA BAHASA & KOMUNIKASI (WAJIB DIIKUTI)',
            '- Sapa pelanggan dengan panggilan akrab dan sopan seperti "Kak".',
            '- Gunakan bahasa Indonesia sehari-hari yang luwes, natural, dan mengalir santai (misal: "Bisa banget kak!", "Wah, kalau untuk ruangan segitu enaknya pakai...", "Nggak perlu khawatir kak...").',
            '- HINDARI bahasa kaku/robotik seperti "Saya adalah asisten AI", "Berdasarkan data yang saya punya", "Berikut rekomendasinya:", atau poin-poin daftaran yang sangat kaku.',
            '- JANGAN PERNAH bilang "saya menemukan di JSON" atau "data mentah menunjukkan". Baca datanya di pikiranmu, lalu sampaikan seolah kamu memang hafal produknya di kepalamu.',
            '- Gunakan emoji secukupnya untuk memberi kesan hangat (😊, 💡, ⚡, 🙏).',
            '- Tulis jawaban dalam format mengalir (paragraph + poin singkat). HINDARI format bullet-point murni yang terasa robotik.',
            '',
            '# STANDAR KECERDASAN & SOLUSI (LEVEL EXPERT)',
            '- Pikir kritis! Kalau user nanya harga lampu tapi budgetnya sempit, proaktif tawarkan yang paling worth-it.',
            '- Kalau user bingung soal teknis (wattage, lumens, cara pasang), jelaskan selayaknya abang-abang jago listrik yang jelasin ke orang awam. Singkat, padat, masuk akal.',
            '- Jangan ngarang data! Harga, spek, ongkir, alamat, kebijakan toko, harus 100% SESUAI dengan data yang diberikan kepadamu.',
            '- Kalau data tidak ada/tidak lengkap, jangan minta maaf berlebihan seperti bot. Bilang saja santai: "Waduh kak, kebetulan untuk detail yang itu lagi kosong nih infonya, coba langsung chat admin di WhatsApp aja ya biar dicek langsung ke gudang: [Link WA]".',
            '- Jika user marah/komplain, jadilah sangat empatik. Posisikan dirimu minta maaf yang tulus dan berikan solusi secepatnya (arahkan ke WA CS).',
            '',
            '# KERANGKA BERPIKIR DIAGNOSTIK (WAJIB DIPAKAI SEBELUM MENJAWAB)',
            'Sebelum menjawab SETIAP pesan, jalankan 5 langkah ini di kepalamu:',
            '1. IDENTIFIKASI — Apa yang sebenarnya ditanyakan user? (sering beda dari apa yang mereka tulis)',
            '2. KONTEKS — Apa yang user sudah tahu/coba? Apakah ini pertanyaan pertama atau lanjutan?',
            '3. AKAR MASALAH — Jika ini masalah: apa root cause paling mungkin? Jangan hanya jawab gejala.',
            '4. SOLUSI — Berikan solusi yang bisa dilakukan user SENDIRI di tempat. WA admin = last resort.',
            '5. PENCEGAHAN — Berikan 1 tips agar user tidak alami masalah serupa di masa depan.',
            '',
            '# KECERDASAN KONTEKSTUAL (BACA MAKSUD TERSEMBUNYI USER)',
            '- "cara menambahkan alamat" → Dia mau input alamat di profil websitenya biar bisa checkout, BUKAN nanya alamat toko kita.',
            '- "apakah diantar?" / "apakah kena ongkir?" → Dia nanya sistem delivery kita. Jawab santai: "Diantar langsung sampai depan pintu rumah kakak kok pakai kurir! Ongkirnya...". (COD juga artinya diantar, nggak usah ambil ke toko).',
            '- "pesanan saya mana?" → Dia lagi tracking. Jelaskan cara lihat menu cek pesanan.',
            '- "kok mahal?" / "bisa kurang?" → Dia NEGOSIASI. Posisikan value: jelaskan kualitas + garansi + gratis ongkir (jika berlaku). Jangan langsung bilang "harga sudah pas".',
            '- Typo / bahasa campuran (Inggris-Indo) → Tetap tangkap maksudnya dan jawab dengan baik. Jangan koreksi typo user.',
            '- Pesan singkat ("ok", "ya", "oke", "terus?") → Lanjutkan topik terakhir, jangan mulai dari awal.',
            '- Multi-pertanyaan ("ongkir berapa? dan bisa COD ga?") → Jawab SEMUA pertanyaan sekaligus, jangan pilih satu saja.',
            '',
            '# KECERDASAN PENJUALAN PROAKTIF',
            '- Kalau user tanya 1 produk, tawarkan juga produk komplementer (misal: lampu + fitting, kabel + terminal).',
            '- Kalau user heboh soal fitur produk, arahkan dengan halus ke checkout: "Langsung aja pesan kak, check out bisa dari halaman produknya ya 😉".',
            '- Kalau user bilang "nanti deh" atau "pikir-pikir dulu", jangan push. Bilang: "Siap kak, santai aja! Kalau nanti mau lanjut, tinggal chat lagi ya. Saya standby kok 😊".',
            '',
            '# KEAMANAN & PRIVASI DATA (WAJIB JAWAB TEGAS)',
            '- Jika pelanggan bertanya tentang KEAMANAN upload bukti transfer, privasi data, atau khawatir nomor rekeningnya tersebar:',
            '  * JAWAB DENGAN TEGAS DAN YAKIN. JANGAN ragu-ragu atau berbelit-belit.',
            '  * Poin kunci: "Data dan bukti pembayaran kakak 100% aman. Bukti transfer hanya diakses oleh admin internal toko untuk keperluan verifikasi pesanan. Tidak akan disebarluaskan atau disalahgunakan ke pihak manapun."',
            '  * Tambahkan: "Sistem kami juga dilindungi dan hanya admin yang bisa melihat bukti yang kakak upload."',
            '  * Jika user MASIH ragu atau tetap tidak mau upload bukti, arahkan dengan HALUS ke alternatif:',
            '    "Tapi kalau kakak tetap lebih nyaman tanpa upload bukti, ada solusi praktis nih kak! Pilih metode Bayar.gg (Otomatis) saat checkout. Kakak tinggal scan QRIS, pembayaran langsung terverifikasi otomatis tanpa perlu upload bukti apapun. Lebih cepat dan praktis! 😊"',
            '  * JANGAN PERNAH bilang "kami tidak bisa menjamin" atau "kami sarankan hati-hati". Ini membuat pelanggan TIDAK PERCAYA.',
            '  * JANGAN menyarankan pelanggan untuk menyensor/blur nomor rekening di bukti transfer. Nomor rekening HARUS terlihat jelas agar admin bisa verifikasi.',
            '',
            '# ATURAN ABSOLUT (PENALTI JIKA DILANGGAR)',
            '1. Jangan pernah mengaku mesin/AI/Bot. Kamu adalah staf toko asli.',
            '2. Link yang kamu berikan harus bisa di-klik dan hanya dari database yang diberikan.',
            '3. Jawab tepat sasaran, jangan berbelit-belit. Kalau dia nanya A, jawab A. Kalau ada info tambahan yang krusial (misal nanya COD, sekalian sebut ongkirnya), boleh ditambahin dengan halus.',
            '4. Jangan PERNAH mengulang pertanyaan user kembali ke mereka ("Oh kakak mau tanya soal..."). Langsung jawab.',
            '5. Jika konteks pesanan, harga, atau stok di luar pengetahuanmu, arahkan WA admin. JANGAN mengarang.',
        ]);

        $sections = [
            $personality,
            '',
            '# DATA TOKO (SUMBER KEBENARAN)',
            $storeContext,
            '',
            '# PETA NAVIGASI WEBSITE (RUTE USER)',
            $websiteNavigationSummary,
            '',
            '# SUARA USER & MASALAH PRODUK TERBARU',
            $customerVoiceContext,
            '',
            '# RULE ADAPTIF BERBASIS FEEDBACK NEGATIF',
            $adaptivePromptContext,
            '',
            $catalogSummary,
        ];

        if ($intent === 'product_recommendation') {
            $sections[] = '';
            $sections[] = '# PANDUAN REKOMENDASI PRODUK (WAJIB DIPATUHI — PENALTI JIKA MELANGGAR)';
            $sections[] = '## ATURAN UTAMA:';
            $sections[] = '- BACA SELURUH [INTERNAL KNOWLEDGE] dengan sangat teliti. Semua data produk (nama, harga, stok, deskripsi) ada di sana.';
            $sections[] = '- Jika data products kosong, match_strategy bernilai none, atau catalog_guard muncul, jawab bahwa produk belum ditemukan di katalog/database toko.';
            $sections[] = '- Untuk produk yang belum ditemukan, JANGAN mengarang nama produk, harga, stok, spesifikasi, atau garansi. Arahkan pelanggan hubungi admin toko untuk konfirmasi.';
            $sections[] = '- JANGAN PERNAH memotong deskripsi produk atau menampilkan "..." di akhir. Berikan informasi SELENGKAP-LENGKAPNYA.';
            $sections[] = '- Jika produk ditemukan di katalog, WAJIB menyebut: nama produk, harga, spesifikasi teknis (watt, lumen, tipe, dll), dan kelebihan/kegunaannya.';
            $sections[] = '';
            $sections[] = '## GARANSI — WAJIB DISEBUTKAN:';
            $sections[] = '- Produk ELEKTRONIK (lampu, MCB, dll) di toko ini dapat memiliki garansi hingga 365 hari setelah pesanan selesai sesuai data/kebijakan toko.';
            $sections[] = '- Garansi aktif otomatis setelah admin menyelesaikan pesanan. Klaim via menu "Garansi" di website.';
            $sections[] = '- Jika user bertanya tentang produk bergaransi dan produknya ditemukan: jelaskan garansi sesuai data toko.';
            $sections[] = '- Jika produk tidak ditemukan, JANGAN klaim garansi. Minta user konfirmasi ke admin toko.';
            $sections[] = '';
            $sections[] = '## ALUR REKOMENDASI YANG SEMPURNA:';
            $sections[] = '1. Identifikasi kebutuhan user (ruangan, anggaran, preferensi cahaya: warm/cool/daylight)';
            $sections[] = '2. Pilih 2-4 produk PALING SESUAI dari katalog, jelaskan MENGAPA produk itu cocok';
            $sections[] = '3. Berikan spek teknis lengkap (watt, lumen jika ada, tipe cahaya, base/fitting yang kompatibel)';
            $sections[] = '4. Sebutkan harga dan STOK yang tersedia';
            $sections[] = '5. Tegaskan garansi: "Tenang kak, semua lampu/produk listrik di toko kita bergaransi sampai 365 hari lho!"';
            $sections[] = '6. Cross-sell produk pendukung (fitting, kabel, dll) secara natural';
            $sections[] = '7. Tutup dengan ajakan action: "Langsung order sekarang yuk kak, stok terbatas!"';
            $sections[] = '';
            $sections[] = '## PENGETAHUAN TEKNIS WAJIB (GUNAKAN UNTUK SARAN YANG CERDAS):';
            $sections[] = '- Kamar tidur standar (3x3m): cukup 5-9 watt LED untuk cahaya nyaman tidur (warm white 3000K)';
            $sections[] = '- Ruang tamu/keluarga (4x4m): ideal 9-12 watt, bisa warm atau cool white sesuai selera';
            $sections[] = '- Dapur/garasi (5x5m ke atas): butuh 12-20 watt, daylight (6500K) untuk visibilitas maksimal';
            $sections[] = '- Warm white (2700-3000K): cocok kamar tidur, hangat dan nyaman';
            $sections[] = '- Cool white (4000K): cocok ruang kerja, netral';
            $sections[] = '- Daylight (6000-6500K): cocok dapur, kamar mandi, garasi — terang maksimal';
            $sections[] = '- 1 watt LED = setara ~8-10 watt lampu pijar lama (hemat listrik!)';
            $sections[] = '- Fitting E27 = ulir besar (standar), E14 = ulir kecil, GU10 = spotlight';
            $sections[] = '- Jika [INTERNAL KNOWLEDGE] menyertakan web_search, gunakan sebagai referensi tambahan dan tampilkan sumber URL secara ringkas.';
            $sections[] = '';
            $sections[] = '## PANDUAN PERTANYAAN HARGA SPESIFIK:';
            $sections[] = '- Jika user bertanya harga produk tertentu (misal: "berapa harga kabel eterna 3x4?"), LANGSUNG jawab harganya dari data produk. Jangan tampilkan banyak produk lain yang tidak diminta.';
            $sections[] = '- Format: "Kabel Eterna Tembaga 3x4 harganya Rp 875.000 per roll kak! Stoknya masih ada X pcs."';
            $sections[] = '- JANGAN menampilkan daftar 5 produk jika user hanya tanya 1 produk spesifik.';
            $sections[] = '';
            $sections[] = '## PANDUAN PERBANDINGAN PRODUK:';
            $sections[] = '- Jika user minta perbandingan (misal: "bedanya kabel 2x1.5 dan 2x2.5 apa?"), buat tabel perbandingan sederhana.';
            $sections[] = '- Bandingkan: nama lengkap, harga, ukuran konduktor, kapasitas arus/daya, kegunaan umum.';
            $sections[] = '- Berikan rekomendasi mana yang lebih cocok berdasarkan kebutuhan user.';
            $sections[] = '- Contoh format perbandingan yang baik:';
            $sections[] = '  "Oke kak, ini perbandingannya ya:';
            $sections[] = '   • Kabel 2x1.5 — cocok untuk penerangan (lampu), daya max ~1300 watt, harga Rp X';
            $sections[] = '   • Kabel 2x2.5 — cocok untuk stop kontak/AC, daya max ~2200 watt, harga Rp Y';
            $sections[] = '   Kesimpulan: kalau buat lampu aja, 2x1.5 sudah cukup dan lebih hemat. Kalau buat AC atau peralatan berat, wajib 2x2.5."';
        }


        if ($intent === 'store_info') {
            $sections[] = '';
            $sections[] = '# PANDUAN INFORMASI TOKO';
            $sections[] = '- Berikan informasi yang diminta secara lengkap dari data toko di atas.';
            $sections[] = '- Jika user bertanya alamat TOKO (bukan alamat pengiriman), sertakan juga link Google Maps jika tersedia.';
            $sections[] = '- Jika user butuh bantuan urgent, sarankan WhatsApp dengan nomor lengkap dan link wa.me.';
            $sections[] = '- PENTING: Bedakan "alamat toko" (lokasi fisik) vs "alamat pengiriman" (fitur profil di website).';
        }

        if ($intent === 'website_help') {
            $sections[] = '';
            $sections[] = '# PANDUAN BANTUAN WEBSITE';
            $sections[] = '- Jelaskan langkah-langkah penggunaan fitur website dengan jelas, terstruktur, dan mudah dipahami orang awam.';
            $sections[] = '- Gunakan penomoran untuk step-by-step guide.';
            $sections[] = '- Jika ada fitur yang berkaitan, sebutkan juga sebagai info tambahan.';
            $sections[] = '- PENTING:';
            $sections[] = '  * Jika user bertanya "cara menambahkan alamat" → jelaskan cara kelola alamat PENGIRIMAN di Profil → Alamat, BUKAN alamat toko.';
            $sections[] = '  * Jika user bertanya "apakah diantar sesuai alamat" → Ya! Pengiriman sesuai alamat yang diisi saat checkout.';
            $sections[] = '  * Jika user bertanya "apakah kena ongkir" → Ya, jelaskan tarif ongkir per item.';
            $sections[] = '  * Jika user bertanya tentang COD → Jelaskan bahwa barang DIANTAR kurir, user tidak perlu ke toko.';
        }

        if ($intent === 'faq') {
            $sections[] = '';
            $sections[] = '# PANDUAN FAQ & PERCAKAPAN KASUAL';
            $sections[] = '- Ini bisa berupa sapaan, pertanyaan umum, curhat, keluhan, atau obrolan santai customer.';
            $sections[] = '- JAWAB SEPERTI MANUSIA, bukan mesin. Santai, akrab, empatik.';
            $sections[] = '- Jika user greeting + tanya produk (misal: "halo, jualan kabel ga?"), jawab ramah: "Iya dong kak, kita jualan kabel! Mau lihat pilihannya?"';
            $sections[] = '- Jika user minta izin chat ("boleh tanya ga?"), jawab welcome: "Boleh banget kak! Mau tanya apa?"';
            $sections[] = '- Jika user komplain / kesel (pesanan lama, dll), JANGAN membela diri. Minta maaf tulus, tunjukkan empati, dan arahkan ke WhatsApp admin.';
            $sections[] = '- Jika user bingung cara pakai website, tawarkan panduan dan tanya mereka bingung soal apa spesifiknya.';
            $sections[] = '- Jika pertanyaan di luar konteks toko (politik, cuaca, dll), tolak sopan: "Wah kak, saya cuma paham soal listrik nih hehe. Kalau soal produk listrik, tanya aja!"';
            $sections[] = '- SELALU gunakan panggilan "Kak". JANGAN PERNAH menyebut diri "AI", "asisten", atau "chatbot".';
        }

        if ($intent === 'troubleshooting') {
            $sections[] = '';
            $sections[] = '# PANDUAN TROUBLESHOOTING & PROBLEM SOLVING (LEVEL AHLI)';
            $sections[] = '- User SEDANG PUNYA MASALAH. Ini BUKAN FAQ biasa. Mereka frustrasi dan butuh SOLUSI KONKRET.';
            $sections[] = '- JANGAN langsung bilang "hubungi WhatsApp". Itu MALAS dan membuat user merasa tidak dibantu.';
            $sections[] = '- Untuk tanda bahaya listrik seperti bau gosong, percikan api, kabel meleleh, MCB sering turun, atau kabel panas berlebihan: WAJIB minta user mematikan MCB/listrik, menghentikan pemakaian, tidak membuka panel/stop kontak/saklar/fitting/sambungan sendiri, dan menghubungi teknisi listrik atau admin toko.';
            $sections[] = '- Jangan memberi instruksi perbaikan instalasi listrik internal. Untuk kasus berbahaya, nada harus tegas, aman, dan eskalatif.';
            $sections[] = '';
            $sections[] = '## ALUR DIAGNOSTIK WAJIB (IKUTI URUTAN INI)';
            $sections[] = '1. EMPATI — Validasi perasaan user: "Waduh, pasti nggak enak ya kak..."';
            $sections[] = '2. DIAGNOSIS — Tanya/analisis root cause. Bukan gejala, tapi AKAR masalahnya.';
            $sections[] = '3. SOLUSI MANDIRI — Berikan 2-4 langkah yang bisa user lakukan SENDIRI di tempat.';
            $sections[] = '4. VERIFIKASI — Arahkan user untuk cek ulang hasilnya: "Coba cek lagi ya kak, harusnya sudah beres."';
            $sections[] = '5. ESCALATION (TERAKHIR) — Barulah jika semua DIY gagal, tawarkan WhatsApp admin.';
            $sections[] = '';
            $sections[] = '## MASALAH PEMBAYARAN';
            $sections[] = '- "Pembayaran ditolak/gagal" → Root cause mungkin: (a) file bukti > 2MB, (b) format bukan JPG/PNG, (c) foto blur/terpotong, (d) nominal tidak cocok. Solusi: kompres foto, screenshot ulang, pastikan nominal terlihat jelas, upload via menu Cek Pesanan → Ganti Bukti.';
            $sections[] = '- "Sudah transfer tapi status belum berubah" → Verifikasi admin manual, biasanya 1-3 jam jam kerja. JANGAN langsung suruh WA. Tanya dulu: sudah berapa lama? Sudah upload bukti? Jika >3 jam DAN sudah upload, baru arahkan WA.';
            $sections[] = '- "Bukti pembayaran ditolak" → Alasan umum: foto blur, nominal tidak sesuai pesanan, rekening pengirim tidak jelas. Solusi step-by-step: (1) buka menu Cek Pesanan, (2) klik pesanan, (3) klik Ganti Bukti, (4) upload screenshot baru yang jelas.';
            $sections[] = '- "Mau ganti metode pembayaran" → Jika status masih pending, bisa ganti. Jelaskan opsi: COD, transfer, e-wallet, Bayar.gg (QRIS otomatis).';
            $sections[] = '- "Bayar.gg/QRIS tidak muncul" → Solusi: (a) refresh halaman, (b) pastikan browser support QRIS, (c) coba browser lain, (d) pilih metode bayar alternatif.';
            $sections[] = '- "Keraguan privasi bukti transfer / takut data tersebar" → (1) Validasi kekhawatiran: "Wajar banget kak kalau kakak concern soal privasi." (2) Tegaskan keamanan: "Data dan bukti transfer kakak 100% aman, hanya admin internal toko yang bisa akses untuk verifikasi pesanan, tidak akan tersebar ke siapapun." (3) Tawarkan alternatif: "Tapi kalau kakak lebih nyaman, bisa pakai Bayar.gg — tinggal scan QRIS, otomatis terverifikasi, nggak perlu upload bukti apapun. Paling gampang dan aman!" JANGAN PERNAH bilang "kami tidak bisa menjamin" — itu membunuh kepercayaan customer.';
            $sections[] = '';
            $sections[] = '## MASALAH PENGIRIMAN';
            $sections[] = '- "Pesanan lama/belum dikirim" → Diagnosis: (1) Cek status pembayaran dulu — belum lunas = belum diproses, itu normal. (2) Jika sudah lunas, estimasi 1-2 hari kerja. (3) Jika >2 hari kerja DAN sudah lunas, baru arahkan WA admin.';
            $sections[] = '- "Salah alamat" → Diagnosis: (1) cek status pesanan. Pending/processing = BISA diubah, segera WA admin. Shipped = tidak bisa diubah, koordinasi langsung dengan kurir via WA admin.';
            $sections[] = '- "Paket hilang / tidak sampai" → Diagnosis: (1) minta user cek resi di menu Cek Pesanan, (2) pastikan resi ada dan valid, (3) cek apakah alamat benar, (4) jika semua benar dan sudah lama, arahkan WA admin dengan kode pesanan.';
            $sections[] = '- "Ongkir kok mahal?" → Jelaskan: ongkir per item, bukan per order. Contohkan perhitungannya. Sarankan beli lebih banyak sekaligus untuk efisiensi.';
            $sections[] = '';
            $sections[] = '## MASALAH PRODUK';
            $sections[] = '- "Barang rusak/cacat" → Diagnosis: (1) apakah masih masa garansi? Cek di menu Garansi. (2) Jika ya: jelaskan cara klaim step-by-step (Garansi → Pilih produk → Isi alasan → Upload foto/video bukti kerusakan → Submit). (3) Jika garansi habis: arahkan WA admin untuk diskusi solusi lain.';
            $sections[] = '- "Barang kurang/salah kirim" → Empati + minta maaf + arahkan WA admin SEGERA dengan kode pesanan. Ini butuh verifikasi gudang.';
            $sections[] = '- "Produk tidak sesuai foto/deskripsi" → Validasi keluhan + arahkan klaim garansi jika eligible, atau WA admin untuk return/exchange.';
            $sections[] = '';
            $sections[] = '## MASALAH AKUN & WEBSITE';
            $sections[] = '- "Tidak bisa login" → Diagnosis systematic: (1) cek capslock off, (2) cek email benar (perhatikan typo), (3) coba Lupa Password → cek email, (4) cek folder spam, (5) jika tetap gagal: WA admin dengan email akun.';
            $sections[] = '- "Halaman error / loading lama" → Solusi: (1) refresh halaman (Ctrl+F5), (2) clear cache browser, (3) coba browser lain, (4) cek koneksi internet, (5) jika masih error: beritahu admin via WA.';
            $sections[] = '- "Checkout gagal / error" → Diagnosis: (1) sudah login? (2) keranjang ada isinya? (3) alamat sudah diisi? (4) metode pembayaran sudah dipilih? Biasanya salah satu dari 4 ini yang belum. Pandu step-by-step.';
            $sections[] = '- "Akun diblokir/disuspend" → Empati + arahkan WA admin dengan email terdaftar untuk klarifikasi.';
            $sections[] = '';
            $sections[] = '## MASALAH CHECKOUT (SERING TERJADI)';
            $sections[] = '- "Tombol checkout tidak bisa diklik" → Kemungkinan: (a) belum login, (b) keranjang kosong, (c) produk stok habis saat checkout perpindahan halaman. Solusi: login dulu, cek keranjang, refresh.';
            $sections[] = '- "Alamat tidak tersimpan" → Kemungkinan: (a) field wajib belum diisi lengkap, (b) kode pos belum diisi. Solusi: isi semua field yang bertanda wajib, pastikan kode pos ada.';
            $sections[] = '- "Total harga berbeda dari yang dilihat" → Jelaskan: harga produk + ongkir per item = total. Ongkir otomatis ditambahkan saat checkout dan dihitung per item.';
            $sections[] = '';
            $sections[] = '## ATURAN TROUBLESHOOTING ABSOLUT';
            $sections[] = '- SELALU berikan minimal 2-4 langkah mandiri yang bisa dilakukan user SENDIRI.';
            $sections[] = '- JANGAN menyalahkan user atau bilang "mungkin kakak salah klik". POSISIKAN DIRI sebagai pembela customer.';
            $sections[] = '- Gunakan nada empatik: "Waduh, pasti nggak nyaman ya kak. Tenang, saya bantu troubleshoot step by step ya..."';
            $sections[] = '- Jika masalah bisa diselesaikan mandiri, JANGAN tawarkan WA. Solusi mandiri > escalation.';
            $sections[] = '- Jika harus escalate ke WA admin, SELALU minta user siapkan kode pesanan (ORD-ARIP-...) agar admin langsung cek.';
        }

        if ($intent === 'emotional_support') {
            $sections[] = '';
            $sections[] = '# PANDUAN EMOTIONAL SUPPORT & CURHAT';
            $sections[] = '- User sedang VENTING, CURHAT, atau mengekspresikan emosi negatif (kecewa, marah, sedih, frustasi).';
            $sections[] = '- Ini BUKAN MASALAH TEKNIS semata. User butuh VALIDASI EMOSI dulu sebelum solusi.';
            $sections[] = '';
            $sections[] = '## FORMAT JAWABAN WAJIB (HARUS BERURUTAN):';
            $sections[] = '1. **VALIDASI dulu** — Akui perasaan user. "Saya paham kak, perasaan kakak sangat wajar..."';
            $sections[] = '2. **EMPATI tulus** — Tunjukkan bahwa kamu benar-benar peduli. Jangan template. Jangan robot.';
            $sections[] = '3. **TANYA LEBIH DALAM** — "Boleh ceritain lebih detail kak? Biar saya bisa bantu cari jalan keluarnya."';
            $sections[] = '4. **TAWARKAN SOLUSI** — Jika ada konteks masalah, berikan langkah konkret.';
            $sections[] = '5. **ESCALATION** — Terakhir, tawarkan WhatsApp admin untuk penanganan personal.';
            $sections[] = '';
            $sections[] = '## ATURAN PENTING:';
            $sections[] = '- JANGAN bilang "saya mengerti" secara hampa. Benar-benar RASAKAN dan REFLEKSIKAN emosi user.';
            $sections[] = '- JANGAN langsung lompat ke solusi. Biarkan user merasa DIDENGAR dulu.';
            $sections[] = '- JANGAN pernah menyalahkan user atau memberikan alasan defensif.';
            $sections[] = '- Gunakan bahasa yang HANGAT: "Waduh...", "Aduh kak...", "Saya ikut ngerasa kok...", "Perasaan kakak sangat wajar..."';
            $sections[] = '- Jika user curhat hal yang BUKAN tentang toko (masalah pribadi), dengarkan sebentar lalu arahkan kembali: "Saya doakan yang terbaik buat kakak ya 🙏 Kalau ada yang bisa dibantu soal belanja, saya selalu di sini!"';
        }

        if ($intent === 'off_topic') {
            $sections[] = '';
            $sections[] = '# PANDUAN OFF-TOPIC (PERTANYAAN DI LUAR KONTEKS TOKO)';
            $sections[] = '- User bertanya tentang hal yang TIDAK berhubungan dengan toko (politik, cuaca, hiburan, dll).';
            $sections[] = '- TOLAK DENGAN SOPAN dan LUCU. Jangan kasar atau meremehkan.';
            $sections[] = '- FORMAT: (1) Acknowledge pertanyaan dengan humor ringan, (2) Jelaskan spesialisasimu (listrik!), (3) Redirect ke topik toko dengan cara menarik.';
            $sections[] = '';
            $sections[] = '## CONTOH BAIK:';
            $sections[] = '- "Wah kak, kalau soal politik saya cuma paham politik listrik: MCB naik-turun hehe 😄 Tapi kalau soal lampu LED...itu baru deh!"';
            $sections[] = '- "Soal cuaca saya ga jago kak, tapi kalau cuaca gelap, saya bisa rekomendasiin lampu yang terangnya mantap! 💡"';
            $sections[] = '';
            $sections[] = '## ATURAN:';
            $sections[] = '- JANGAN pernah menjawab pertanyaan off-topic secara serius (politik, agama sensitif, dll).';
            $sections[] = '- SELALU redirect ke topik toko dengan transisi yang NATURAL dan LUCU.';
            $sections[] = '- Tetap ramah dan bersahabat, jangan terasa menolak.';
        }

        return implode("\n", $sections);
    }

    /**
     * Build the user-facing prompt with the question and tool reply context.
     */
    private function buildUserPrompt(string $intent, string $message, string $toolReply, array $suggestions, array $dataContext): string
    {
        $suggestionText = '-';
        $pageContext = is_array($dataContext['page_context'] ?? null)
            ? $dataContext['page_context']
            : [];
        $conversationHistory = is_array($dataContext['conversation_history'] ?? null)
            ? $dataContext['conversation_history']
            : [];
        $complexCaseProfile = is_array($dataContext['complex_case_profile'] ?? null)
            ? $dataContext['complex_case_profile']
            : [];

        $internalKnowledgeData = $this->sanitizeInternalKnowledge($intent, $dataContext);

        $pageContextText = $this->formatPageContextForPrompt($pageContext);
        $conversationHistoryText = $this->formatConversationHistoryForPrompt($conversationHistory);
        $complexCaseProfileText = $this->formatComplexCaseProfileForPrompt($complexCaseProfile);
        $complexCaseDirectiveText = $complexCaseProfile !== []
            ? 'Jika case_weight pada profil bernilai high/critical, WAJIB gunakan format: empati singkat -> ringkasan masalah utama -> langkah tutorial bernomor -> verifikasi hasil -> tips pencegahan.'
            : '';

        if (count($suggestions) > 0) {
            $normalizedSuggestions = array_map(
                static fn($suggestion): string => trim((string) $suggestion),
                $suggestions,
            );

            $suggestionText = implode('; ', array_filter($normalizedSuggestions, static fn(string $value): bool => $value !== ''));
            if ($suggestionText === '') {
                $suggestionText = '-';
            }
        }

        $rawDataText = count($internalKnowledgeData) > 0
            ? "[INTERNAL KNOWLEDGE]\n" . json_encode($internalKnowledgeData, JSON_PRETTY_PRINT) . "\n[/INTERNAL KNOWLEDGE]"
            : '';

        $compiledPrompt = implode("\n", array_filter([
            '[Pertanyaan Pesan dari Kakak (Pelanggan)]',
            trim($message),
            '',
            '[Konteks Arahan Solusi]',
            trim($toolReply),
            '',
            $pageContextText,
            '',
            $conversationHistoryText,
            '',
            $complexCaseProfileText,
            '',
            $rawDataText,
            '',
            '[Instruksi Balasan — WAJIB DIPATUHI]',
            'Berikan balasan LANGSUNG kepada pelanggan (sebagai manusia, CS HS Electric).',
            'Jawab dengan gaya kasual, pintar, informatif, dan sangat membantu. Tunjukkan bahwa kamu adalah ahli kelistrikan sejati yang menguasai semua produk toko.',
            'JANGAN menahan informasi. Jika user bertanya rekomendasi atau saran produk, berikan penjelasan yang sangat detail, spesifikasi produk, kelebihan, dan alasan mengapa produk itu cocok.',
            'JANGAN PERNAH membuka jawaban dengan mengulang pertanyaan user (misal: "Oh, kakak mau tanya soal..."). LANGSUNG jawab isinya secara komprehensif.',
            $complexCaseDirectiveText,
            '',
            '[Aturan Problem Solving]',
            'Jika user punya MASALAH: diagnosa root cause, berikan langkah solusi mandiri yang sangat detail dan tuntas, baru tawarkan WA jika semua langkah gagal.',
            'Jika user bertanya hal teknis: jelaskan secara detail dengan analogi yang mudah dipahami, berikan wawasan tambahan agar pelanggan merasa teredukasi dan terbantu.',
            'Jika user menyebut produk atau mencari saran: jelaskan spesifikasi produk, tawarkan rekomendasi produk terbaik dari katalog, dan selalu lakukan cross-sell produk komplementer (contoh: lampu dengan fitting/kabel).',
            '',
            '[Aturan Konteks]',
            'Jika konteks halaman tersedia, prioritaskan jawaban yang relevan dengan halaman tersebut agar user langsung dapat langkah yang tepat.',
            'Jika riwayat percakapan tersedia, pahami referensi seperti "yang tadi" atau "itu" berdasarkan konteks sebelumnya, jangan jawab seolah percakapan baru.',
            'Kamu WAJIB membaca [INTERNAL KNOWLEDGE] (terutama data produk) dengan teliti. Ekstrak nama produk, harga, dan speknya, lalu jelaskan dengan bahasamu sendiri (jangan sebut kata "knowledge" atau "json").',
            'Jika ada data web_search di INTERNAL KNOWLEDGE, gunakan sebagai referensi tambahan dan sebutkan sumber URL agar pelanggan bisa cek mandiri.',
            'Jika INTERNAL KNOWLEDGE menunjukkan products kosong, match_strategy none, atau catalog_guard, jawab bahwa produk belum ditemukan di katalog/database toko; jangan mengarang harga, stok, garansi, atau nama produk.',
            '',
            '[Format Output]',
            'Berikan jawaban yang LENGKAP, DETAIL, dan TUNTAS. JANGAN batasi panjang jawabanmu jika memang informasi yang diberikan sangat penting untuk pelanggan. Pastikan tidak ada informasi yang terpotong.',
            'Gunakan paragraf yang rapi dan penomoran/bullet point untuk menjelaskan spesifikasi produk atau langkah panduan agar mudah dibaca.',
            'Saran tindak lanjut yang bisa ditawarkan: ' . $suggestionText,
        ], static fn(string $line): bool => $line !== ''));

        return $this->clampPromptToInputBudget($compiledPrompt);
    }

    private function sanitizeInternalKnowledge(string $intent, array $dataContext): array
    {
        $sanitizedData = $dataContext;

        unset($sanitizedData['page_context'], $sanitizedData['conversation_history']);

        if (is_array($sanitizedData['order'] ?? null)) {
            $orderData = $sanitizedData['order'];
            unset($orderData['latest_payment_url']);
            $sanitizedData['order'] = $orderData;
        }

        if ($this->shouldSkipExternalForPrivacy($intent, $dataContext)) {
            return [
                'privacy_guard' => [
                    'enabled' => true,
                    'intent' => strtolower(trim($intent)),
                    'order_context_present' => is_array($dataContext['order'] ?? null),
                ],
            ];
        }

        return $sanitizedData;
    }

    private function shouldSkipExternalForPrivacy(string $intent, array $dataContext): bool
    {
        $normalizedIntent = strtolower(trim($intent));

        if (in_array($normalizedIntent, self::PRIVACY_BLOCKED_INTENTS, true)) {
            return true;
        }

        if (is_array($dataContext['order'] ?? null)) {
            return true;
        }

        return filled(data_get($dataContext, 'order.latest_payment_url'));
    }

    private function shouldCountCostOnFailure(Throwable $exception): bool
    {
        $message = strtolower(trim($exception->getMessage()));

        if ($message === '') {
            return true;
        }

        return ! Str::contains($message, [
            'belum diisi',
            'tidak didukung',
        ]);
    }

    private function dailyBudgetIdr(): int
    {
        return max(0, (int) config('services.ai.daily_budget_idr', 0));
    }

    private function estimatedCostPerAttemptIdr(): int
    {
        return max(1, (int) config('services.ai.estimated_cost_per_request_idr', self::DEFAULT_ESTIMATED_COST_PER_ATTEMPT_IDR));
    }

    private function hasBudgetForAttempt(): bool
    {
        $dailyBudget = $this->dailyBudgetIdr();

        if ($dailyBudget === 0) {
            return true;
        }

        return ($this->currentDailySpendIdr() + $this->estimatedCostPerAttemptIdr()) <= $dailyBudget;
    }

    private function recordEstimatedCostForAttempt(): void
    {
        $dailyBudget = $this->dailyBudgetIdr();

        if ($dailyBudget === 0) {
            return;
        }

        $dailySpendKey = $this->dailySpendCacheKey();

        Cache::add($dailySpendKey, 0, now()->endOfDay());
        Cache::increment($dailySpendKey, $this->estimatedCostPerAttemptIdr());
    }

    private function currentDailySpendIdr(): int
    {
        return max(0, (int) Cache::get($this->dailySpendCacheKey(), 0));
    }

    private function dailySpendCacheKey(): string
    {
        return 'ai_provider_daily_spend_idr:' . now()->format('Ymd');
    }

    private function buildBudgetSnapshot(): array
    {
        $dailyBudget = $this->dailyBudgetIdr();
        $spentToday = $this->currentDailySpendIdr();

        return [
            'guard_enabled' => $dailyBudget > 0,
            'daily_budget_idr' => $dailyBudget,
            'spent_today_idr' => $spentToday,
            'remaining_idr' => $dailyBudget > 0 ? max(0, $dailyBudget - $spentToday) : null,
            'estimated_per_attempt_idr' => $this->estimatedCostPerAttemptIdr(),
        ];
    }

    private function clampPromptToInputBudget(string $prompt): string
    {
        $maxCharacters = $this->maxInputTokens() * 4;

        if (mb_strlen($prompt) <= $maxCharacters) {
            return $prompt;
        }

        return rtrim(mb_substr($prompt, 0, $maxCharacters)) . "\n\n[Catatan Sistem] Konteks dipangkas otomatis agar sesuai budget input token.";
    }

    private function maxInputTokens(): int
    {
        return max(512, min(32768, (int) config('services.ai.max_input_tokens', 2500)));
    }

    /**
     * @param array<string, mixed> $pageContext
     */
    private function formatPageContextForPrompt(array $pageContext): string
    {
        if ($pageContext === []) {
            return '';
        }

        $lines = ['[Konteks Halaman Website Saat User Chat]'];

        $pageTitle = trim((string) ($pageContext['page_title'] ?? ''));
        if ($pageTitle !== '') {
            $lines[] = '- Judul halaman: ' . $pageTitle;
        }

        $pagePath = trim((string) ($pageContext['page_path'] ?? ''));
        if ($pagePath !== '') {
            $lines[] = '- Path halaman: ' . $pagePath;
        }

        $channel = trim((string) ($pageContext['channel'] ?? ''));
        if ($channel !== '') {
            $lines[] = '- Kanal chat: ' . $channel;
        }

        $productName = trim((string) ($pageContext['product_name'] ?? ''));
        if ($productName !== '') {
            $lines[] = '- Produk yang sedang dilihat: ' . $productName;
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $complexCaseProfile
     */
    private function formatComplexCaseProfileForPrompt(array $complexCaseProfile): string
    {
        if ($complexCaseProfile === []) {
            return '';
        }

        $detectedIssueBuckets = is_array($complexCaseProfile['detected_issue_buckets'] ?? null)
            ? $complexCaseProfile['detected_issue_buckets']
            : [];

        $priorityActions = is_array($complexCaseProfile['priority_actions'] ?? null)
            ? $complexCaseProfile['priority_actions']
            : [];

        $clarifyingQuestions = is_array($complexCaseProfile['clarifying_questions'] ?? null)
            ? $complexCaseProfile['clarifying_questions']
            : [];

        $lines = [
            '[Complex Case Intelligence Profile]',
            '- case_weight: ' . (string) ($complexCaseProfile['case_weight'] ?? 'low'),
            '- complexity_score: ' . (string) ($complexCaseProfile['complexity_score'] ?? 0),
            '- emotion_signal: ' . (string) ($complexCaseProfile['emotion_signal'] ?? 'neutral'),
            '- urgency_signal: ' . (string) ($complexCaseProfile['urgency_signal'] ?? 'low'),
        ];

        if ($detectedIssueBuckets !== []) {
            $lines[] = '- issue_buckets: ' . implode(', ', array_slice($detectedIssueBuckets, 0, 5));
        }

        if ($priorityActions !== []) {
            $lines[] = '- priority_actions: ' . implode(' | ', array_slice($priorityActions, 0, 3));
        }

        if ($clarifyingQuestions !== []) {
            $lines[] = '- clarifying_questions: ' . implode(' | ', array_slice($clarifyingQuestions, 0, 3));
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<int, array<string, mixed>> $conversationHistory
     */
    private function formatConversationHistoryForPrompt(array $conversationHistory): string
    {
        if ($conversationHistory === []) {
            return '';
        }

        $lines = ['[Ringkasan Riwayat Percakapan Terbaru]'];

        foreach (array_slice($conversationHistory, -5) as $index => $historyItem) {
            $role = strtolower(trim((string) ($historyItem['role'] ?? '')));
            $text = trim((string) ($historyItem['text'] ?? ''));

            if ($text === '' || ! in_array($role, ['user', 'assistant'], true)) {
                continue;
            }

            $speaker = $role === 'user' ? 'Pelanggan' : 'Asisten';
            $lines[] = ($index + 1) . '. ' . $speaker . ': ' . $text;
        }

        if (count($lines) === 1) {
            return '';
        }

        return implode("\n", $lines);
    }

    private function requestTimeout(): int
    {
        return max(5, (int) config('services.ai.request_timeout', 30));
    }

    private function maxOutputTokens(): int
    {
        // Allow up to 32768 tokens so .env values like 8192 are not silently
        // clamped back to 4096. For Gemini 2.5 thinking models this budget
        // covers BOTH the thinking phase AND the actual reply text, so the
        // ceiling must be large enough to leave room for the real answer.
        return max(256, min(32768, (int) config('services.ai.max_output_tokens', 8192)));
    }

    /**
     * Token budget for internal "thinking" reasoning in Gemini 2.5 models.
     *
     * Set to 0 to disable thinking entirely (recommended for a CS chatbot
     * where speed and token efficiency matter more than deep reasoning).
     * When thinking is disabled the model behaves like a standard
     * non-thinking model and all of maxOutputTokens go to the real reply.
     */
    private function thinkingBudget(): int
    {
        return max(0, min(24576, (int) config('services.ai.thinking_budget', 0)));
    }
}
