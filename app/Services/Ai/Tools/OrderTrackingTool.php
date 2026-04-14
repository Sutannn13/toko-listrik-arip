<?php

namespace App\Services\Ai\Tools;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;

class OrderTrackingTool
{
    public function lookup(array $payload, ?User $authenticatedUser): array
    {
        $message = (string) ($payload['message'] ?? '');

        $orderCode = $this->extractOrderCode((string) ($payload['order_code'] ?? ''), $message);
        if ($orderCode === null) {
            return [
                'ok' => false,
                'requires_verification' => true,
                'reply' => 'Agar saya bisa cek pesanan, kirim kode order Anda dengan format ORD-ARIP-YYYYMMDD-XXXXXX.',
                'suggestions' => [
                    'Contoh: ORD-ARIP-20260414-ABC123',
                    'Cek status pesanan saya',
                ],
                'order' => null,
            ];
        }

        $order = Order::query()
            ->with('payments')
            ->where('order_code', $orderCode)
            ->first();

        if (! $order) {
            return [
                'ok' => false,
                'requires_verification' => false,
                'reply' => 'Kode order tidak ditemukan. Mohon cek kembali format kode order Anda.',
                'suggestions' => [
                    'Periksa kode order dari riwayat transaksi',
                    'Coba kirim ulang kode order',
                ],
                'order' => null,
            ];
        }

        if (! $this->isAuthorizedToViewOrder($order, $payload, $message, $authenticatedUser)) {
            return [
                'ok' => false,
                'requires_verification' => true,
                'reply' => 'Untuk keamanan, sertakan email pemesan atau 4 digit akhir nomor telepon pemesan.',
                'suggestions' => [
                    'Format: ORD-... + email pemesan',
                    'Format: ORD-... + 4 digit akhir nomor HP',
                ],
                'order' => null,
            ];
        }

        $latestPayment = $order->payments->sortByDesc('id')->first();

        return [
            'ok' => true,
            'requires_verification' => false,
            'reply' => $this->buildTrackingReply($order, $latestPayment),
            'suggestions' => $this->buildSuggestions($order, $latestPayment),
            'order' => [
                'order_code' => $order->order_code,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'tracking_number' => $order->tracking_number,
                'latest_payment_method' => $latestPayment?->method,
                'latest_payment_url' => $latestPayment?->gateway_payment_url,
            ],
        ];
    }

    private function extractOrderCode(string $orderCodeFromPayload, string $message): ?string
    {
        $candidate = strtoupper(trim($orderCodeFromPayload));

        if ($candidate !== '' && preg_match('/^ORD-ARIP-\\d{8}-[A-Z0-9]{6}$/', $candidate) === 1) {
            return $candidate;
        }

        if (preg_match('/ORD-ARIP-\\d{8}-[A-Z0-9]{6}/i', strtoupper($message), $matches) === 1) {
            return strtoupper($matches[0]);
        }

        return null;
    }

    private function isAuthorizedToViewOrder(Order $order, array $payload, string $message, ?User $authenticatedUser): bool
    {
        if ($authenticatedUser && (int) $order->user_id === (int) $authenticatedUser->id) {
            return true;
        }

        $emailFromPayload = strtolower(trim((string) ($payload['customer_email'] ?? '')));
        $emailFromMessage = $this->extractEmailFromMessage($message);

        $phoneLast4FromPayload = trim((string) ($payload['customer_phone_last4'] ?? ''));
        $phoneLast4FromMessage = $this->extractPhoneLast4FromMessage($message);

        $orderEmail = strtolower(trim((string) $order->customer_email));
        $orderPhoneLast4 = substr(preg_replace('/[^0-9]/', '', (string) $order->customer_phone) ?: '', -4);

        if ($emailFromPayload !== '' && $emailFromPayload === $orderEmail) {
            return true;
        }

        if ($emailFromMessage !== '' && $emailFromMessage === $orderEmail) {
            return true;
        }

        if ($phoneLast4FromPayload !== '' && $phoneLast4FromPayload === $orderPhoneLast4) {
            return true;
        }

        if ($phoneLast4FromMessage !== '' && $phoneLast4FromMessage === $orderPhoneLast4) {
            return true;
        }

        return false;
    }

    private function extractEmailFromMessage(string $message): string
    {
        if (preg_match('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $message, $matches) === 1) {
            return strtolower(trim($matches[0]));
        }

        return '';
    }

    private function extractPhoneLast4FromMessage(string $message): string
    {
        if (preg_match_all('/\b(\d{4})\b/', $message, $matches) === false) {
            return '';
        }

        if (empty($matches[1])) {
            return '';
        }

        return (string) end($matches[1]);
    }

    private function buildTrackingReply(Order $order, ?Payment $latestPayment): string
    {
        $statusLabel = $this->normalizeStatusLabel((string) $order->status);
        $paymentStatusLabel = $this->normalizePaymentStatusLabel((string) $order->payment_status);

        $reply = 'Pesanan ' . $order->order_code . ' saat ini berstatus ' . $statusLabel . ' dan pembayaran ' . $paymentStatusLabel . '.';

        if (! empty($order->tracking_number)) {
            $reply .= ' Nomor resi: ' . $order->tracking_number . '.';
        }

        if ($latestPayment && $latestPayment->method === 'bayargg' && $order->payment_status !== 'paid' && filled($latestPayment->gateway_payment_url)) {
            $reply .= ' Anda masih bisa melanjutkan pembayaran melalui link Bayar.gg.';
        }

        return $reply;
    }

    private function buildSuggestions(Order $order, ?Payment $latestPayment): array
    {
        $suggestions = [
            'Buka halaman tracking pesanan',
            'Cek metode pembayaran pesanan',
        ];

        if ($latestPayment && $latestPayment->method === 'bayargg' && $order->payment_status !== 'paid') {
            $suggestions[] = 'Lanjutkan pembayaran Bayar.gg';
        }

        return $suggestions;
    }

    private function normalizeStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'menunggu proses',
            'processing' => 'sedang diproses',
            'shipped' => 'sudah dikirim',
            'completed' => 'selesai',
            'cancelled' => 'dibatalkan',
            default => $status,
        };
    }

    private function normalizePaymentStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'menunggu pembayaran',
            'paid' => 'sudah dibayar',
            'failed' => 'gagal',
            'refunded' => 'dikembalikan',
            default => $status,
        };
    }
}
