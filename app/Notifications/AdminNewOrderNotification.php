<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminNewOrderNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $orderedItems = $this->order->items()
            ->orderBy('id')
            ->get(['product_name', 'quantity']);

        $customerName = trim((string) $this->order->customer_name);
        if ($customerName === '') {
            $customerName = 'Pelanggan';
        }

        $productSummary = 'produk kelistrikan';
        if ($orderedItems->isNotEmpty()) {
            $firstProductName = trim((string) $orderedItems->first()->product_name);
            $firstProductName = $firstProductName !== '' ? $firstProductName : 'produk tanpa nama';

            $totalDistinctProducts = $orderedItems->count();
            $totalQuantity = max((int) $orderedItems->sum('quantity'), 1);

            $productSummary = $totalDistinctProducts > 1
                ? sprintf('%s + %d produk lain (%d item)', $firstProductName, $totalDistinctProducts - 1, $totalQuantity)
                : sprintf('%s (%d item)', $firstProductName, $totalQuantity);
        }

        return [
            'title' => 'Pesanan baru masuk',
            'message' => sprintf(
                '%s memesan %s. Order %s menunggu verifikasi admin.',
                $customerName,
                $productSummary,
                $this->order->order_code
            ),
            'order_id' => $this->order->id,
            'order_code' => $this->order->order_code,
            'product_summary' => $productSummary,
            'route' => route('admin.orders.show', $this->order),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
