<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderCompletedNotification extends Notification
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
            'title' => 'Pesanan selesai',
            'message' => sprintf(
                'Pesanan %s sudah selesai. Cek di riwayat transaksi Anda.',
                $this->order->order_code
            ),
            'order_id' => $this->order->id,
            'order_code' => $this->order->order_code,
            'route' => route('home.transactions'),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
