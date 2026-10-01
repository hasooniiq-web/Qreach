<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

/**
 * Customer account. Authenticates via phone + OTP only — there is no password.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'uuid',
        'phone',
        'email',
        'name',
        'status',
        'locale',
        'timezone',
        'phone_verified_at',
        'email_verified_at',
        'last_login_at',
        'avatar_path',
        'cover_path',
        'show_name_publicly',
        'show_avatar_publicly',
        'show_cover_publicly',
        'password',
    ];

    protected $hidden = [
        'remember_token',
        'password',
    ];

    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'show_name_publicly' => 'boolean',
            'show_avatar_publicly' => 'boolean',
            'show_cover_publicly' => 'boolean',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $user) {
            $user->uuid ??= (string) Str::uuid();
        });
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function contactMethods(): HasMany
    {
        return $this->hasMany(ContactMethod::class);
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmergencyContact::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
