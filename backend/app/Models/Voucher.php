<?php

namespace App\Models;

use App\Services\VoucherService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One single-use activation voucher. The serial (12 characters; older ones are 16 digits) is encrypted at rest. */
class Voucher extends Model
{
    public const AVAILABLE = 'available';

    public const DISTRIBUTED = 'distributed';

    public const REDEEMED = 'redeemed';

    public const REVOKED = 'revoked';

    protected $fillable = [
        'batch_id', 'code_hash', 'code', 'last4', 'status', 'exported_at', 'delivered_to',
        'redeemed_by', 'redeemed_at', 'subscription_id', 'revoked_at', 'revoke_reason', 'revoked_by',
    ];

    protected $hidden = ['code', 'code_hash'];

    protected function casts(): array
    {
        return [
            'code' => 'encrypted',
            'exported_at' => 'datetime',
            'redeemed_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(VoucherBatch::class, 'batch_id');
    }

    public function redeemer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }

    /** 1234 5678 9012 3456 */
    public function formattedCode(): string
    {
        return VoucherService::format((string) $this->code);
    }

    /** •••• •••• •••• 3456 — what lists show; the full serial is only in exports. */
    public function maskedCode(): string
    {
        return '•••• •••• •••• '.$this->last4;
    }

    public function isRedeemable(): bool
    {
        if (! in_array($this->status, [self::AVAILABLE, self::DISTRIBUTED], true)) {
            return false;
        }

        return ! ($this->batch?->isExpired() ?? false);
    }

    /** The status to show: an unused voucher past its batch date reads as expired. */
    public function effectiveStatus(): string
    {
        if (in_array($this->status, [self::AVAILABLE, self::DISTRIBUTED], true) && ($this->batch?->isExpired() ?? false)) {
            return 'expired';
        }

        return $this->status;
    }
}
