<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminPaymentProofUploadedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly Payment $payment,
        private readonly bool $isReplacement = false,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $actionLabel = $this->isReplacement ? 'mengganti' : 'mengunggah';

        return [
            'title' => 'Bukti pembayaran masuk',
            'message' => sprintf(
                'Pelanggan baru saja %s bukti pembayaran untuk order %s. Silakan tinjau sekarang.',
                $actionLabel,
                $this->order->order_code
            ),
            'order_id' => $this->order->id,
            'order_code' => $this->order->order_code,
            'payment_id' => $this->payment->id,
            'payment_code' => $this->payment->payment_code,
            'payment_method' => $this->payment->method,
            'route' => route('admin.orders.show', $this->order),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
