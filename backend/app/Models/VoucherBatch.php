<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A numbered run of activation vouchers made together for one plan and one duration. */
class VoucherBatch extends Model
{
    protected $fillable = [
        'batch_code', 'plan_id', 'duration_days', 'unit_price', 'customer_id', 'quantity', 'recipient', 'notes', 'expires_at', 'created_by', 'portal_code',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $batch) {
            $batch->portal_code ??= self::newPortalCode();
        });
    }

    /** The secret a reseller types, with the batch number, on the public look-up page. */
    public static function newPortalCode(): string
    {
        $alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $alphabet[random_int(0, 31)];
        }

        return $code;
    }

    public static function normalizePortalCode(string $input): string
    {
        $code = strtoupper((string) preg_replace('/[\s\-]+/', '', $input));

        return strtr($code, ['O' => '0', 'I' => '1', 'L' => '1']);
    }

    public function portalCodeFormatted(): string
    {
        $code = (string) $this->portal_code;

        return substr($code, 0, 4).'-'.substr($code, 4);
    }

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'duration_days' => 'integer', 'quantity' => 'integer'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class, 'batch_id');
    }

    public function exports(): HasMany
    {
        return $this->hasMany(VoucherExport::class, 'batch_id')->latest('id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(TradeCustomer::class, 'customer_id');
    }

    /** The trade-book entry that billed this batch to its reseller, if it is still standing. */
    public function saleEntry(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TradeEntry::class, 'voucher_batch_id')
            ->where('type', TradeEntry::VOUCHER_SALE)->whereNull('voided_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** @return array{available: int, distributed: int, redeemed: int, revoked: int} */
    public function counts(): array
    {
        $rows = $this->vouchers()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        return [
            'available' => (int) ($rows['available'] ?? 0),
            'distributed' => (int) ($rows['distributed'] ?? 0),
            'redeemed' => (int) ($rows['redeemed'] ?? 0),
            'revoked' => (int) ($rows['revoked'] ?? 0),
        ];
    }
}
