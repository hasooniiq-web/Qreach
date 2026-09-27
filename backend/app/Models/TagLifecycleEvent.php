<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only ledger of every QR/NFC status transition (spec: "every status
 * change must be recorded in an immutable audit/history table"). Never
 * update or delete rows here.
 */
class TagLifecycleEvent extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'tag_id', 'previous_status', 'new_status', 'reason',
        'performed_by_type', 'performed_by_id', 'vehicle_id', 'user_id', 'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The admin/customer who performed the action, when known. */
    public function performedBy(): ?Model
    {
        return match ($this->performed_by_type) {
            'admin' => Admin::find($this->performed_by_id),
            'customer' => User::find($this->performed_by_id),
            default => null,
        };
    }
}
