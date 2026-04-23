<?php

namespace App\Services\Ai;

use Illuminate\Support\Str;

class AiResponseQualityCoachService
{
    private const MAX_SUGGESTION_COUNT = 6;

    private const MAX_RECOMMENDED_ACTION_COUNT = 4;

    public function coach(array $responsePayload, string $resolvedIntent, array $requestPayload = []): array
    {
        $normalizedIntent = $this->resolveIntent($responsePayload, $resolvedIntent);
        $existingSuggestions = $this->normalizeSuggestions($responsePayload['suggestions'] ?? []);

        $guidancePackage = $this->buildGuidancePackage($normalizedIntent, $responsePayload, $requestPayload);
        $mergedSuggestions = $this->mergeSuggestions($existingSuggestions, $guidancePackage['suggestion_seed']);

        if ($mergedSuggestions !== []) {
            $responsePayload['suggestions'] = $mergedSuggestions;
        }

        $responseData = is_array($responsePayload['data'] ?? null)
            ? $responsePayload['data']
            : [];

        $responseData['assistant_guidance'] = [
            'intent_focus' => $normalizedIntent,
            'core_summary' => $guidancePackage['core_summary'],
            'recommended_actions' => $guidancePackage['recommended_actions'],
            'follow_up_question' => $guidancePackage['follow_up_question'],
            'quality_version' => 'v1',
        ];

        if ($guidancePackage['escalation_path'] !== null) {
            $responseData['assistant_guidance']['escalation_path'] = $guidancePackage['escalation_path'];
        }

        $responsePayload['data'] = $responseData;

        $currentReply = trim((string) ($responsePayload['reply'] ?? ''));

        if ($this->shouldAppendActionDigest($normalizedIntent, $currentReply, $responseData, $guidancePackage['recommended_actions'])) {
            $responsePayload['reply'] = $this->appendActionDigest($currentReply, $guidancePackage['recommended_actions']);
        }

        return $responsePayload;
    }

    private function resolveIntent(array $responsePayload, string $resolvedIntent): string
    {
        $normalizedIntent = strtolower(trim($resolvedIntent));

        if ($normalizedIntent !== '') {
            return $normalizedIntent;
        }

        $fallbackIntent = strtolower(trim((string) ($responsePayload['intent'] ?? '')));

        return $fallbackIntent !== '' ? $fallbackIntent : 'faq';
    }

    /**
     * @return array{
     *     core_summary: string,
     *     recommended_actions: array<int, string>,
     *     follow_up_question: string,
     *     escalation_path: string|null,
     *     suggestion_seed: array<int, string>
     * }
     */
    private function buildGuidancePackage(string $normalizedIntent, array $responsePayload, array $requestPayload): array
    {
        $responseData = is_array($responsePayload['data'] ?? null)
            ? $responsePayload['data']
            : [];

        return match ($normalizedIntent) {
            'order_tracking' => $this->buildOrderTrackingGuidance($responseData),
            'product_recommendation' => $this->buildProductRecommendationGuidance($responseData),
            'website_help' => $this->buildWebsiteHelpGuidance($responseData),
            'troubleshooting' => $this->buildTroubleshootingGuidance($responseData),
            'store_info' => $this->buildStoreInfoGuidance(),
            'emotional_support' => $this->buildEmotionalSupportGuidance(),
            'off_topic' => $this->buildOffTopicGuidance(),
            default => $this->buildFaqGuidance($requestPayload),
        };
    }

    /**
     * @param mixed $suggestions
     * @return array<int, string>
     */
    private function normalizeSuggestions(mixed $suggestions): array
    {
        if (! is_array($suggestions)) {
            return [];
        }

        $normalizedSuggestions = [];

        foreach ($suggestions as $suggestion) {
            $normalizedSuggestion = trim((string) $suggestion);

            if ($normalizedSuggestion === '') {
                continue;
            }

            $normalizedSuggestions[] = Str::limit($normalizedSuggestion, 110, '');
        }

        return array_values(array_unique($normalizedSuggestions));
    }

    /**
     * @param array<int, string> $existingSuggestions
     * @param array<int, string> $seedSuggestions
     * @return array<int, string>
     */
    private function mergeSuggestions(array $existingSuggestions, array $seedSuggestions): array
    {
        $mergedSuggestions = array_values(array_unique(array_merge($existingSuggestions, $seedSuggestions)));

        return array_slice($mergedSuggestions, 0, self::MAX_SUGGESTION_COUNT);
    }

