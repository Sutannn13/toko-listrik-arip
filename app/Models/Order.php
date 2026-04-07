<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_code',
        'user_id',
        'address_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'notes',
        'status',
        'tracking_number',
        'payment_status',
        'warranty_status',
        'subtotal',
        'shipping_cost',
        'discount_amount',
        'total_amount',
        'placed_at',
        'paid_at',
        'completed_at',
    ];

    protected $casts = [
        'placed_at' => 'datetime',
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function warrantyClaims(): HasMany
    {
        return $this->hasMany(WarrantyClaim::class);
    }
}
