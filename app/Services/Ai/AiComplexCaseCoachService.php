<?php

namespace App\Services\Ai;

class AiComplexCaseCoachService
{
    /**
     * @return array<string, mixed>
     */
    public function buildCaseProfile(string $intent, string $message, array $payload = []): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        $normalizedMessage = $this->normalizeMessage($message);

        if ($normalizedMessage === '') {
            return [];
        }

        $issueBuckets = $this->detectIssueBuckets($normalizedMessage);
        $emotionSignal = $this->detectEmotionSignal($normalizedMessage);
        $urgencySignal = $this->detectUrgencySignal($normalizedMessage);

        $complexityScore = $this->calculateComplexityScore(
            $intent,
            $normalizedMessage,
            $issueBuckets,
            $emotionSignal,
            $urgencySignal,
        );

        if ($complexityScore < 35 && count($issueBuckets) === 0 && $emotionSignal === 'neutral') {
            return [];
        }

        $caseWeight = $this->resolveCaseWeight($complexityScore);
        $needsTutorialMode = $this->shouldUseTutorialMode($intent, $complexityScore, $issueBuckets);
        $clarifyingQuestions = $this->buildClarifyingQuestions($issueBuckets, $caseWeight);
        $priorityActions = $this->buildPriorityActions($issueBuckets, $caseWeight);
        $suggestedFollowUp = $this->buildSuggestedFollowUp($issueBuckets, $caseWeight);

