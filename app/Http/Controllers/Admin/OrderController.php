<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Notifications\OrderCompletedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
            ->with(['user:id,name,email', 'payments', 'latestPayment'])
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

            if ($oldStatus !== 'cancelled' && $lockedOrder->status === 'cancelled') {
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
            } else {
                $lockedOrder->paid_at = null;
            }

            $lockedOrder->save();

            $latestPayment = $lockedOrder->payments()
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($latestPayment) {
                $latestPayment->update([
                    'status' => $validated['payment_status'],
                    'paid_at' => $validated['payment_status'] === 'paid' ? ($latestPayment->paid_at ?: now()) : null,
                    'notes' => 'Status diperbarui oleh admin.',
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
        $maxWarrantyDate = $warrantyStart->copy()->addDays(7)->endOfDay();
        $requestedWarrantyExpiry = Carbon::parse($validated['warranty_expires_at'])->endOfDay();

        if ($requestedWarrantyExpiry->lt($minWarrantyDate) || $requestedWarrantyExpiry->gt($maxWarrantyDate)) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Tanggal garansi harus di rentang 1 sampai maksimal 7 hari dari tanggal pesanan.');
        }

        try {
            DB::transaction(function () use ($order, $orderItem, $warrantyStart, $requestedWarrantyExpiry) {
                $lockedOrderItem = OrderItem::query()
                    ->with('product:id,is_electronic')
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

            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Item pesanan tidak ditemukan untuk pembaruan garansi.');
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Tanggal garansi item berhasil diperbarui.');
    }
}
