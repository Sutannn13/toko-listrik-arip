<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'image_path',
        'price',
        'stock',
        'unit',
        'specifications',
        'is_active',
        'is_electronic',
        'warranty_days',
    ];

    protected $casts = [
        'specifications' => 'array', // Biar JSON otomatis jadi array di PHP
        'is_active' => 'boolean',
        'is_electronic' => 'boolean',
        'warranty_days' => 'integer',
    ];

    public function getWarrantyDaysForClaimAttribute(): int
    {
        if (! $this->is_electronic) {
            return 0;
        }

        $days = (int) $this->warranty_days;

        if ($days < 1) {
            return 7;
        }

        return min(365, $days);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function getImageUrlAttribute(): string
    {
        if (! empty($this->image_path)) {
            return Storage::url($this->image_path);
        }

        return asset('img/hero-bg.jpg');
    }

    public function getAverageRatingAttribute(): float
    {
        $avg = $this->getAttribute('reviews_avg_rating');
        if ($avg === null) {
            $avg = $this->reviews()->avg('rating');
        }

        return round((float) $avg, 1);
    }

    public function getReviewsTotalAttribute(): int
    {
        return (int) ($this->getAttribute('reviews_count') ?? $this->reviews()->count());
    }
}
