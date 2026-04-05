<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'unit',
        'specifications',
        'is_active'
    ];

    protected $casts = [
        'specifications' => 'array', // Biar JSON otomatis jadi array di PHP
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
