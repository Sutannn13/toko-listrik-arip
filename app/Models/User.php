<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'profile_photo_path',
        'password',
        'is_suspended',
        'suspended_at',
        'suspended_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_suspended'      => 'boolean',
            'suspended_at'      => 'datetime',
        ];
    }

    public function isSuspended(): bool
    {
        return (bool) $this->is_suspended;
    }

    public function primaryRole(): string
    {
        return $this->getRoleNames()->first() ?? 'user';
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function warrantyClaims(): HasMany
    {
        return $this->hasMany(WarrantyClaim::class);
    }

    public function warrantyClaimActivities(): HasMany
    {
        return $this->hasMany(WarrantyClaimActivity::class, 'actor_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
