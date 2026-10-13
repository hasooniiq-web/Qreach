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

    /**
     * Statuses a tag may be activated/replaced-into from. The lifecycle is strictly
     * sequential (generated -> printing -> unassigned -> assigned), so a code that
     * has not been sent to print yet ("generated") can never be assigned.
     * `available` is the legacy spelling of `unassigned`.
     */
    public const ASSIGNABLE_STATUSES = ['printing', 'unassigned', 'available'];

    /** Statuses a tag can be cancelled from: only a code that was assigned before. */
    public const CANCELLABLE_STATUSES = ['assigned', 'activated'];

    protected $fillable = [
        'public_token', 'serial_number', 'type', 'batch_id', 'status', 'vehicle_id',
        'activated_at', 'assigned_at', 'revoked_at', 'revocation_reason', 'replaced_by_tag_id',
        'qr_enabled', 'nfc_enabled',
    ];

    protected $hidden = ['id', 'uuid'];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'assigned_at' => 'datetime',
            'revoked_at' => 'datetime',
            'qr_enabled' => 'boolean',
            'nfc_enabled' => 'boolean',
        ];
    }

    /** The two ways a code is opened: the printed QR, or the NFC chip. */
    public const CHANNEL_QR = 'qr';

    public const CHANNEL_NFC = 'nfc';

    /** Whether this code answers when opened through that channel. */
    public function channelEnabled(string $channel): bool
    {
        return $channel === self::CHANNEL_NFC ? (bool) $this->nfc_enabled : (bool) $this->qr_enabled;
    }

    /** True while at least one channel is switched off (the code then needs proof of how it was opened). */
    public function hasDisabledChannel(): bool
    {
        return ! $this->qr_enabled || ! $this->nfc_enabled;
    }

    /**
     * What the NFC chip is written with. The extra `s` is a signature only the server can make, so
     * someone who photographed the printed QR cannot add it: "QR off, NFC on" really keeps the QR out.
     */
    public function nfcSignature(): string
    {
        return substr(hash_hmac('sha256', $this->public_token.'|nfc', (string) config('app.key')), 0, 16);
    }

    public function nfcUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/t/'.$this->public_token.'?c=n&s='.$this->nfcSignature();
    }

    /** Which channel a request came through: signed NFC address, or (anything else) the QR / plain link. */
    public function channelOf(?string $c, ?string $s): string
    {
        return $c === 'n' && is_string($s) && hash_equals($this->nfcSignature(), $s)
            ? self::CHANNEL_NFC
            : self::CHANNEL_QR;
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

    public function isCancellable(): bool
    {
        return in_array($this->status, self::CANCELLABLE_STATUSES, true);
    }

    public function getRouteKeyName(): string
    {
        return 'public_token';
    }
}
