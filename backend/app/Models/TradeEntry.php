<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One line of the accounts book: a sale of stickers, or a payment received. */
class TradeEntry extends Model
{
    public const SALE = 'sale';

    public const PAYMENT = 'payment';

    /** Activation vouchers sold to a reseller (money only: they are not stickers in stock). */
    public const VOUCHER_SALE = 'voucher_sale';

    /** Entry types that add to what a customer owes. */
    public const CHARGES = [self::SALE, self::VOUCHER_SALE];

    protected $fillable = [
        'customer_id', 'type', 'batch_id', 'voucher_batch_id', 'quantity', 'unit_price', 'amount', 'receipt_number',
        'occurred_on', 'note', 'created_by', 'voided_at', 'void_reason', 'voided_by',
    ];

    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
            'voided_at' => 'datetime',
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'amount' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(TradeCustomer::class, 'customer_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(TagBatch::class, 'batch_id');
    }

    public function voucherBatch(): BelongsTo
    {
        return $this->belongsTo(VoucherBatch::class, 'voucher_batch_id');
    }

    public function isCharge(): bool
    {
        return in_array($this->type, self::CHARGES, true);
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }
}
