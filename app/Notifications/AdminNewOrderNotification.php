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
        return [
            'title' => 'Pesanan baru masuk',
            'message' => sprintf(
                'Pesanan %s dari %s menunggu verifikasi admin.',
                $this->order->order_code,
                $this->order->customer_name
            ),
            'order_id' => $this->order->id,
            'order_code' => $this->order->order_code,
            'route' => route('admin.orders.show', $this->order),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
