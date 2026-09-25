<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Subscription extends Model
{
    protected $fillable = ['user_id', 'plan_id', 'status', 'current_period_starts_at', 'current_period_ends_at', 'canceled_at'];

    protected function casts(): array
    {
        return [
            'current_period_starts_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $sub) {
            $sub->uuid ??= (string) Str::uuid();
        });
    }

    /** Active/trialing and not past its end date — the only state that grants plan features. */
    public function isCurrentlyActive(): bool
    {
        return in_array($this->status, ['active', 'trialing'], true)
            && ($this->current_period_ends_at === null || $this->current_period_ends_at->isFuture());
    }

    /** What to show an admin: a stored "active" row past its end date is really expired. */
    public function effectiveStatus(): string
    {
        if (in_array($this->status, ['active', 'trialing'], true) && ! $this->isCurrentlyActive()) {
            return 'expired';
        }

        return $this->status;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }
}
