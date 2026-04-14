<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'payment_code',
        'gateway_invoice_id',
        'method',
        'gateway_provider',
        'amount',
        'status',
        'gateway_status',
        'paid_at',
        'gateway_expires_at',
        'proof_url',
        'gateway_payment_url',
        'gateway_paid_reference',
        'notes',
        'gateway_payload',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'gateway_expires_at' => 'datetime',
        'gateway_payload' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
