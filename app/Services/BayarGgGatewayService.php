<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BayarGgGatewayService
{
    public function isConfigured(): bool
    {
        $apiKey = trim((string) config('services.bayargg.api_key'));
        $baseUrl = trim((string) config('services.bayargg.base_url'));

        return $apiKey !== '' && $baseUrl !== '';
    }

    public function createPayment(Order $order, Payment $payment): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Konfigurasi Bayar.gg belum lengkap.');
        }

        $baseUrl = rtrim((string) config('services.bayargg.base_url'), '/');
        $apiKey = (string) config('services.bayargg.api_key');
        $timeout = max(5, (int) config('services.bayargg.timeout', 15));

        $response = Http::baseUrl($baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout($timeout)
            ->withHeaders([
                'X-API-Key' => $apiKey,
            ])
            ->post('/create-payment.php', $this->buildCreatePaymentPayload($order, $payment));

        if (! $response->successful()) {
            throw new RuntimeException('Bayar.gg mengembalikan HTTP ' . $response->status() . '.');
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException('Response Bayar.gg tidak valid.');
        }

        if (($json['success'] ?? false) !== true) {
            $apiMessage = trim((string) ($json['message'] ?? ''));
            throw new RuntimeException($apiMessage !== ''
                ? 'Bayar.gg error: ' . $apiMessage
                : 'Bayar.gg menolak request create-payment.');
        }

        $gatewayData = is_array($json['data'] ?? null) ? $json['data'] : [];
        $paymentData = is_array($json['payment'] ?? null) ? $json['payment'] : [];

        $invoiceId = trim((string) ($gatewayData['invoice_id'] ?? $paymentData['invoice_id'] ?? $json['invoice_id'] ?? ''));
        $paymentUrl = trim((string) ($gatewayData['payment_url'] ?? $json['payment_url'] ?? $paymentData['payment_url'] ?? ''));
        $gatewayStatus = (string) ($gatewayData['status'] ?? $paymentData['status'] ?? 'pending');
        $gatewayExpiresAt = $gatewayData['expires_at'] ?? $paymentData['expires_at'] ?? null;

        if ($invoiceId === '' || $paymentUrl === '') {
            throw new RuntimeException('Response Bayar.gg tidak menyertakan invoice_id/payment_url.');
        }

        return [
            'invoice_id' => $invoiceId,
            'payment_url' => $paymentUrl,
            'gateway_status' => $this->normalizeGatewayStatus($gatewayStatus),
            'expires_at' => $this->parseGatewayDate($gatewayExpiresAt),
            'payload' => $json,
        ];
    }

    public function normalizeGatewayStatus(string $status): string
    {
        $normalizedStatus = strtolower(trim($status));

        return match ($normalizedStatus) {
            'paid' => 'paid',
            'expired' => 'expired',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }

    public function verifyWebhookSignature(array $payload, string $signature, string $timestamp): bool
    {
        $secret = trim((string) config('services.bayargg.webhook_secret'));

        if ($secret === '' || trim($signature) === '' || trim($timestamp) === '') {
            return false;
        }

        $parsedTimestamp = $this->parseWebhookTimestamp($timestamp);
        if ($parsedTimestamp === null) {
            return false;
        }

        $toleranceSeconds = max(30, (int) config('services.bayargg.webhook_tolerance_seconds', 300));
        if (abs(now()->timestamp - $parsedTimestamp) > $toleranceSeconds) {
            return false;
        }

        $invoiceId = trim((string) ($payload['invoice_id'] ?? ''));
        $status = strtolower(trim((string) ($payload['status'] ?? '')));
        $finalAmount = (string) ($payload['final_amount'] ?? '');

        if ($invoiceId === '' || $status === '' || $finalAmount === '') {
            return false;
        }

        // Format signature mengikuti dokumentasi Bayar.gg.
        $signatureData = $invoiceId . '|' . $status . '|' . $finalAmount . '|' . $timestamp;
        $expectedSignature = hash_hmac('sha256', $signatureData, $secret);

        if (! hash_equals($expectedSignature, trim($signature))) {
            return false;
        }

        // Tolak callback duplikat agar replay request tidak memproses event yang sama berulang kali.
        $replayTtlSeconds = max(
            $toleranceSeconds,
            (int) config('services.bayargg.webhook_replay_ttl_seconds', 600),
        );
        $replayCacheKey = 'bayargg:webhook:' . hash('sha256', $signatureData . '|' . trim($signature));

        try {
            return Cache::add($replayCacheKey, true, now()->addSeconds($replayTtlSeconds));
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }

    private function buildCreatePaymentPayload(Order $order, Payment $payment): array
    {
        $callbackUrl = trim((string) config('services.bayargg.callback_url', ''));
        $redirectUrl = trim((string) config('services.bayargg.redirect_url', ''));

        $payload = [
            'amount' => (int) $payment->amount,
            'description' => 'Pembayaran order ' . $order->order_code,
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'callback_url' => $callbackUrl !== ''
                ? $callbackUrl
                : route('api.webhooks.bayar-gg'),
            'redirect_url' => $redirectUrl !== ''
                ? $redirectUrl
                : route('home.tracking.show', ['orderCode' => $order->order_code]),
            'payment_method' => $this->resolveGatewayPaymentMethod(),
        ];

        if ((bool) config('services.bayargg.use_qris_converter', false)) {
            $payload['use_qris_converter'] = true;
        }

        return $payload;
    }

    private function resolveGatewayPaymentMethod(): string
    {
        $requestedMethod = strtolower(trim((string) config('services.bayargg.payment_method', 'qris')));

        return in_array($requestedMethod, ['qris', 'qris_user', 'gopay_qris', 'ovo'], true)
            ? $requestedMethod
            : 'qris';
    }

    private function parseGatewayDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseWebhookTimestamp(string $timestamp): ?int
    {
        $normalized = trim($timestamp);

        if ($normalized === '' || ! preg_match('/^[0-9]+$/', $normalized)) {
            return null;
        }

        $seconds = (int) $normalized;

        // Provider bisa mengirim milidetik; normalisasi ke detik.
        if (strlen($normalized) >= 13) {
            $seconds = (int) floor($seconds / 1000);
        }

        return $seconds > 0 ? $seconds : null;
    }
}
