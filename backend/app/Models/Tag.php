<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    use HasFactory;

    /**
     * The 6-stage lifecycle (spec naming -> internal value):
     *   Created -> generated | Sent to Printing -> printing | Unassigned -> unassigned
     *   Assigned -> assigned | Replacement (old code) -> replaced | Cancelled -> revoked
     * `available`/`activated`/`disabled` are legacy values kept for old rows.
     */
    public const STATUS_CREATED = 'generated';

    public const STATUS_PRINTING = 'printing';

    public const STATUS_UNASSIGNED = 'unassigned';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_REPLACED = 'replaced';

    public const STATUS_CANCELLED = 'revoked';

    /** Statuses a tag may still be activated/replaced-into from. */
    public const ASSIGNABLE_STATUSES = ['generated', 'available', 'unassigned'];

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

    /** A unified code carries both a QR and an NFC chip; older rows are one or the other. */
    public function hasQr(): bool
    {
        return in_array($this->type, ['qr', 'code'], true);
    }

    public function hasNfc(): bool
    {
        return in_array($this->type, ['nfc', 'code'], true);
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->type] ?? strtoupper((string) $this->type);
    }

    /** @return array<string, string> */
    public static function typeLabels(): array
    {
        return ['code' => 'QR + NFC', 'qr' => 'QR', 'nfc' => 'NFC'];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TagAssignment::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    public function lifecycleEvents(): HasMany
    {
        return $this->hasMany(TagLifecycleEvent::class)->latest('id');
    }

    /** The tag this one replaced, if it was created as a replacement. */
    public function replaces(): HasOne
    {
        return $this->hasOne(Tag::class, 'replaced_by_tag_id');
    }

    public function isAvailableForActivation(): bool
    {
        return in_array($this->status, self::ASSIGNABLE_STATUSES, true);
    }

    public function getRouteKeyName(): string
    {
        return 'public_token';
    }
}