    /**
     * @param array<string, mixed> $responseData
     * @return array{
     *     core_summary: string,
     *     recommended_actions: array<int, string>,
     *     follow_up_question: string,
     *     escalation_path: string|null,
     *     suggestion_seed: array<int, string>
     * }
     */
    private function buildOrderTrackingGuidance(array $responseData): array
    {
        $orderData = is_array($responseData['order'] ?? null)
            ? $responseData['order']
            : [];

        $orderCode = trim((string) ($orderData['order_code'] ?? ''));
        $orderStatus = trim((string) ($orderData['status'] ?? 'pending'));
        $paymentStatus = trim((string) ($orderData['payment_status'] ?? 'pending'));

        $recommendedActions = [
            'Cek status terbaru pesanan di menu Cek Pesanan untuk memastikan progres realtime.',
            'Pastikan status pembayaran sudah sesuai agar pesanan bisa lanjut diproses.',
            'Siapkan kode pesanan sebelum menghubungi admin agar investigasi lebih cepat.',
        ];

        if ($orderStatus === 'shipped') {
            $recommendedActions[0] = 'Pantau nomor resi secara berkala sampai paket diterima.';
        }

        if ($paymentStatus === 'pending') {
            $recommendedActions[1] = 'Selesaikan atau verifikasi pembayaran terlebih dahulu agar proses pengiriman tidak tertunda.';
        }

        return [
            'core_summary' => $orderCode !== ''
                ? 'Fokus utama: bantu user menuntaskan tracking pesanan ' . $orderCode . ' sampai statusnya jelas.'
                : 'Fokus utama: validasi identitas pesanan user lalu berikan status dan aksi paling relevan.',
            'recommended_actions' => array_slice($recommendedActions, 0, self::MAX_RECOMMENDED_ACTION_COUNT),
            'follow_up_question' => 'Mau saya bantu cek langkah paling tepat sesuai status pesanan saat ini?',
            'escalation_path' => 'Jika status tidak berubah melewati estimasi normal, arahkan user ke WhatsApp admin dengan kode order.',
            'suggestion_seed' => [
                'Cek detail status pesanan terbaru',
                'Verifikasi metode pembayaran pesanan',
                'Siapkan kode order untuk bantuan admin',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $responseData
     * @return array{
     *     core_summary: string,
     *     recommended_actions: array<int, string>,
     *     follow_up_question: string,
     *     escalation_path: string|null,
     *     suggestion_seed: array<int, string>
     * }
     */
    private function buildProductRecommendationGuidance(array $responseData): array
    {
        $products = is_array($responseData['products'] ?? null)
            ? $responseData['products']
            : [];

        $topProductNames = $this->collectTopProductNames($products, 2);

        $recommendedActions = [
            'Bandingkan minimal dua opsi produk agar user paham trade-off harga dan spesifikasi.',
            'Pilih produk dengan stok aman serta spesifikasi yang paling cocok dengan kebutuhan user.',
            'Arahkan user lanjut checkout dari halaman produk yang dipilih supaya proses beli lebih cepat.',
        ];

        if ($topProductNames !== []) {
            $recommendedActions[0] = 'Prioritaskan perbandingan produk: ' . implode(' dan ', $topProductNames) . '.';
        }

        return [
            'core_summary' => $topProductNames !== []
                ? 'Fokus rekomendasi: tingkatkan keyakinan user untuk memilih antara ' . implode(' atau ', $topProductNames) . '.'
                : 'Fokus rekomendasi: bantu user memilih produk paling tepat berdasarkan kebutuhan dan budget.',
            'recommended_actions' => array_slice($recommendedActions, 0, self::MAX_RECOMMENDED_ACTION_COUNT),
            'follow_up_question' => 'Mau saya bantu bandingkan plus-minus tiap opsi supaya lebih yakin pilihnya?',
            'escalation_path' => null,
            'suggestion_seed' => [
                'Bandingkan dua produk teratas',
                'Cek stok dan spesifikasi detail',
                'Lanjut checkout produk yang dipilih',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $responseData
     * @return array{
     *     core_summary: string,
     *     recommended_actions: array<int, string>,
     *     follow_up_question: string,
     *     escalation_path: string|null,
     *     suggestion_seed: array<int, string>
     * }
     */
    private function buildWebsiteHelpGuidance(array $responseData): array
    {
        $pageContext = is_array($responseData['page_context'] ?? null)
            ? $responseData['page_context']
            : [];

        $pagePath = trim((string) ($pageContext['page_path'] ?? ''));

        $recommendedActions = [
            'Jelaskan langkah penggunaan fitur secara berurutan agar user bisa eksekusi tanpa bingung.',
            'Minta user verifikasi hasil di akhir langkah supaya masalah tidak berulang.',
            'Jika ada hambatan, arahkan user ke alternatif langkah yang paling aman.',
        ];

        if ($pagePath !== '') {
            $recommendedActions[0] = 'Prioritaskan panduan langkah yang relevan dengan halaman aktif: ' . $pagePath . '.';
        }

        return [
            'core_summary' => 'Fokus utama: ubah jawaban menjadi tutorial praktis yang bisa langsung diikuti user.',
            'recommended_actions' => array_slice($recommendedActions, 0, self::MAX_RECOMMENDED_ACTION_COUNT),
            'follow_up_question' => 'Bagian langkah mana yang masih membingungkan supaya saya bisa jelaskan lebih detail?',
            'escalation_path' => null,
            'suggestion_seed' => [
                'Panduan langkah detail sesuai halaman',
                'Cek ulang hasil setelah tiap langkah',
                'Alternatif solusi jika langkah utama gagal',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $responseData
     * @return array{
     *     core_summary: string,
     *     recommended_actions: array<int, string>,
     *     follow_up_question: string,
     *     escalation_path: string|null,
     *     suggestion_seed: array<int, string>
     * }
     */
    private function buildTroubleshootingGuidance(array $responseData): array
    {
        $complexCaseProfile = is_array($responseData['complex_case_profile'] ?? null)
            ? $responseData['complex_case_profile']
            : [];

        $priorityActions = is_array($complexCaseProfile['priority_actions'] ?? null)
            ? $this->normalizeSuggestions($complexCaseProfile['priority_actions'])
            : [];

        if ($priorityActions === []) {
            $priorityActions = [
                'Identifikasi akar masalah paling mungkin dari gejala yang disampaikan user.',
                'Berikan 2-4 langkah mandiri yang bisa dieksekusi user saat itu juga.',
                'Lakukan verifikasi hasil sebelum mengarahkan eskalasi ke admin.',
            ];
        }

        $clarifyingQuestions = is_array($complexCaseProfile['clarifying_questions'] ?? null)
            ? $this->normalizeSuggestions($complexCaseProfile['clarifying_questions'])
            : [];

        return [
            'core_summary' => 'Fokus troubleshooting: validasi masalah user, beri langkah mandiri jelas, lalu verifikasi hasil.',
            'recommended_actions' => array_slice($priorityActions, 0, self::MAX_RECOMMENDED_ACTION_COUNT),
            'follow_up_question' => $clarifyingQuestions[0] ?? 'Bagian mana yang paling urgent untuk diselesaikan lebih dulu?',
            'escalation_path' => 'Jika langkah mandiri gagal, eskalasi ke admin dengan ringkasan kronologi + kode pesanan.',
            'suggestion_seed' => [
                'Diagnosa akar masalah paling mungkin',
                'Jalankan langkah mandiri secara berurutan',
                'Siapkan data untuk eskalasi cepat',
            ],
        ];
    }

    /**
     * @return array{
     *     core_summary: string,
     *     recommended_actions: array<int, string>,
     *     follow_up_question: string,
     *     escalation_path: string|null,
     *     suggestion_seed: array<int, string>
     * }
     */
    private function buildStoreInfoGuidance(): array
    {
        return [
            'core_summary' => 'Fokus store_info: berikan informasi toko yang lengkap dan mudah ditindaklanjuti user.',
            'recommended_actions' => [
                'Sampaikan informasi inti toko secara ringkas dan akurat.',
                'Tambahkan jalur kontak cepat agar user bisa lanjut bertanya jika perlu.',
                'Arahkan user ke halaman relevan untuk aksi berikutnya.',
            ],
            'follow_up_question' => 'Mau saya bantu arahkan ke kontak atau halaman yang paling relevan sekarang?',
            'escalation_path' => null,
            'suggestion_seed' => [
                'Lihat alamat dan kontak toko',
                'Cek jam operasional terbaru',
                'Buka halaman bantuan belanja',
            ],
        ];
    }

    /**
     * @return array{
     *     core_summary: string,
     *     recommended_actions: array<int, string>,
     *     follow_up_question: string,
     *     escalation_path: string|null,
     *     suggestion_seed: array<int, string>
     * }
     */
    private function buildEmotionalSupportGuidance(): array
    {
        return [
            'core_summary' => 'Fokus emotional_support: validasi emosi user dulu, lalu arahkan ke solusi yang menenangkan.',
            'recommended_actions' => [
                'Validasi perasaan user secara tulus sebelum membahas solusi teknis.',
                'Tawarkan langkah kecil yang paling mudah dilakukan user saat ini.',
                'Pastikan user tahu kanal bantuan prioritas jika butuh penanganan lanjut.',
            ],
            'follow_up_question' => 'Mau saya bantu urutkan langkah paling ringan dulu supaya tidak terasa berat?',
            'escalation_path' => 'Jika emosi user tetap tinggi, tawarkan jalur komunikasi langsung dengan admin.',
            'suggestion_seed' => [
                'Ceritakan masalah paling mengganggu dulu',
                'Pilih langkah bantuan paling ringan',
                'Hubungi admin untuk bantuan personal',
            ],
        ];
    }

    /**
     * @return array{
     *     core_summary: string,
     *     recommended_actions: array<int, string>,
     *     follow_up_question: string,
     *     escalation_path: string|null,
     *     suggestion_seed: array<int, string>
     * }
     */
    private function buildOffTopicGuidance(): array
    {
        return [
            'core_summary' => 'Fokus off_topic: tolak sopan lalu redirect user ke konteks layanan toko.',
            'recommended_actions' => [
                'Acknowledge pertanyaan user dengan ramah.',
                'Redirect ke topik produk atau layanan toko secara natural.',
                'Tawarkan bantuan yang relevan dengan kebutuhan belanja user.',
            ],
            'follow_up_question' => 'Mau saya bantu cari produk atau info toko yang lagi kakak butuhkan?',
            'escalation_path' => null,
            'suggestion_seed' => [
                'Rekomendasi produk sesuai kebutuhan',
                'Panduan belanja cepat di website',
                'Cek alamat dan kontak toko',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $requestPayload
     * @return array{
     *     core_summary: string,
     *     recommended_actions: array<int, string>,
     *     follow_up_question: string,
     *     escalation_path: string|null,
     *     suggestion_seed: array<int, string>
     * }
     */
    private function buildFaqGuidance(array $requestPayload): array
    {
        $message = trim((string) ($requestPayload['message'] ?? ''));

        return [
            'core_summary' => $message !== ''
                ? 'Fokus FAQ: jawab inti pertanyaan user lalu berikan arah tindakan paling relevan.'
                : 'Fokus FAQ: berikan jawaban ringkas yang membantu user lanjut ke aksi berikutnya.',
            'recommended_actions' => [
                'Jawab pertanyaan inti user secara langsung dan mudah dipahami.',
                'Tambahkan rekomendasi lanjutan yang relevan dengan konteks user.',
                'Arahkan user ke langkah konkret setelah membaca jawaban.',
            ],
            'follow_up_question' => 'Mau saya lanjutkan dengan panduan yang lebih detail sesuai kebutuhan kakak?',
            'escalation_path' => null,
            'suggestion_seed' => [
                'Lanjutkan ke langkah berikutnya',
                'Minta panduan lebih detail',
                'Konsultasi kebutuhan produk',
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $products
     * @return array<int, string>
     */
    private function collectTopProductNames(array $products, int $limit): array
    {
        $productNames = [];

        foreach (array_slice($products, 0, max(1, $limit)) as $product) {
            if (! is_array($product)) {
                continue;
            }

            $productName = trim((string) ($product['name'] ?? ''));

            if ($productName === '') {
                continue;
            }

            $productNames[] = $productName;
        }

        return array_values(array_unique($productNames));
    }

    /**
     * @param array<string, mixed> $responseData
     * @param array<int, string> $recommendedActions
     */
    private function shouldAppendActionDigest(string $normalizedIntent, string $currentReply, array $responseData, array $recommendedActions): bool
    {
        if ($recommendedActions === []) {
            return false;
        }

        if (in_array($normalizedIntent, ['off_topic', 'emotional_support'], true)) {
            return false;
        }

        $llmStatus = strtolower(trim((string) data_get($responseData, 'llm.status', '')));
        if (in_array($llmStatus, ['primary_success', 'fallback_success'], true)) {
            return false;
        }

        if ($currentReply !== '') {
            $hasNumberedStep = preg_match('/(^|\n)\s*\d+[\.)]\s+/m', $currentReply) === 1;
            $hasActionKeyword = preg_match('/\b(langkah|step|cara|cek|pastikan|pilih|klik|buka)\b/i', $currentReply) === 1;

            if ($hasNumberedStep || $hasActionKeyword) {
                return false;
            }

            if (str_contains(strtolower($currentReply), 'ringkasan aksi cepat')) {
                return false;
            }

            if (mb_strlen($currentReply) > 1600) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, string> $recommendedActions
     */
    private function appendActionDigest(string $currentReply, array $recommendedActions): string
    {
        $digestLines = ['Ringkasan aksi cepat:'];

        foreach (array_slice($recommendedActions, 0, 3) as $index => $recommendedAction) {
            $digestLines[] = ($index + 1) . '. ' . $recommendedAction;
        }

        $digestText = implode("\n", $digestLines);

        if ($currentReply === '') {
            return $digestText;
        }

        return trim($currentReply) . "\n\n" . $digestText;
    }
}