        return [
            'intent' => strtolower(trim($intent)),
            'complexity_score' => $complexityScore,
            'case_weight' => $caseWeight,
            'emotion_signal' => $emotionSignal,
            'urgency_signal' => $urgencySignal,
            'issue_bucket_count' => count($issueBuckets),
            'detected_issue_buckets' => $issueBuckets,
            'needs_tutorial_mode' => $needsTutorialMode,
            'recommended_response_blueprint' => [
                'Empati singkat yang tulus dan validasi emosi user.',
                'Ringkas masalah utama agar user merasa dipahami.',
                'Diagnosis akar masalah berdasarkan data yang tersedia.',
                'Langkah solusi bernomor yang bisa langsung dijalankan user.',
                'Verifikasi hasil, lalu berikan langkah pencegahan agar tidak terulang.',
            ],
            'clarifying_questions' => $clarifyingQuestions,
            'priority_actions' => $priorityActions,
            'suggested_follow_up' => $suggestedFollowUp,
            'context_flags' => [
                'has_order_code' => preg_match('/ord-arip-\d{8}-[a-z0-9]{6}/i', $normalizedMessage) === 1,
                'has_multi_issue_signal' => count($issueBuckets) >= 2,
                'has_context_payload' => is_array($payload['context'] ?? null) && count((array) $payload['context']) > 0,
            ],
        ];
    }

    private function isEnabled(): bool
    {
        return filter_var(config('services.ai.complex_case_enabled', true), FILTER_VALIDATE_BOOLEAN) !== false;
    }

    private function normalizeMessage(string $message): string
    {
        $normalized = strtolower(trim($message));

        return (string) preg_replace('/\s+/', ' ', $normalized);
    }

    /**
     * @return array<int, string>
     */
    private function detectIssueBuckets(string $message): array
    {
        $bucketMap = [
            'payment' => [
                'bayar',
                'pembayaran',
                'transfer',
                'bukti',
                'qris',
                'ditolak',
                'gagal bayar',
                'metode pembayaran',
            ],
            'shipping' => [
                'kirim',
                'pengiriman',
                'kurir',
                'belum sampai',
                'belum dikirim',
                'salah alamat',
                'resi',
                'paket',
            ],
            'product_quality' => [
                'rusak',
                'cacat',
                'retak',
                'pecah',
                'tidak sesuai',
                'beda barang',
                'garansi',
                'klaim',
            ],
            'account_website' => [
                'login',
                'password',
                'akun',
                'error',
                'checkout gagal',
                'tidak bisa masuk',
                'alamat tidak tersimpan',
                'halaman',
            ],
            'privacy_security' => [
                'privasi',
                'privacy',
                'aman',
                'takut bocor',
                'disalahgunakan',
                'data saya',
                'keamanan',
                'ragu upload',
            ],
        ];

        $detectedBuckets = [];

        foreach ($bucketMap as $bucket => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($message, $keyword)) {
                    $detectedBuckets[] = $bucket;
                    break;
                }
            }
        }

        return array_values(array_unique($detectedBuckets));
    }

    private function detectEmotionSignal(string $message): string
    {
        $angryKeywords = ['marah', 'kesal', 'kecewa berat', 'parah', 'ngaco', 'frustasi'];
        $worriedKeywords = ['panik', 'khawatir', 'takut', 'cemas', 'ragu'];
        $sadKeywords = ['sedih', 'capek', 'lelah', 'nyerah'];

        if ($this->containsAnyKeyword($message, $angryKeywords)) {
            return 'frustrated';
        }

        if ($this->containsAnyKeyword($message, $worriedKeywords)) {
            return 'worried';
        }

        if ($this->containsAnyKeyword($message, $sadKeywords)) {
            return 'discouraged';
        }

        return 'neutral';
    }

    private function detectUrgencySignal(string $message): string
    {
        $highUrgencyKeywords = ['sekarang', 'secepatnya', 'darurat', 'urgent', 'segera', 'hari ini'];
        $mediumUrgencyKeywords = ['cepat', 'lama banget', 'lama sekali', 'belum juga'];

        if ($this->containsAnyKeyword($message, $highUrgencyKeywords)) {
            return 'high';
        }

        if ($this->containsAnyKeyword($message, $mediumUrgencyKeywords)) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * @param array<int, string> $issueBuckets
     */
    private function calculateComplexityScore(string $intent, string $message, array $issueBuckets, string $emotionSignal, string $urgencySignal): int
    {
        $normalizedIntent = strtolower(trim($intent));
        $score = in_array($normalizedIntent, ['troubleshooting', 'emotional_support'], true) ? 24 : 10;

        $score += min(40, count($issueBuckets) * 14);

        $questionMarks = preg_match_all('/\?/', $message, $matches);
        $score += min(16, $questionMarks * 4);

        $connectorKeywords = [' dan ', ' tapi ', ' plus ', ' sekaligus ', ' sementara ', ' terus '];
        $connectorCount = 0;
        foreach ($connectorKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                $connectorCount++;
            }
        }
        $score += min(14, $connectorCount * 3);

        $score += match ($emotionSignal) {
            'frustrated' => 16,
            'worried' => 12,
            'discouraged' => 10,
            default => 0,
        };

        $score += match ($urgencySignal) {
            'high' => 14,
            'medium' => 8,
            default => 0,
        };

        if (mb_strlen($message) >= 220) {
            $score += 8;
        }

        return max(0, min(100, $score));
    }

    private function resolveCaseWeight(int $complexityScore): string
    {
        $highThreshold = max(45, (int) config('services.ai.complex_case_high_threshold', 65));
        $criticalThreshold = max($highThreshold + 10, (int) config('services.ai.complex_case_critical_threshold', 85));

        if ($complexityScore >= $criticalThreshold) {
            return 'critical';
        }

        if ($complexityScore >= $highThreshold) {
            return 'high';
        }

        if ($complexityScore >= max(30, $highThreshold - 15)) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * @param array<int, string> $issueBuckets
     */
    private function shouldUseTutorialMode(string $intent, int $complexityScore, array $issueBuckets): bool
    {
        $normalizedIntent = strtolower(trim($intent));

        if (in_array($normalizedIntent, ['website_help', 'troubleshooting', 'emotional_support'], true)) {
            return true;
        }

        if ($complexityScore >= 55) {
            return true;
        }

        return count($issueBuckets) >= 2;
    }

    /**
     * @param array<int, string> $issueBuckets
     * @return array<int, string>
     */
    private function buildClarifyingQuestions(array $issueBuckets, string $caseWeight): array
    {
        $questionMap = [
            'payment' => [
                'Boleh info kode pesanan (ORD-ARIP-...) dan jam transfer terakhirnya?',
                'Bukti transfer yang diupload sudah menampilkan nominal dan tanggal dengan jelas?',
            ],
            'shipping' => [
                'Status pesanan saat ini apa di halaman Cek Pesanan (pending/processing/shipped)?',
                'Kalau sudah ada resi, boleh dibagikan agar bisa dicek tahap pengirimannya?',
            ],
            'product_quality' => [
                'Kerusakan produk yang dialami muncul sejak awal diterima atau setelah dipakai?',
                'Apakah sudah ada foto/video kondisi produk untuk mempercepat verifikasi klaim?',
            ],
            'account_website' => [
                'Boleh share langkah terakhir sebelum error muncul agar diagnosis lebih akurat?',
                'Error terjadi di perangkat/browser apa saat ini?',
            ],
            'privacy_security' => [
                'Kakak lebih nyaman pakai metode otomatis QRIS (Bayar.gg) tanpa upload bukti transfer?',
                'Bagian privasi mana yang paling dikhawatirkan supaya saya jelaskan fokus di situ?',
            ],
        ];

        $questions = [];

        foreach ($issueBuckets as $issueBucket) {
            $questions = array_merge($questions, $questionMap[$issueBucket] ?? []);
        }

        if ($caseWeight === 'critical') {
            $questions[] = 'Masalah paling urgent yang perlu diselesaikan duluan saat ini yang mana, kak?';
        }

        return array_values(array_slice(array_unique($questions), 0, 4));
    }

    /**
     * @param array<int, string> $issueBuckets
     * @return array<int, string>
     */
    private function buildPriorityActions(array $issueBuckets, string $caseWeight): array
    {
        $actionMap = [
            'payment' => [
                'Validasi ulang nominal pembayaran dan kualitas bukti transfer (jelas, tidak blur, lengkap).',
                'Pandukan user upload ulang bukti via menu Cek Pesanan lalu konfirmasi status verifikasi.',
            ],
            'shipping' => [
                'Cek status order dan identifikasi bottleneck: menunggu verifikasi, proses gudang, atau pengiriman kurir.',
                'Berikan next action berbasis status terkini agar user tahu estimasi dan langkah berikutnya.',
            ],
            'product_quality' => [
                'Diagnosis gejala kerusakan dan tentukan apakah eligible klaim garansi.',
                'Berikan tutorial pengajuan klaim dengan checklist bukti agar proses cepat.',
            ],
            'account_website' => [
                'Berikan troubleshooting teknis berurutan: refresh, cache, browser alternatif, dan validasi field wajib.',
                'Minta satu bukti error ringkas (screenshot/pesan error) bila masalah berulang.',
            ],
            'privacy_security' => [
                'Tekankan jaminan privasi data secara tegas dan jelaskan batas akses internal admin.',
                'Tawarkan metode pembayaran otomatis QRIS sebagai alternatif minim friksi.',
            ],
        ];

        $actions = [];

        foreach ($issueBuckets as $issueBucket) {
            $actions = array_merge($actions, $actionMap[$issueBucket] ?? []);
        }

        if ($caseWeight === 'critical') {
            $actions[] = 'Jika langkah mandiri selesai tapi masalah tetap ada, eskalasi cepat ke admin dengan kode order + ringkasan kronologi singkat.';
        }

        if ($actions === []) {
            $actions[] = 'Berikan diagnosis singkat, langkah mandiri bernomor, lalu verifikasi hasil sebelum eskalasi.';
        }

        return array_values(array_slice(array_unique($actions), 0, 5));
    }

    /**
     * @param array<int, string> $issueBuckets
     * @return array<int, string>
     */
    private function buildSuggestedFollowUp(array $issueBuckets, string $caseWeight): array
    {
        $followUp = [];

        if (in_array('payment', $issueBuckets, true)) {
            $followUp[] = 'Cek ulang langkah upload bukti pembayaran';
            $followUp[] = 'Mau saya pandu verifikasi status pembayaran sekarang?';
        }

        if (in_array('shipping', $issueBuckets, true)) {
            $followUp[] = 'Mau saya bantu baca status pesanan per tahap?';
        }

        if (in_array('product_quality', $issueBuckets, true)) {
            $followUp[] = 'Panduan klaim garansi step-by-step';
        }

        if (in_array('privacy_security', $issueBuckets, true)) {
            $followUp[] = 'Alternatif pembayaran QRIS otomatis tanpa upload bukti';
        }

        if ($caseWeight === 'critical') {
            $followUp[] = 'Jika perlu, saya rangkum kronologi singkat untuk dikirim ke admin';
        }

        if ($followUp === []) {
            $followUp[] = 'Butuh panduan langkah demi langkah sesuai kasus kakak?';
        }

        return array_values(array_slice(array_unique($followUp), 0, 4));
    }

    private function containsAnyKeyword(string $message, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
