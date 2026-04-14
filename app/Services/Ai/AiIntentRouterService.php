<?php

namespace App\Services\Ai;

class AiIntentRouterService
{
    public function resolveIntent(string $message): string
    {
        $normalizedMessage = strtolower(trim($message));

        if ($normalizedMessage === '') {
            return 'faq';
        }

        if ($this->containsOrderTrackingHint($normalizedMessage)) {
            return 'order_tracking';
        }

        if ($this->containsRecommendationHint($normalizedMessage)) {
            return 'product_recommendation';
        }

        return 'faq';
    }

    private function containsOrderTrackingHint(string $message): bool
    {
        if (preg_match('/ord-arip-\\d{8}-[a-z0-9]{6}/i', $message)) {
            return true;
        }

        $trackingKeywords = [
            'tracking',
            'cek pesanan',
            'status pesanan',
            'lacak pesanan',
            'nomor resi',
            'order saya',
            'status order',
        ];

        foreach ($trackingKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function containsRecommendationHint(string $message): bool
    {
        $recommendationKeywords = [
            'rekomendasi',
            'saran produk',
            'produk apa',
            'cari produk',
            'budget',
            'murah',
            'stok',
        ];

        foreach ($recommendationKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
