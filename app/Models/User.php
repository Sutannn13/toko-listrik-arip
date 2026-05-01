<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'profile_photo_path',
        'google_id',
        'avatar',
        'provider',
        'password',
        'email_verified_at',
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

    /**
     * Check if the user registered via Google OAuth.
     */
    public function isGoogleUser(): bool
    {
        return $this->google_id !== null;
    }

    /**
     * Check if the user has a local (password-based) account.
     */
    public function hasLocalPassword(): bool
    {
        return $this->password !== null;
    }

    public function primaryRole(): string
    {
        return $this->getRoleNames()->first() ?? 'user';
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
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
