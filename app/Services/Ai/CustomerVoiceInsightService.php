<?php

namespace App\Services\Ai;

use App\Models\AiAssistantFeedback;
use App\Models\Review;
use App\Models\WarrantyClaim;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CustomerVoiceInsightService
{
    private const CACHE_TTL_SECONDS = 300;

    private const MAX_SAMPLE_COUNT = 20;

    public function buildCustomerVoiceContext(): string
    {
        return Cache::remember('ai_customer_voice_context', self::CACHE_TTL_SECONDS, function (): string {
            return $this->compileContext();
        });
    }

    private function compileContext(): string
    {
        $lookbackDays = max(7, min(120, (int) config('services.ai.customer_voice_lookback_days', 45)));
        $startAt = now()->subDays($lookbackDays);

        $negativeFeedback = AiAssistantFeedback::query()
            ->where('rating', -1)
            ->whereNotNull('reason')
            ->where('reason', '!=', '')
            ->where('created_at', '>=', $startAt)
            ->latest('id')
            ->limit(self::MAX_SAMPLE_COUNT)
            ->get(['intent', 'reason']);

        $lowReviews = Review::query()
            ->with('product:id,name')
            ->where('rating', '<=', 2)
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->where('created_at', '>=', $startAt)
            ->latest('id')
            ->limit(self::MAX_SAMPLE_COUNT)
            ->get(['product_id', 'rating', 'comment']);

        $warrantyClaims = WarrantyClaim::query()
            ->with('orderItem:id,product_name')
            ->whereNotNull('reason')
            ->where('reason', '!=', '')
            ->where('created_at', '>=', $startAt)
            ->latest('id')
            ->limit(self::MAX_SAMPLE_COUNT)
            ->get(['order_item_id', 'status', 'reason']);

        $lines = [
            '## SUARA USER TERBARU (DATA INTERNAL NYATA)',
            '- Periode analisis: ' . $lookbackDays . ' hari terakhir.',
        ];

        if ($negativeFeedback->isEmpty() && $lowReviews->isEmpty() && $warrantyClaims->isEmpty()) {
            $lines[] = '- Belum ada cukup data keluhan terbaru. Tetap gunakan empati, ajukan pertanyaan klarifikasi, dan beri solusi langkah demi langkah.';

            return implode("\n", $lines);
        }

        if (! $negativeFeedback->isEmpty()) {
            $lines[] = '';
            $lines[] = '### Keluhan ke jawaban AI';
            $lines[] = '- Total feedback negatif: ' . $negativeFeedback->count() . ' laporan.';

            $intentBreakdown = $negativeFeedback
                ->groupBy(fn(AiAssistantFeedback $feedback): string => (string) ($feedback->intent ?: 'faq'))
                ->map(fn(Collection $group): int => $group->count())
                ->sortDesc()
                ->take(3);

            if (! $intentBreakdown->isEmpty()) {
                $lines[] = '- Intent yang paling sering dikeluhkan: ' . $this->formatDistribution($intentBreakdown) . '.';
            }

            $feedbackReasons = $negativeFeedback
                ->pluck('reason')
                ->map(fn($reason): string => $this->normalizeText((string) $reason))
                ->filter(fn(string $reason): bool => $reason !== '')
                ->values()
                ->all();

            $topKeywords = $this->extractTopKeywords($feedbackReasons, 6);
            if (count($topKeywords) > 0) {
                $lines[] = '- Kata kunci keluhan dominan: ' . implode(', ', $topKeywords) . '.';
            }

            $feedbackExamples = array_slice($feedbackReasons, 0, 3);
            foreach ($feedbackExamples as $index => $example) {
                $lines[] = '- Contoh keluhan ' . ($index + 1) . ': ' . Str::limit($example, 140, '...');
            }
        }

        if (! $lowReviews->isEmpty()) {
            $lines[] = '';
            $lines[] = '### Keluhan produk dari review rating rendah (<= 2)';
            $lines[] = '- Total review negatif: ' . $lowReviews->count() . ' review.';

            $productBreakdown = $lowReviews
                ->groupBy(function (Review $review): string {
                    return (string) ($review->product?->name ?: 'produk_tidak_teridentifikasi');
                })
                ->map(fn(Collection $group): int => $group->count())
                ->sortDesc()
                ->take(3);

            if (! $productBreakdown->isEmpty()) {
                $lines[] = '- Produk yang paling sering dapat ulasan negatif: ' . $this->formatDistribution($productBreakdown) . '.';
            }

            $reviewComments = $lowReviews
                ->pluck('comment')
                ->map(fn($comment): string => $this->normalizeText((string) $comment))
                ->filter(fn(string $comment): bool => $comment !== '')
                ->values()
                ->all();

            $reviewKeywords = $this->extractTopKeywords($reviewComments, 6);
            if (count($reviewKeywords) > 0) {
                $lines[] = '- Pola masalah produk yang sering muncul: ' . implode(', ', $reviewKeywords) . '.';
            }

            foreach ($lowReviews->take(3) as $index => $review) {
                $productName = (string) ($review->product?->name ?: 'Produk tanpa nama');
                $comment = $this->normalizeText((string) $review->comment);

                if ($comment === '') {
                    continue;
                }

                $lines[] = '- Contoh review negatif ' . ($index + 1) . ' (' . $productName . '): ' . Str::limit($comment, 140, '...');
            }
        }

        if (! $warrantyClaims->isEmpty()) {
            $lines[] = '';
            $lines[] = '### Sinyal masalah dari klaim garansi';
            $lines[] = '- Total klaim garansi dengan alasan jelas: ' . $warrantyClaims->count() . ' klaim.';

            $statusBreakdown = $warrantyClaims
                ->groupBy(fn(WarrantyClaim $claim): string => (string) ($claim->status ?: 'submitted'))
                ->map(fn(Collection $group): int => $group->count())
                ->sortDesc()
                ->take(4);

            if (! $statusBreakdown->isEmpty()) {
                $lines[] = '- Distribusi status klaim: ' . $this->formatDistribution($statusBreakdown) . '.';
            }

            $claimReasons = $warrantyClaims
                ->pluck('reason')
                ->map(fn($reason): string => $this->normalizeText((string) $reason))
                ->filter(fn(string $reason): bool => $reason !== '')
                ->values()
                ->all();

            $claimKeywords = $this->extractTopKeywords($claimReasons, 6);
            if (count($claimKeywords) > 0) {
                $lines[] = '- Pola kerusakan/keluhan garansi dominan: ' . implode(', ', $claimKeywords) . '.';
            }

            foreach ($warrantyClaims->take(3) as $index => $claim) {
                $productName = (string) ($claim->orderItem?->product_name ?: 'Produk tanpa nama');
                $reason = $this->normalizeText((string) $claim->reason);

                if ($reason === '') {
                    continue;
                }

                $lines[] = '- Contoh alasan klaim ' . ($index + 1) . ' (' . $productName . '): ' . Str::limit($reason, 140, '...');
            }
        }

        $lines[] = '';
        $lines[] = '### Instruksi sikap AI terhadap data di atas';
        $lines[] = '- Pakai pola keluhan ini untuk meningkatkan empati dan ketepatan diagnosis masalah user.';
        $lines[] = '- Jangan mengarang data baru. Gunakan data ini sebagai konteks, lalu verifikasi detail transaksi ke tools/order jika dibutuhkan.';
        $lines[] = '- Untuk masalah sensitif (pembayaran, pengiriman, barang rusak), beri langkah mandiri singkat dulu sebelum eskalasi ke WhatsApp admin.';

        return implode("\n", $lines);
    }

    private function formatDistribution(Collection $distribution): string
    {
        return $distribution
            ->map(fn(int $count, string $key): string => $key . ' (' . $count . ')')
            ->values()
            ->implode(', ');
    }

    private function normalizeText(string $text): string
    {
        $stripped = strip_tags($text);
        $normalized = preg_replace('/\s+/', ' ', $stripped);

        return trim((string) $normalized);
    }

    /**
     * @param array<int, string> $texts
     * @return array<int, string>
     */
    private function extractTopKeywords(array $texts, int $limit = 6): array
    {
        $stopWords = [
            'yang',
            'untuk',
            'dengan',
            'karena',
            'sudah',
            'belum',
            'saya',
            'aku',
            'kamu',
            'kak',
            'admin',
            'agar',
            'atau',
            'dan',
            'dari',
            'pada',
            'jadi',
            'supaya',
            'dalam',
            'saat',
            'ini',
            'itu',
            'tidak',
            'gak',
            'ga',
            'nggak',
            'bisa',
            'banget',
            'masih',
            'kalo',
            'kalau',
            'lagi',
            'sih',
            'produk',
            'barang',
            'pesanan',
            'order',
            'tolong',
            'mohon',
            'minta',
            'sama',
            'lebih',
            'kurang',
            'udah',
            'sangat',
            'sekali',
            'aja',
            'juga',
            'nya',
            'nih',
            'dong',
            'deh',
        ];

        $frequency = [];

        foreach ($texts as $text) {
            $normalized = strtolower($this->normalizeText($text));
            $tokenized = preg_replace('/[^a-z0-9\s]/', ' ', $normalized);
            $tokens = preg_split('/\s+/', trim((string) $tokenized)) ?: [];

            foreach ($tokens as $token) {
                if ($token === '' || strlen($token) < 4) {
                    continue;
                }

                if (in_array($token, $stopWords, true)) {
                    continue;
                }

                $frequency[$token] = ($frequency[$token] ?? 0) + 1;
            }
        }

        if (count($frequency) === 0) {
            return [];
        }

        arsort($frequency);

        return array_slice(array_keys($frequency), 0, max(1, $limit));
    }
}
