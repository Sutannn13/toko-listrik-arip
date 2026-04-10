<?php

namespace App\Notifications;

use App\Models\WarrantyClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WarrantyClaimSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly WarrantyClaim $claim) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Klaim garansi baru masuk',
            'message' => sprintf(
                'Klaim %s untuk order %s memerlukan peninjauan.',
                $this->claim->claim_code,
                $this->claim->order?->order_code ?? '-'
            ),
            'claim_id' => $this->claim->id,
            'claim_code' => $this->claim->claim_code,
            'order_code' => $this->claim->order?->order_code,
            'route' => route('admin.warranty-claims.show', $this->claim),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
