<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentProofStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly Payment $payment,
        private readonly string $decision,
        private readonly ?string $adminNotes = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $isApproved = $this->decision === 'approved';
        $title = $isApproved
            ? 'Bukti pembayaran disetujui'
            : 'Bukti pembayaran ditolak';

        $message = $isApproved
            ? sprintf(
                'Bukti pembayaran untuk pesanan %s telah disetujui admin. Pesanan Anda sedang diproses.',
                $this->order->order_code
            )
            : sprintf(
                'Bukti pembayaran untuk pesanan %s ditolak admin. Silakan unggah bukti baru melalui halaman cek pesanan.',
                $this->order->order_code
            );

        $trimmedAdminNotes = trim((string) $this->adminNotes);
        if (!$isApproved && $trimmedAdminNotes !== '') {
            $message .= ' Catatan admin: ' . $trimmedAdminNotes;
        }

        return [
            'title' => $title,
            'message' => $message,
            'order_id' => $this->order->id,
            'order_code' => $this->order->order_code,
            'payment_id' => $this->payment->id,
            'payment_code' => $this->payment->payment_code,
            'decision' => $this->decision,
            'status' => $this->payment->status,
            'admin_notes' => $trimmedAdminNotes !== '' ? $trimmedAdminNotes : null,
            'route' => route('home.tracking.show', $this->order->order_code),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
