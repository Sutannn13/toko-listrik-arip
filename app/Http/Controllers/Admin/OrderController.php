<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:pending,processing,shipped,completed,cancelled'],
            'payment_status' => ['nullable', 'in:pending,paid,failed,refunded'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $orders = Order::query()
            ->with(['user:id,name,email', 'payments'])
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
        $validated = $request->validate([
            'status' => ['required', 'in:pending,processing,shipped,completed,cancelled'],
            'payment_status' => ['required', 'in:pending,paid,failed,refunded'],
            'tracking_number' => ['nullable', 'string', 'max:50'],
        ]);

        $oldStatus = $order->status;
        $order->status = $validated['status'];
        $order->payment_status = $validated['payment_status'];
        
        if (isset($validated['tracking_number'])) {
            $order->tracking_number = $validated['tracking_number'];
        }

        if ($order->status === 'completed' && (!$order->completed_at)) {
            $order->completed_at = now();
            $order->warranty_status = 'active';
        }

        if ($order->status === 'cancelled') {
            $order->warranty_status = 'void';
        }

        // 1. TAMBAL BUG PENGEMBALIAN STOK (Prioritas 1)
        if ($oldStatus !== 'cancelled' && $order->status === 'cancelled') {
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock', $item->quantity);
                }
            }
        }

        if ($order->payment_status === 'paid' && !$order->paid_at) {
            $order->paid_at = now();
        }

        $order->save();

        $latestPayment = $order->payments()->latest()->first();

        if ($latestPayment) {
            $latestPayment->update([
                'status' => $validated['payment_status'],
                'paid_at' => $validated['payment_status'] === 'paid' ? ($latestPayment->paid_at ?: now()) : null,
                'notes' => 'Status diperbarui oleh admin.',
            ]);
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Status pesanan berhasil diperbarui.');
    }
}
