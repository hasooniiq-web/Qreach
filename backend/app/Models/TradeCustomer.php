<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A wholesale customer (trader) who receives stickers on account and pays later. */
class TradeCustomer extends Model
{
    protected $fillable = ['name', 'phone', 'notes', 'created_by'];

    public function entries(): HasMany
    {
        return $this->hasMany(TradeEntry::class, 'customer_id');
    }

    /** Sales minus payments, voided entries ignored. Positive = the customer owes us. */
    public function balance(): int
    {
        $sales = (int) $this->entries()->where('type', TradeEntry::SALE)->whereNull('voided_at')->sum('amount');
        $paid = (int) $this->entries()->where('type', TradeEntry::PAYMENT)->whereNull('voided_at')->sum('amount');

        return $sales - $paid;
    }
}
