<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\BayarGgGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BayarGgWebhookController extends Controller
{
    public function __construct(
        private readonly BayarGgGatewayService $bayarGgGatewayService,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        if (! is_array($payload)) {
            return response()->json([
                'success' => false,
                'message' => 'Payload callback tidak valid.',
            ], 400);
        }

        $signature = (string) $request->header('X-Webhook-Signature', '');
        $timestamp = (string) $request->header('X-Webhook-Timestamp', '');

        if (! $this->bayarGgGatewayService->verifyWebhookSignature($payload, $signature, $timestamp)) {
            return response()->json([
                'success' => false,
                'message' => 'Signature callback tidak valid.',
            ], 401);
        }

        $invoiceId = trim((string) ($payload['invoice_id'] ?? ''));
        if ($invoiceId === '') {
            return response()->json([
                'success' => false,
                'message' => 'invoice_id wajib diisi.',
            ], 422);
        }

        $gatewayStatus = $this->bayarGgGatewayService->normalizeGatewayStatus((string) ($payload['status'] ?? 'pending'));

        $payment = Payment::query()
            ->where('gateway_provider', 'bayargg')
            ->where('gateway_invoice_id', $invoiceId)
            ->first();

        // Balas 200 agar provider tidak melakukan retry tanpa henti.
        if (! $payment) {
            return response()->json([
                'success' => true,
                'message' => 'Invoice tidak ditemukan. Callback diabaikan.',
            ]);
        }

        DB::transaction(function () use ($payment, $payload, $gatewayStatus) {
            $lockedPayment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedPayment) {
                return;
            }

            $lockedOrder = Order::query()
                ->whereKey($lockedPayment->order_id)
                ->lockForUpdate()
                ->first();

            if (! $lockedOrder) {
                return;
            }

            $lockedPayment->update([
                'gateway_status' => $gatewayStatus,
                'gateway_payload' => $payload,
                'gateway_paid_reference' => $payload['paid_reff_num'] ?? $lockedPayment->gateway_paid_reference,
            ]);

            if ($gatewayStatus === 'paid' && $lockedPayment->status !== 'paid') {
                $paidAt = ! empty($payload['paid_at']) ? $payload['paid_at'] : now();
                $isCancelledOrder = $lockedOrder->status === 'cancelled';

                $lockedPayment->update([
                    'status' => 'paid',
                    'paid_at' => $paidAt,
                    'notes' => $isCancelledOrder
                        ? 'Pembayaran Bayar.gg diterima setelah pesanan dibatalkan. Perlu review admin untuk tindak lanjut/refund.'
                        : 'Pembayaran terkonfirmasi otomatis via Bayar.gg webhook.',
                ]);

                Payment::query()
                    ->where('order_id', $lockedOrder->id)
                    ->where('method', 'bayargg')
                    ->where('id', '!=', $lockedPayment->id)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->get()
                    ->each(function (Payment $pendingPayment): void {
                        $pendingPayment->update([
                            'status' => 'failed',
                            'paid_at' => null,
                            'notes' => 'Dibatalkan otomatis karena invoice Bayar.gg lain pada pesanan yang sama sudah lunas.',
                        ]);
                    });

                if (! $isCancelledOrder) {
                    $lockedOrder->update([
                        'payment_status' => 'paid',
                        'paid_at' => $paidAt,
                    ]);
                }

                return;
            }

            if (in_array($gatewayStatus, ['expired', 'cancelled'], true) && $lockedPayment->status !== 'paid') {
                $lockedPayment->update([
                    'status' => 'failed',
                    'notes' => 'Pembayaran ' . $gatewayStatus . ' di Bayar.gg.',
                ]);

                $latestPaymentId = (int) $lockedOrder->payments()->latest('id')->value('id');
                $hasPaidPayment = $lockedOrder->payments()->where('status', 'paid')->exists();

                if (
                    $lockedOrder->status !== 'cancelled'
                    && $lockedOrder->payment_status !== 'paid'
                    && ! $hasPaidPayment
                    && $latestPaymentId === (int) $lockedPayment->id
                ) {
                    $lockedOrder->update([
                        'payment_status' => 'failed',
                        'paid_at' => null,
                    ]);
                }
            }
        }, 3);

        return response()->json([
            'success' => true,
            'message' => 'Callback diproses.',
        ]);
    }
}
