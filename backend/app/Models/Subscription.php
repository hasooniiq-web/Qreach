<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Subscription extends Model
{
    protected $fillable = ['user_id', 'plan_id', 'status', 'current_period_starts_at', 'current_period_ends_at', 'canceled_at', 'renewal_canceled_at'];

    protected function casts(): array
    {
        return [
            'current_period_starts_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'canceled_at' => 'datetime',
            'renewal_canceled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $sub) {
            $sub->uuid ??= (string) Str::uuid();
        });
    }

    public static function graceDays(): int
    {
        return max(0, (int) config('subscriptions.grace_days', 7));
    }

    public function isTrial(): bool
    {
        return $this->status === 'trialing';
    }

    private function isLive(): bool
    {
        return in_array($this->status, ['active', 'trialing'], true);
    }

    /** Active/trialing and inside its paid period (not yet past its end date). */
    public function isCurrentlyActive(): bool
    {
        return $this->isLive()
            && ($this->current_period_ends_at === null || $this->current_period_ends_at->isFuture());
    }

    /** The paid period ended less than the grace period ago — still working, renewable without loss. */
    public function isInGrace(): bool
    {
        // A free trial just ends: the grace week protects what a customer paid for.
        return $this->status === 'active'
            && $this->current_period_ends_at !== null
            && $this->current_period_ends_at->isPast()
            && $this->graceEndsAt()->isFuture();
    }

    public function graceEndsAt(): ?Carbon
    {
        return $this->current_period_ends_at?->copy()->addDays(self::graceDays());
    }

    /** Whether the plan's features apply right now: paid period or its grace week. */
    public function grantsAccess(): bool
    {
        return $this->isCurrentlyActive() || $this->isInGrace();
    }

    /** The customer chose not to renew; the plan still runs to the end of the period. */
    public function renewalCanceled(): bool
    {
        return $this->renewal_canceled_at !== null;
    }

    /** What to show an admin: grace and expiry are derived from the dates, not stored. */
    public function effectiveStatus(): string
    {
        if ($this->isLive() && ! $this->isCurrentlyActive()) {
            return $this->isInGrace() ? 'grace' : 'expired';
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

    public function notices(): HasMany
    {
        return $this->hasMany(SubscriptionNotice::class);
    }
}
