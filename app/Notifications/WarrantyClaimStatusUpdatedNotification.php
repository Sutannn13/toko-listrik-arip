<?php

namespace App\Notifications;

use App\Models\WarrantyClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WarrantyClaimStatusUpdatedNotification extends Notification
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
            'title' => 'Status klaim garansi diperbarui',
            'message' => sprintf(
                'Klaim %s kini berstatus %s.',
                $this->claim->claim_code,
                strtoupper($this->claim->status)
            ),
            'claim_id' => $this->claim->id,
            'claim_code' => $this->claim->claim_code,
            'status' => $this->claim->status,
            'admin_notes' => $this->claim->admin_notes,
            'route' => route('home.warranty-claims.index'),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
