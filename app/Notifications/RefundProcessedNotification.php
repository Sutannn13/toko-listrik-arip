<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RefundProcessedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly Payment $payment,
        private readonly string $status,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $isRefunded = $this->status === 'refunded';

        return [
            'title' => $isRefunded ? 'Refund diproses' : 'Pengajuan refund ditolak',
            'message' => $isRefunded
                ? sprintf(
                    'Refund untuk pesanan %s telah diproses. Dana akan dikembalikan sesuai kebijakan pembayaran.',
                    $this->order->order_code
                )
                : sprintf(
                    'Pengajuan refund untuk pesanan %s ditolak. Hubungi admin untuk informasi lebih lanjut.',
                    $this->order->order_code
                ),
            'order_id' => $this->order->id,
            'order_code' => $this->order->order_code,
            'payment_id' => $this->payment->id,
            'payment_code' => $this->payment->payment_code,
            'refund_status' => $this->status,
            'route' => route('home.tracking.show', $this->order->order_code),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
