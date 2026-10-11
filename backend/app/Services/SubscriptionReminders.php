<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionNotice;
use App\Notifications\SubscriptionNoticeNotification;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Subscription reminders, run once a day:
 *
 *   30, 10 and 1 day(s) before the period ends   -> "ينتهي اشتراكك بعد ..."
 *   when it ended (the grace week begins)        -> "لديك أسبوع إضافي"
 *   when the grace week is over                  -> "عادت باقتك إلى الأساسية"
 *
 * Every reminder is written to `subscription_notices` first (unique per
 * subscription, kind and period end), so a re-run, a restart or a catch-up
 * after downtime can never send the same reminder twice — and a renewal, which
 * moves the period end, starts a fresh set. After downtime only the most
 * relevant reminder is sent, not the whole backlog.
 */
class SubscriptionReminders
{
    /** @return array<string, int> kind => how many were sent */
    public function run(): array
    {
        $sent = [];
        $thresholds = collect(config('subscriptions.reminder_days', [30, 10, 1]))->map(fn ($d) => (int) $d)->sort()->values();

        Subscription::query()
            ->with(['user', 'plan'])
            ->whereIn('status', ['active', 'trialing'])
            ->whereNotNull('current_period_ends_at')
            ->whereHas('plan', fn ($q) => $q->where('price_cents', '>', 0))
            ->where('current_period_ends_at', '>', now()->subDays(Subscription::graceDays() + 2))
            ->orderBy('id')
            ->chunkById(200, function ($subscriptions) use (&$sent, $thresholds) {
                foreach ($subscriptions as $subscription) {
                    if (! $subscription->user || ! $subscription->plan) {
                        continue;
                    }

                    $kind = $this->dueKind($subscription, $thresholds->all());
                    if ($kind === null) {
                        continue;
                    }

                    if ($this->send($subscription, $kind)) {
                        $sent[$kind] = ($sent[$kind] ?? 0) + 1;
                    }
                }
            });

        return $sent;
    }

    /** The one reminder that should go out for this subscription now, if any. */
    private function dueKind(Subscription $subscription, array $thresholds): ?string
    {
        $ends = $subscription->current_period_ends_at;

        if ($ends->isFuture()) {
            $daysLeft = (int) ceil(now()->floatDiffInDays($ends));
            $periodDays = $subscription->current_period_starts_at
                ? (int) $subscription->current_period_starts_at->diffInDays($ends)
                : (int) $subscription->plan->billing_period_days;

            // The smallest threshold that has been reached; every larger one is considered
            // handled (recorded silently) so a late run sends one reminder, not three.
            $reached = null;
            foreach ($thresholds as $t) {
                if ($daysLeft <= $t) {
                    $reached = $t;
                    break;
                }
            }
            if ($reached === null) {
                return null;
            }

            foreach ($thresholds as $t) {
                // A 7-day grant never gets a "30 days left" reminder.
                if ($t > $reached || $t >= $periodDays) {
                    $this->mark($subscription, "before_{$t}");
                }
            }

            return $reached < $periodDays ? "before_{$reached}" : null;
        }

        return $subscription->graceEndsAt()->isFuture() ? 'expired' : 'downgraded';
    }

    private function mark(Subscription $subscription, string $kind): void
    {
        try {
            SubscriptionNotice::firstOrCreate([
                'subscription_id' => $subscription->id,
                'kind' => $kind,
                'period_ends_at' => $subscription->current_period_ends_at,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Raced with another run: it is recorded, which is all that matters.
        }
    }

    private function send(Subscription $subscription, string $kind): bool
    {
        $notice = SubscriptionNotice::where([
            'subscription_id' => $subscription->id,
            'kind' => $kind,
            'period_ends_at' => $subscription->current_period_ends_at,
        ])->exists();

        if ($notice) {
            return false;
        }

        try {
            SubscriptionNotice::create([
                'subscription_id' => $subscription->id,
                'kind' => $kind,
                'period_ends_at' => $subscription->current_period_ends_at,
            ]);
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        // Skipped silently when the previous kinds of this period were never sent (see run()).
        if ($kind === 'downgraded') {
            $this->mark($subscription, 'expired');
        }

        $ends = $subscription->current_period_ends_at;
        $daysLeft = max(1, (int) ceil(now()->floatDiffInDays($ends, false)));

        $subscription->user->notify(new SubscriptionNoticeNotification(
            kind: $kind,
            daysLeft: $daysLeft,
            planName: $subscription->plan->name_ar,
            endsOn: $ends->copy()->timezone(config('app.display_timezone'))->format('Y-m-d'),
            renewalCanceled: $subscription->renewalCanceled(),
            graceDays: Subscription::graceDays(),
        ));

        return true;
    }
}
