<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Device extends Model
{
    protected $fillable = [
        'user_id', 'admin_id', 'personal_access_token_id', 'platform', 'push_provider',
        'push_token', 'device_name', 'app_version', 'os_version', 'last_active_at',
        'is_active', 'revoked_at',
    ];

    protected $hidden = ['push_token'];

    protected function casts(): array
    {
        return [
            'last_active_at' => 'datetime',
            'is_active' => 'boolean',
            'revoked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $device) {
            $device->uuid ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
