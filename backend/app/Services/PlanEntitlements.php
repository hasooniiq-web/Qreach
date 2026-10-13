<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;

/**
 * The single place that answers "what is this customer allowed to do?".
 * Every plan-gated behavior (setting public contact info, showing it on the
 * scan page) goes through here, server-side — the app and dashboard only
 * *display* entitlements, they never decide them.
 *
 * A customer with no active paid subscription is on Basic. Basic is the
 * free default tier, so it has no subscription row; only explicit plan
 * grants (Pro) create rows in `subscriptions`.
 */
class PlanEntitlements
{
    /** The subscription currently granting access, if any (active/trialing, inside its period or its grace week). */
    public function activeSubscription(User $user): ?Subscription
    {
        return $user->subscriptions()
            ->with('plan')
            ->whereIn('status', ['active', 'trialing'])
            // The paid period plus its grace week (config subscriptions.grace_days).
            ->where(fn ($q) => $q->whereNull('current_period_ends_at')->orWhere('current_period_ends_at', '>', now()->subDays(Subscription::graceDays())))
            ->latest('id')
            ->get()
            // A trial has no grace week, so the query's widened window is re-checked per row.
            ->filter(fn (Subscription $s) => $s->grantsAccess())
            ->sortByDesc(fn (Subscription $s) => $s->plan->price_cents)
            ->first();
    }

    public function basicPlan(): SubscriptionPlan
    {
        // Basic is what everyone falls back to, so a missing row must never
        // take the public scan page down — recreate the free, messaging-only
        // default instead (the seeder normally provides it).
        return SubscriptionPlan::firstOrCreate(
            ['slug' => SubscriptionPlan::BASIC],
            [
                'name_ar' => 'الأساسية', 'name_en' => 'Basic', 'price_cents' => 0, 'currency' => 'IQD',
                'billing_period_days' => 365, 'is_active' => true, 'sort_order' => 1,
                'features' => ['messaging' => true, 'emergency_contact' => true, 'extra_contact_methods' => false, 'public_contacts' => ['phone']],
            ]
        );
    }

    public function planFor(User $user): SubscriptionPlan
    {
        return $this->activeSubscription($user)?->plan ?? $this->basicPlan();
    }

    public function canDisplayPublicly(User $user, string $contactType): bool
    {
        return $this->planFor($user)->allowsPublicContact($contactType);
    }

    public function canAddExtraMethods(User $user): bool
    {
        return $this->planFor($user)->allowsExtraMethods();
    }
}
