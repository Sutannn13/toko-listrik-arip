<?php

namespace App\Services\Ai\Tools;

use App\Models\Setting;

class FaqAnswerTool
{
    public function answer(string $question): array
    {
        $normalizedQuestion = strtolower(trim($question));

        if ($normalizedQuestion === '') {
            return [
                'answer' => 'Silakan kirim pertanyaan Anda. Saya bisa bantu FAQ toko, cek status pesanan, atau rekomendasi produk.',
                'source_key' => 'faq.generic.empty',
                'confidence' => 0.5,
                'suggestions' => [
                    'Cara cek status pesanan',
                    'Metode pembayaran yang tersedia',
                    'Rekomendasi produk sesuai budget',
                ],
            ];
        }

        if (str_contains($normalizedQuestion, 'ongkir') || str_contains($normalizedQuestion, 'shipping')) {
            $shippingCost = $this->resolveShippingCostPerItem();

            return [
                'answer' => 'Ongkir dihitung per item. Tarif saat ini Rp ' . number_format($shippingCost, 0, ',', '.') . ' per item.',
                'source_key' => 'faq.shipping.cost_per_item',
                'confidence' => 0.93,
                'suggestions' => [
                    'Bagaimana cara checkout?',
                    'Metode pembayaran Bayar.gg',
                    'Cara cek pesanan',
                ],
            ];
        }

        if (str_contains($normalizedQuestion, 'garansi')) {
            return [
                'answer' => 'Produk elektronik memiliki garansi klaim hingga 7 hari sesuai ketentuan toko. Klaim bisa diajukan dari menu Garansi/Klaim Garansi.',
                'source_key' => 'faq.warranty.electronic_7_days',
                'confidence' => 0.9,
                'suggestions' => [
                    'Cara submit klaim garansi',
                    'Syarat bukti klaim',
                    'Status klaim garansi',
                ],
            ];
        }

        if (
            str_contains($normalizedQuestion, 'bayar')
            || str_contains($normalizedQuestion, 'pembayaran')
            || str_contains($normalizedQuestion, 'qris')
            || str_contains($normalizedQuestion, 'bayargg')
        ) {
            return [
                'answer' => 'Metode pembayaran tersedia: COD, transfer bank, e-wallet, dan Bayar.gg (otomatis). Untuk Bayar.gg, Anda cukup klik link pembayaran tanpa upload bukti manual.',
                'source_key' => 'faq.payment.methods',
                'confidence' => 0.9,
                'suggestions' => [
                    'Cara cek status pembayaran',
                    'Upload bukti transfer',
                    'Bayar.gg tidak bisa dibuka',
                ],
            ];
        }

        if (str_contains($normalizedQuestion, 'resi') || str_contains($normalizedQuestion, 'lacak')) {
            return [
                'answer' => 'Untuk cek status pesanan atau nomor resi, kirim kode order Anda dengan format ORD-ARIP-YYYYMMDD-XXXXXX.',
                'source_key' => 'faq.order.tracking',
                'confidence' => 0.88,
                'suggestions' => [
                    'Cek status pesanan saya',
                    'Nomor resi saya',
                ],
            ];
        }

        return [
            'answer' => 'Saya siap bantu. Anda bisa tanya tentang FAQ toko, status pesanan, atau minta rekomendasi produk sesuai kebutuhan.',
            'source_key' => 'faq.generic.default',
            'confidence' => 0.65,
            'suggestions' => [
                'Status pesanan saya',
                'Rekomendasi produk untuk rumah',
                'Metode pembayaran',
            ],
        ];
    }

    private function resolveShippingCostPerItem(): int
    {
        $rawValue = (string) Setting::get('shipping_cost_per_item', '5000');
        $normalizedValue = preg_replace('/[^0-9]/', '', $rawValue);

        if ($normalizedValue === null || $normalizedValue === '') {
            return 5000;
        }

        return max(0, (int) $normalizedValue);
    }
}
