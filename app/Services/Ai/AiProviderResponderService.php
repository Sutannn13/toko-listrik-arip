<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class AiProviderResponderService
{
    public function __construct(
        private readonly StoreKnowledgeService $storeKnowledge,
        private readonly CustomerVoiceInsightService $customerVoiceInsight,
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
        $systemPrompt = $this->buildSystemPrompt($intent);
        $userPrompt = $this->buildUserPrompt($intent, $message, $toolReply, $suggestions, $dataContext);
        $attempts = [];

        try {
            $reply = $this->requestCompletion($primaryProvider, $primaryModel, $systemPrompt, $userPrompt);
            $attempts[] = $this->buildAttempt($primaryProvider, $primaryModel, true);

            return [
                'reply' => $reply,
                'provider' => $primaryProvider,
                'model' => $primaryModel,
                'fallback_used' => false,
                'status' => 'primary_success',
                'attempts' => $attempts,
            ];
        } catch (Throwable $exception) {
            report($exception);
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
            ];
        }

        try {
            $reply = $this->requestCompletion($fallbackProvider, $fallbackModel, $systemPrompt, $userPrompt);
            $attempts[] = $this->buildAttempt($fallbackProvider, $fallbackModel, true);

            return [
                'reply' => $reply,
                'provider' => $fallbackProvider,
                'model' => $fallbackModel,
                'fallback_used' => true,
                'status' => 'fallback_success',
                'attempts' => $attempts,
            ];
        } catch (Throwable $exception) {
            report($exception);
            $attempts[] = $this->buildAttempt($fallbackProvider, $fallbackModel, false, $exception);

            return [
                'reply' => null,
                'provider' => $fallbackProvider,
                'model' => $fallbackModel,
                'fallback_used' => false,
                'status' => 'fallback_failed',
                'attempts' => $attempts,
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

        $response = Http::acceptJson()
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
                'generationConfig' => [
                    'temperature' => 0.3,
                    'maxOutputTokens' => $this->maxOutputTokens(),
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gemini request gagal dengan status HTTP ' . $response->status() . '.');
        }

        $reply = trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text', ''));

        if ($reply === '') {
            throw new RuntimeException('Gemini tidak mengembalikan teks jawaban.');
        }

        return $reply;
    }

    private function requestDeepSeekCompletion(string $model, string $systemPrompt, string $userPrompt): string
    {
        $apiKey = trim((string) config('services.ai.deepseek_api_key', ''));

        if ($apiKey === '') {
            throw new RuntimeException('AI_DEEPSEEK_API_KEY belum diisi.');
        }

        $response = Http::acceptJson()
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
        $customerVoiceContext = $this->customerVoiceInsight->buildCustomerVoiceContext();
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
            '',
            '# STANDAR KECERDASAN & SOLUSI',
            '- Pikir kritis! Kalau user nanya harga lampu tapi budgetnya sempit, proaktif tawarkan yang paling worth-it.',
            '- Kalau user bingung soal teknis (wattage, lumens, cara pasang), jelaskan selayaknya abang-abang jago listrik yang jelasin ke orang awam. Singkat, padat, masuk akal.',
            '- Jangan ngarang data! Harga, spek, ongkir, alamat, kebijakan toko, harus 100% SESUAI dengan data yang diberikan kepadamu.',
            '- Kalau data tidak ada/tidak lengkap, jangan minta maaf berlebihan seperti bot. Bilang saja santai: "Waduh kak, kebetulan untuk detail yang itu lagi kosong nih infonya, coba langsung chat admin di WhatsApp aja ya biar dicek langsung ke gudang: [Link WA]".',
            '- Jika user marah/komplain, jadilah sangat empatik. Posisikan dirimu minta maaf yang tulus dan berikan solusi secepatnya (arahkan ke WA CS).',
            '',
            '# KECERDASAN KONTEKSTUAL (BACA MAKSUD TERSEMBUNYI USER)',
            '- "cara menambahkan alamat" → Dia mau input alamat di profil websitenya biar bisa checkout, BUKAN nanya alamat toko kita.',
            '- "apakah diantar?" / "apakah kena ongkir?" → Dia nanya sistem delivery kita. Jawab santai: "Diantar langsung sampai depan pintu rumah kakak kok pakai kurir! Ongkirnya...". (COD juga artinya diantar, nggak usah ambil ke toko).',
            '- "pesanan saya mana?" → Dia lagi tracking. Jelaskan cara lihat menu cek pesanan.',
            '',
            '# ATURAN ABSOLUT (PENALTI JIKA DILANGGAR)',
            '1. Jangan pernah mengaku mesin/AI/Bot. Kamu adalah staf toko asli.',
            '2. Link yang kamu berikan harus bisa di-klik dan hanya dari database yang diberikan.',
            '3. Jawab tepat sasaran, jangan berbelit-belit. Kalau dia nanya A, jawab A. Kalau ada info tambahan yang krusial (misal nanya COD, sekalian sebut ongkirnya), boleh ditambahin dengan halus.',
        ]);

        $sections = [
            $personality,
            '',
            '# DATA TOKO (SUMBER KEBENARAN)',
            $storeContext,
            '',
            '# SUARA USER & MASALAH PRODUK TERBARU',
            $customerVoiceContext,
            '',
            $catalogSummary,
        ];

        if ($intent === 'product_recommendation') {
            $sections[] = '';
            $sections[] = '# PANDUAN REKOMENDASI PRODUK';
            $sections[] = '- Ketika merekomendasikan produk, jelaskan MENGAPA produk itu cocok berdasarkan kebutuhan user.';
            $sections[] = '- Jika user menyebut ruangan (kamar tidur, dapur, dll), berikan saran watt, tipe cahaya, dan desain yang cocok.';
            $sections[] = '- Jika ada budget, filter produk yang masuk budget dan berikan pilihan terbaik dengan penjelasan value-for-money.';
            $sections[] = '- Sertakan tips penggunaan jika relevan (misalnya: watt ideal, warm white vs cool daylight, hemat listrik, dll).';
            $sections[] = '- Gunakan pengetahuan umum tentang produk listrik untuk memberikan saran yang bermanfaat dan meyakinkan.';
            $sections[] = '- Jika produk yang pas tidak tersedia, sarankan alternatif terdekat atau hubungi WhatsApp toko.';
            $sections[] = '- Jika [INTERNAL KNOWLEDGE] menyertakan web_search, gunakan itu sebagai referensi tambahan dan tampilkan sumber URL secara ringkas.';
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
            $sections[] = '# PANDUAN TROUBLESHOOTING & PROBLEM SOLVING';
            $sections[] = '- User SEDANG PUNYA MASALAH. Ini BUKAN FAQ biasa. Mereka frustrasi dan butuh SOLUSI KONKRET.';
            $sections[] = '- FORMAT JAWABAN WAJIB: (1) Empati dulu, (2) Diagnosis masalah, (3) Solusi langkah demi langkah, (4) Barulah tawarkan WhatsApp jika masalah butuh verifikasi admin.';
            $sections[] = '- JANGAN langsung bilang "hubungi WhatsApp". Itu malas. Beri solusi yang bisa dilakukan user SENDIRI dulu.';
            $sections[] = '';
            $sections[] = '## TEMPLATE MASALAH PEMBAYARAN';
            $sections[] = '- "Pembayaran ditolak/gagal" → Kemungkinan: (a) file bukti terlalu besar (max 2MB), (b) format bukan JPG/PNG, (c) admin belum verifikasi. Solusi: cek ukuran file, coba kompres, upload ulang lewat menu Cek Pesanan.';
            $sections[] = '- "Sudah transfer tapi status belum berubah" → Admin memverifikasi manual, butuh waktu 1-3 jam. Jika lebih dari 3 jam, baru arahkan WA.';
            $sections[] = '- "Bukti pembayaran ditolak" → Alasan umum: foto blur, nominal tidak sesuai, rekening pengirim tidak jelas. Solusi: screenshot ulang yang jelas, pastikan nominal terlihat.';
            $sections[] = '';
            $sections[] = '## TEMPLATE MASALAH PENGIRIMAN';
            $sections[] = '- "Pesanan lama/belum dikirim" → Cek apakah pembayaran sudah lunas (status paid). Jika belum, ingatkan user upload bukti bayar. Jika sudah paid, berikan estimasi 1-2 hari kerja dan arahkan WA admin.';
            $sections[] = '- "Salah alamat" → Jika status masih pending/processing, bisa diubah: hubungi admin segera via WA. Jika status shipped, alamat tidak bisa diubah.';
            $sections[] = '- "Paket hilang" → Arahkan cek resi di menu Cek Pesanan. Jika resi valid dan sudah lama, hubungi WA admin.';
            $sections[] = '';
            $sections[] = '## TEMPLATE MASALAH PRODUK';
            $sections[] = '- "Barang rusak/tidak sesuai" → Cek apakah masih dalam masa garansi (sesuai produk, maksimal 365 hari untuk elektronik). Jika ya, ajukan klaim garansi di menu Garansi. Jelaskan step-by-step cara klaim.';
            $sections[] = '- "Barang kurang/salah" → Minta maaf, dan arahkan WA admin dengan kode pesanan untuk pengecekan.';
            $sections[] = '';
            $sections[] = '## TEMPLATE MASALAH AKUN';
            $sections[] = '- "Tidak bisa login" → Step: (a) cek capslock, (b) cek email benar, (c) klik Lupa Password untuk reset, (d) cek email untuk link reset.';
            $sections[] = '- "Akun diblokir/disuspend" → Hubungi admin via WA, berikan email akun yang terdaftar agar bisa dikonfirmasi.';
            $sections[] = '';
            $sections[] = '## ATURAN TROUBLESHOOTING';
            $sections[] = '- SELALU berikan minimal 1-3 langkah yang bisa dilakukan user SENDIRI.';
            $sections[] = '- Barulah jika langkah mandiri tidak bisa menyelesaikan, arahkan WhatsApp admin.';
            $sections[] = '- Gunakan nada empatik: "Waduh, pasti tidak enak ya kak. Tenang, saya bantu ya..."';
            $sections[] = '- JANGAN menyalahkan user. Posisikan diri sebagai pembela customer.';
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

        $rawDataText = count($dataContext) > 0
            ? "[INTERNAL KNOWLEDGE]\n" . json_encode($dataContext, JSON_PRETTY_PRINT) . "\n[/INTERNAL KNOWLEDGE]"
            : '';

        return implode("\n", [
            '[Pertanyaan Pesan dari Kakak (Pelanggan)]',
            trim($message),
            '',
            '[Konteks Arahan Solusi]',
            trim($toolReply),
            '',
            $rawDataText,
            '',
            '[Instruksi Balasan]',
            'Berikan balasan LANGSUNG kepada pelanggan (sebagai manusia, CS HS Electric).',
            'Jawab dengan gaya kasual, pintar, empati tinggi. JANGAN JADIKAN POIN-POIN KAKU.',
            'Jika ada [INTERNAL KNOWLEDGE], itu adalah otakmu. Pahami nilainya dan sampaikan dengan bahasamu sendiri (jangan sebut kata "knowledge" atau "json").',
            'Jika ada data web_search di INTERNAL KNOWLEDGE, gunakan sebagai referensi tambahan dan sebutkan sumber URL agar pelanggan bisa cek mandiri.',
        ]);
    }

    private function requestTimeout(): int
    {
        return max(5, (int) config('services.ai.request_timeout', 20));
    }

    private function maxOutputTokens(): int
    {
        return max(64, min(2048, (int) config('services.ai.max_output_tokens', 800)));
    }
}
