<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 'subscription_id', 'plan_id', 'idempotency_key', 'amount_cents', 'currency', 'status',
        'gateway', 'gateway_reference', 'metadata', 'receipt_number', 'checkout_url', 'failure_reason',
        'paid_at', 'refunded_amount_cents', 'refunded_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'paid_at' => 'datetime', 'refunded_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $payment) {
            $payment->uuid ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class)->latest('id');
    }

    public function refundableCents(): int
    {
        return $this->status === 'succeeded' ? max(0, $this->amount_cents - $this->refunded_amount_cents) : 0;
    }
}
