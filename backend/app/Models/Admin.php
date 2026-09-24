<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Dashboard administrator. Fully separate guard/table from customer users
 * (section 20) — never share auth, sessions, or Sanctum tokens with `User`.
 */
class Admin extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'status',
        'must_change_password',
        'locked_until',
        'failed_login_attempts',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'totp_secret',
        'recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'totp_secret' => 'encrypted',
            'recovery_codes' => 'encrypted:array',
            'totp_enabled' => 'boolean',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $admin) {
            $admin->uuid ??= (string) Str::uuid();
        });
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'admin_role');
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    public function hasPermission(string $key): bool
    {
        return $this->roles->loadMissing('permissions')
            ->pluck('permissions')
            ->flatten()
            ->pluck('key')
            ->contains($key);
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }
}
