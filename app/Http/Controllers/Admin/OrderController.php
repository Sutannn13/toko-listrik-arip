<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Notifications\OrderCompletedNotification;
use App\Notifications\PaymentProofStatusUpdatedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:pending,processing,shipped,completed,cancelled'],
            'payment_status' => ['nullable', 'in:pending,paid,failed,refunded'],
            'proof' => ['nullable', 'in:all,uploaded,missing'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $orders = Order::query()
            ->with([
                'user:id,name,email',
                'address:id,label,recipient_name,address_line,city,province,postal_code',
                'payments',
                'latestPayment',
            ])
            ->when($filters['q'] ?? null, function ($query, $keyword) {
                $query->where(function ($searchQuery) use ($keyword) {
                    $searchQuery
                        ->where('order_code', 'like', '%' . $keyword . '%')
                        ->orWhere('customer_name', 'like', '%' . $keyword . '%')
                        ->orWhere('customer_email', 'like', '%' . $keyword . '%')
                        ->orWhereHas('payments', fn($paymentQuery) => $paymentQuery->where('payment_code', 'like', '%' . $keyword . '%'));
                });
            })
            ->when($filters['status'] ?? null, fn($query, $status) => $query->where('status', $status))
            ->when($filters['payment_status'] ?? null, fn($query, $paymentStatus) => $query->where('payment_status', $paymentStatus))
            ->when(($filters['proof'] ?? null) === 'uploaded', function ($query) {
                $query->whereHas('latestPayment', fn($paymentQuery) => $paymentQuery
                    ->whereNotNull('proof_url')
                    ->where('proof_url', '!=', ''));
            })
            ->when(($filters['proof'] ?? null) === 'missing', function ($query) {
                $query->where(function ($proofQuery) {
                    $proofQuery
                        ->whereDoesntHave('latestPayment')
                        ->orWhereHas('latestPayment', fn($paymentQuery) => $paymentQuery
                            ->where(function ($missingProofQuery) {
                                $missingProofQuery
                                    ->whereNull('proof_url')
                                    ->orWhere('proof_url', '');
                            }));
                });
            })
            ->when($filters['date_from'] ?? null, fn($query, $dateFrom) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn($query, $dateTo) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => $filters,
        ]);
    }

    public function show(Order $order): View
    {
        $order->load([
            'user:id,name,email',
            'address',
            'items.warrantyClaims.user:id,name,email',
            'items.product:id,is_electronic,warranty_days',
            'payments',
            'warrantyClaims.user:id,name,email',
        ]);

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $previousStatus = $order->status;

        $validated = $request->validate([
            'status' => ['required', 'in:pending,processing,shipped,completed,cancelled'],
            'payment_status' => ['required', 'in:pending,paid,failed,refunded'],
            'tracking_number' => ['nullable', 'string', 'max:50', 'required_if:status,shipped'],
        ]);

        if ($order->status === 'cancelled' && $validated['status'] !== 'cancelled') {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Pesanan yang sudah cancelled tidak dapat diaktifkan kembali.');
        }

        if ($validated['status'] === 'completed' && $validated['payment_status'] !== 'paid') {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Status completed hanya boleh untuk pesanan yang sudah paid.');
        }

        if ($validated['status'] === 'cancelled' && $validated['payment_status'] === 'paid') {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Pesanan cancelled tidak boleh memiliki payment status paid.');
        }

        if ($order->status === 'completed' && $validated['status'] !== 'completed') {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Pesanan yang sudah completed tidak dapat diturunkan statusnya.');
        }

        if ($validated['payment_status'] === 'paid') {
            $latestPayment = $order->payments()->latest('id')->first();

            if (!$latestPayment) {
                return redirect()->route('admin.orders.show', $order)
                    ->with('error', 'Data pembayaran terbaru tidak ditemukan. Periksa data order sebelum menandai paid.');
            }

            if ($latestPayment && in_array($latestPayment->method, ['bank_transfer', 'ewallet', 'dummy'], true) && blank($latestPayment->proof_url)) {
                return redirect()->route('admin.orders.show', $order)
                    ->with('error', 'Pembayaran transfer/e-wallet hanya bisa diubah ke paid setelah pelanggan upload bukti dan diverifikasi admin.');
            }
        }

        if ($validated['payment_status'] === 'refunded') {
            $latestPayment = $order->payments()->latest('id')->first();

            if (! $latestPayment) {
                return redirect()->route('admin.orders.show', $order)
                    ->with('error', 'Data pembayaran terbaru tidak ditemukan. Tidak bisa memproses refunded.');
            }

            $hasPendingRefundRequest = Str::contains((string) ($latestPayment->notes ?? ''), '[REFUND_REQUEST_PENDING]');
            if (! $hasPendingRefundRequest) {
                return redirect()->route('admin.orders.show', $order)
                    ->with('error', 'Status refunded hanya boleh diproses jika pelanggan sudah mengajukan refund.');
            }

            if ($order->payment_status !== 'paid' || $latestPayment->status !== 'paid') {
                return redirect()->route('admin.orders.show', $order)
                    ->with('error', 'Status refunded hanya boleh untuk pesanan yang sudah dibayar lunas.');
            }
        }

        $transitionBlocked = false;

        DB::transaction(function () use ($order, $validated, &$transitionBlocked) {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedOrder) {
                return;
            }

            if ($lockedOrder->status === 'cancelled' && $validated['status'] !== 'cancelled') {
                $transitionBlocked = true;
                return;
            }

            $oldStatus = $lockedOrder->status;
            $lockedOrder->status = $validated['status'];
            $lockedOrder->payment_status = $validated['payment_status'];
            $isNowCompleted = $oldStatus !== 'completed' && $lockedOrder->status === 'completed';
            $isNowCancelled = $oldStatus !== 'cancelled' && $lockedOrder->status === 'cancelled';

            if (array_key_exists('tracking_number', $validated)) {
                $lockedOrder->tracking_number = $validated['tracking_number'] !== ''
                    ? $validated['tracking_number']
                    : null;
            }

            if ($lockedOrder->status === 'completed' && !$lockedOrder->completed_at) {
                $lockedOrder->completed_at = now();
                $lockedOrder->warranty_status = 'active';
            }

            if ($lockedOrder->status === 'cancelled') {
                $lockedOrder->warranty_status = 'void';
            }

            if ($isNowCancelled) {
                $lockedOrder->load('items');

                foreach ($lockedOrder->items as $item) {
                    if (!$item->product_id) {
                        continue;
                    }

                    $lockedProduct = $item->product()->lockForUpdate()->first();
                    if ($lockedProduct) {
                        $lockedProduct->increment('stock', $item->quantity);
                    }
                }
            }

            if ($lockedOrder->payment_status === 'paid') {
                $lockedOrder->paid_at = $lockedOrder->paid_at ?: now();
            } elseif ($lockedOrder->payment_status === 'refunded') {
                $lockedOrder->paid_at = $lockedOrder->paid_at ?: now();
            } else {
                $lockedOrder->paid_at = null;
            }

            $lockedOrder->save();

            if ($isNowCompleted) {
                $warrantyStart = ($lockedOrder->completed_at ?? now())->copy()->startOfDay();

                $lockedOrder->items()
                    ->where('warranty_days', '>', 0)
                    ->lockForUpdate()
                    ->get()
                    ->each(function (OrderItem $item) use ($warrantyStart) {
                        $days = max(1, (int) $item->warranty_days);
                        $item->warranty_expires_at = $warrantyStart->copy()->addDays($days)->endOfDay();
                        $item->save();
                    });
            }

            if ($isNowCancelled) {
                $lockedOrder->items()
                    ->where('warranty_days', '>', 0)
                    ->whereNotNull('warranty_expires_at')
                    ->lockForUpdate()
                    ->update(['warranty_expires_at' => null]);
            }

            $latestPayment = $lockedOrder->payments()
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($latestPayment) {
                $latestPayment->update([
                    'status' => $validated['payment_status'],
                    'paid_at' => match ($validated['payment_status']) {
                        'paid' => $latestPayment->paid_at ?: now(),
                        'refunded' => $latestPayment->paid_at ?: ($lockedOrder->paid_at ?: now()),
                        default => null,
                    },
                    'notes' => $this->buildPaymentStatusUpdateNote(
                        (string) ($latestPayment->notes ?? ''),
                        $validated['payment_status'],
                    ),
                ]);
            }
        }, 3);

        if ($transitionBlocked) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Pesanan sudah dibatalkan oleh proses lain dan tidak dapat diaktifkan kembali.');
        }

        $order->refresh();
        if ($previousStatus !== 'completed' && $order->status === 'completed' && $order->user) {
            $order->user->notify(new OrderCompletedNotification($order));
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Status pesanan berhasil diperbarui.');
    }

    public function approvePaymentProof(Request $request, Order $order, Payment $payment): RedirectResponse
    {
        if ($payment->order_id !== $order->id) {
            abort(404);
        }

        if (!$this->isLatestOrderPayment($order, $payment)) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Verifikasi hanya dapat dilakukan untuk pembayaran terbaru pada pesanan ini.');
        }

        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (blank($payment->proof_url)) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Bukti pembayaran belum diunggah oleh pelanggan.');
        }

        if (in_array($payment->status, ['paid', 'refunded'], true)) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Status pembayaran ini sudah final dan tidak bisa diverifikasi ulang.');
        }

        if ($order->status === 'cancelled') {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Pesanan yang sudah cancelled tidak dapat diubah menjadi paid.');
        }

        try {
            DB::transaction(function () use ($order, $payment, $validated) {
                $lockedOrder = Order::query()
                    ->whereKey($order->id)
                    ->lockForUpdate()
                    ->first();

                $lockedPayment = Payment::query()
                    ->whereKey($payment->id)
                    ->where('order_id', $order->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedOrder || !$lockedPayment) {
                    throw new \RuntimeException('NOT_FOUND');
                }

                $latestPaymentId = (int) $lockedOrder->payments()->latest('id')->value('id');
                if ($latestPaymentId !== (int) $lockedPayment->id) {
                    throw new \RuntimeException('NOT_LATEST_PAYMENT');
                }

                if (blank($lockedPayment->proof_url)) {
                    throw new \RuntimeException('NO_PROOF');
                }

                if (in_array($lockedPayment->status, ['paid', 'refunded'], true)) {
                    throw new \RuntimeException('FINAL_STATUS');
                }

                if ($lockedOrder->status === 'cancelled') {
                    throw new \RuntimeException('ORDER_CANCELLED');
                }

                $paidAt = $lockedPayment->paid_at ?: now();

                $lockedPayment->update([
                    'status' => 'paid',
                    'paid_at' => $paidAt,
                    'notes' => $this->buildPaymentReviewNote('approved', $validated['admin_notes'] ?? null),
                ]);

                $lockedOrder->payment_status = 'paid';
                $lockedOrder->paid_at = $paidAt;
                $lockedOrder->save();
            }, 3);
        } catch (\RuntimeException $e) {
            $message = match ($e->getMessage()) {
                'NO_PROOF' => 'Bukti pembayaran tidak ditemukan untuk diverifikasi.',
                'FINAL_STATUS' => 'Status pembayaran ini sudah final dan tidak bisa diverifikasi ulang.',
                'ORDER_CANCELLED' => 'Pesanan yang sudah cancelled tidak dapat diubah menjadi paid.',
                'NOT_LATEST_PAYMENT' => 'Pembayaran yang Anda verifikasi bukan pembayaran terbaru pada pesanan ini.',
                default => 'Data pesanan/pembayaran tidak ditemukan saat proses verifikasi.',
            };

            return redirect()->route('admin.orders.show', $order)
                ->with('error', $message);
        }

        $order->refresh();
        $payment->refresh();

        if ($order->user) {
            $order->user->notify(new PaymentProofStatusUpdatedNotification(
                $order,
                $payment,
                'approved',
                $validated['admin_notes'] ?? null,
            ));
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Bukti pembayaran berhasil disetujui.');
    }

    public function rejectPaymentProof(Request $request, Order $order, Payment $payment): RedirectResponse
    {
        if ($payment->order_id !== $order->id) {
            abort(404);
        }

        if (!$this->isLatestOrderPayment($order, $payment)) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Penolakan hanya dapat dilakukan untuk pembayaran terbaru pada pesanan ini.');
        }

        $validated = $request->validate([
            'admin_notes' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        if (blank($payment->proof_url)) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Bukti pembayaran belum diunggah oleh pelanggan.');
        }

        if (in_array($payment->status, ['paid', 'refunded'], true)) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Status pembayaran ini sudah final dan tidak bisa ditolak.');
        }

        if ($order->status === 'completed') {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Pesanan yang sudah completed tidak dapat diubah menjadi failed.');
        }

        try {
            DB::transaction(function () use ($order, $payment, $validated) {
                $lockedOrder = Order::query()
                    ->whereKey($order->id)
                    ->lockForUpdate()
                    ->first();

                $lockedPayment = Payment::query()
                    ->whereKey($payment->id)
                    ->where('order_id', $order->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedOrder || !$lockedPayment) {
                    throw new \RuntimeException('NOT_FOUND');
                }

                $latestPaymentId = (int) $lockedOrder->payments()->latest('id')->value('id');
                if ($latestPaymentId !== (int) $lockedPayment->id) {
                    throw new \RuntimeException('NOT_LATEST_PAYMENT');
                }

                if (blank($lockedPayment->proof_url)) {
                    throw new \RuntimeException('NO_PROOF');
                }

                if (in_array($lockedPayment->status, ['paid', 'refunded'], true)) {
                    throw new \RuntimeException('FINAL_STATUS');
                }

                if ($lockedOrder->status === 'completed') {
                    throw new \RuntimeException('ORDER_COMPLETED');
                }

                $lockedPayment->update([
                    'status' => 'failed',
                    'paid_at' => null,
                    'notes' => $this->buildPaymentReviewNote('rejected', $validated['admin_notes']),
                ]);

                $lockedOrder->payment_status = 'failed';
                $lockedOrder->paid_at = null;
                $lockedOrder->save();
            }, 3);
        } catch (\RuntimeException $e) {
            $message = match ($e->getMessage()) {
                'NO_PROOF' => 'Bukti pembayaran tidak ditemukan untuk ditolak.',
                'FINAL_STATUS' => 'Status pembayaran ini sudah final dan tidak bisa ditolak.',
                'ORDER_COMPLETED' => 'Pesanan yang sudah completed tidak dapat diubah menjadi failed.',
                'NOT_LATEST_PAYMENT' => 'Pembayaran yang Anda tolak bukan pembayaran terbaru pada pesanan ini.',
                default => 'Data pesanan/pembayaran tidak ditemukan saat proses penolakan.',
            };

            return redirect()->route('admin.orders.show', $order)
                ->with('error', $message);
        }

        $order->refresh();
        $payment->refresh();

        if ($order->user) {
            $order->user->notify(new PaymentProofStatusUpdatedNotification(
                $order,
                $payment,
                'rejected',
                $validated['admin_notes'],
            ));
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Bukti pembayaran ditolak. Pelanggan dapat mengunggah bukti baru.');
    }

    public function updateItemWarranty(Request $request, Order $order, OrderItem $orderItem): RedirectResponse
    {
        if ($orderItem->order_id !== $order->id) {
            abort(404);
        }

        $validated = $request->validate([
            'warranty_expires_at' => ['required', 'date'],
        ]);

        $warrantyStart = ($order->completed_at ?? $order->placed_at ?? $order->created_at ?? now())
            ->copy()
            ->startOfDay();

        $minWarrantyDate = $warrantyStart->copy()->addDay()->startOfDay();
        $maxWarrantyDate = $warrantyStart->copy()->addDays(365)->endOfDay();
        $requestedWarrantyExpiry = Carbon::parse($validated['warranty_expires_at'])->endOfDay();

        if ($requestedWarrantyExpiry->lt($minWarrantyDate) || $requestedWarrantyExpiry->gt($maxWarrantyDate)) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Tanggal garansi harus di rentang 1 sampai maksimal 365 hari dari tanggal pesanan.');
        }

        try {
            DB::transaction(function () use ($order, $orderItem, $warrantyStart, $requestedWarrantyExpiry) {
                $lockedOrderItem = OrderItem::query()
                    ->with('product:id,is_electronic,warranty_days')
                    ->whereKey($orderItem->id)
                    ->where('order_id', $order->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedOrderItem) {
                    throw new \RuntimeException('ORDER_ITEM_NOT_FOUND');
                }

                $isElectronicProduct = (bool) ($lockedOrderItem->product?->is_electronic);
                $isWarrantyEligible = $isElectronicProduct || (int) $lockedOrderItem->warranty_days > 0;
                if (!$isWarrantyEligible) {
                    throw new \RuntimeException('NON_ELECTRONIC_PRODUCT');
                }

                $warrantyDays = (int) $warrantyStart->diffInDays($requestedWarrantyExpiry->copy()->startOfDay());
                $maxWarrantyDays = max(1, min(365, (int) ($lockedOrderItem->product?->warranty_days_for_claim ?: $lockedOrderItem->warranty_days)));

                if ($warrantyDays > $maxWarrantyDays) {
                    throw new \RuntimeException('WARRANTY_EXCEEDS_PRODUCT_MAX_' . $maxWarrantyDays);
                }

                $lockedOrderItem->update([
                    'warranty_days' => $warrantyDays,
                    'warranty_expires_at' => $requestedWarrantyExpiry,
                ]);
            }, 3);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'NON_ELECTRONIC_PRODUCT') {
                return redirect()->route('admin.orders.show', $order)
                    ->with('error', 'Produk non-elektronik tidak dapat diberi garansi klaim.');
            }

            if (str_starts_with($e->getMessage(), 'WARRANTY_EXCEEDS_PRODUCT_MAX_')) {
                $maxDays = (int) str_replace('WARRANTY_EXCEEDS_PRODUCT_MAX_', '', $e->getMessage());

                return redirect()->route('admin.orders.show', $order)
                    ->with('error', 'Tanggal garansi melebihi batas produk ini (maksimal ' . $maxDays . ' hari).');
            }

            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Item pesanan tidak ditemukan untuk pembaruan garansi.');
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Tanggal garansi item berhasil diperbarui.');
    }

    private function buildPaymentReviewNote(string $decision, ?string $adminNotes = null): string
    {
        $baseNote = $decision === 'approved'
            ? 'Bukti pembayaran diverifikasi admin dan dinyatakan valid.'
            : 'Bukti pembayaran ditolak admin. Silakan unggah bukti pembayaran yang valid.';

        $adminNotes = trim((string) $adminNotes);
        if ($adminNotes !== '') {
            return $baseNote . ' Catatan admin: ' . $adminNotes;
        }

        return $baseNote;
    }

    private function buildPaymentStatusUpdateNote(string $existingNotes, string $paymentStatus): string
    {
        $statusNote = match ($paymentStatus) {
            'paid' => 'Status pembayaran ditandai paid oleh admin.',
            'failed' => 'Status pembayaran ditandai failed oleh admin.',
            'refunded' => 'Status pembayaran ditandai refunded oleh admin.',
            default => 'Status pembayaran diperbarui oleh admin.',
        };

        $existingNotes = trim($existingNotes);
        if ($existingNotes === '') {
            return $statusNote;
        }

        if (Str::contains($existingNotes, $statusNote)) {
            return $existingNotes;
        }

        return $existingNotes . ' | ' . $statusNote;
    }

    private function isLatestOrderPayment(Order $order, Payment $payment): bool
    {
        $latestPaymentId = (int) $order->payments()->latest('id')->value('id');

        return $latestPaymentId > 0 && $latestPaymentId === (int) $payment->id;
    }
}
