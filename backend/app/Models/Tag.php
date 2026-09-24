<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * Generic tag abstraction shared by QR and NFC (section 12). `public_token`
 * is the only identifier ever sent to a public/unauthenticated client —
 * `id` and `uuid` must never leave the server boundary.
 */
class Tag extends Model
{
    protected $fillable = [
        'public_token', 'serial_number', 'type', 'batch_id', 'status', 'vehicle_id',
        'activated_at', 'assigned_at', 'revoked_at', 'revocation_reason', 'replaced_by_tag_id',
    ];

    protected $hidden = ['id', 'uuid'];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'assigned_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $tag) {
            $tag->uuid ??= (string) Str::uuid();
        });
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(TagBatch::class, 'batch_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function qrTag(): HasOne
    {
        return $this->hasOne(QrTag::class);
    }

    public function nfcTag(): HasOne
    {
        return $this->hasOne(NfcTag::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TagAssignment::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    public function isAvailableForActivation(): bool
    {
        return $this->status === 'available';
    }

    public function getRouteKeyName(): string
    {
        return 'public_token';
    }
}
