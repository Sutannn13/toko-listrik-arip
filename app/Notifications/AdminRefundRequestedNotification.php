<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminRefundRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly Payment $payment,
        private readonly string $reasonLabel,
        private readonly string $details,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $customerName = trim((string) $this->order->customer_name);
        if ($customerName === '') {
            $customerName = 'Pelanggan';
        }

        $message = sprintf(
            '%s mengajukan refund untuk order %s. Alasan: %s.',
            $customerName,
            $this->order->order_code,
            $this->reasonLabel,
        );

        if (trim($this->details) !== '') {
            $message .= ' Detail: ' . trim($this->details);
        }

        return [
            'title' => 'Pengajuan refund baru',
            'message' => $message,
            'order_id' => $this->order->id,
            'order_code' => $this->order->order_code,
            'payment_id' => $this->payment->id,
            'payment_code' => $this->payment->payment_code,
            'refund_reason' => $this->reasonLabel,
            'refund_details' => trim($this->details) !== '' ? trim($this->details) : null,
            'route' => route('admin.orders.show', $this->order),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
