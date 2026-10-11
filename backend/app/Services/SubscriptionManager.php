<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PlanUpgradeRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Plan changes: an admin grants, extends, or cancels a plan by hand, and a
 * verified electronic payment activates one automatically (activateFromPayment).
 * Every change is audit-logged.
 */
class SubscriptionManager
{
    /** Grants the plan, or extends the current period if the user already has it active. */
    public function grant(User $user, SubscriptionPlan $plan, int $adminId, ?int $days = null): Subscription
    {
        $days ??= $plan->billing_period_days;

        return DB::transaction(function () use ($user, $plan, $adminId, $days) {
            [$subscription, $action] = $this->applyGrant($user, $plan, $days);
            $this->recordUpgrade($user, $plan, $adminId, 'منحها المسؤول مباشرة');

            AuditLogger::log($action, 'admin', $adminId, 'Subscription', $subscription->id, 'success', [
                'user_id' => $user->id, 'plan' => $plan->slug, 'days' => $days,
            ]);

            return $subscription;
        });
    }

    /**
     * Activates (or extends) the plan a verified payment bought. Called only by
     * PaymentService, after the gateway's outcome and amount have been checked.
     */
    public function activateFromPayment(Payment $payment): Subscription
    {
        $plan = $payment->plan;
        $days = $plan->billing_period_days;

        return DB::transaction(function () use ($payment, $plan, $days) {
            [$subscription, $action] = $this->applyGrant($payment->user, $plan, $days);
            $this->recordUpgrade($payment->user, $plan, null, 'تفعيل تلقائي بعد دفع إلكتروني ناجح');

            AuditLogger::log($action, 'system', null, 'Subscription', $subscription->id, 'success', [
                'user_id' => $payment->user_id, 'plan' => $plan->slug, 'days' => $days, 'payment_id' => $payment->id,
            ]);

            return $subscription;
        });
    }

    /**
     * Every plan change, however it happened, appears in the upgrade-requests
     * history: a waiting request from this user is closed as approved, and when
     * there was none (an admin grant, or a payment) an approved entry is added so
     * the "approved" list is the complete record of who was upgraded and how.
     */
    private function recordUpgrade(User $user, SubscriptionPlan $plan, ?int $adminId, string $note): void
    {
        $pending = PlanUpgradeRequest::where('user_id', $user->id)
            ->where('plan_id', $plan->id)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        if ($pending) {
            $pending->update(['status' => 'approved', 'handled_by' => $adminId, 'handled_at' => now()]);

            return;
        }

        PlanUpgradeRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'approved',
            'note' => $note,
            'handled_by' => $adminId,
            'handled_at' => now(),
        ]);
    }

    /** @return array{0: Subscription, 1: string} the subscription and the audit action describing what happened */
    private function applyGrant(User $user, SubscriptionPlan $plan, int $days): array
    {
        $existing = $user->subscriptions()
            ->where('plan_id', $plan->id)
            ->whereIn('status', ['active', 'trialing'])
            ->latest('id')
            ->lockForUpdate()
            ->first();

        if ($existing && $existing->grantsAccess()) {
            // Extends from the later of the old end and now: renewing inside the grace week
            // must not hand back days that were already used up.
            $base = $existing->current_period_ends_at?->isFuture() ? $existing->current_period_ends_at : now();
            $existing->update([
                'current_period_ends_at' => $base->copy()->addDays($days),
                'renewal_canceled_at' => null,
            ]);

            return [$existing, 'admin.subscription_extended'];
        }

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_starts_at' => now(),
            'current_period_ends_at' => now()->addDays($days),
        ]);

        return [$subscription, 'admin.subscription_granted'];
    }

    public function cancel(Subscription $subscription, int $adminId): void
    {
        $subscription->update(['status' => 'canceled', 'canceled_at' => now()]);

        AuditLogger::log('admin.subscription_canceled', 'admin', $adminId, 'Subscription', $subscription->id, 'success', [
            'user_id' => $subscription->user_id, 'plan_id' => $subscription->plan_id,
        ]);
    }

    /**
     * The customer stops the yearly renewal. Nothing is taken away: the status stays
     * active and the plan keeps working until the end of the period already paid for.
     */
    public function cancelRenewal(Subscription $subscription, string $actorType = 'customer', ?int $actorId = null): Subscription
    {
        if ($subscription->renewal_canceled_at === null) {
            $subscription->update(['renewal_canceled_at' => now()]);
            AuditLogger::log('subscription.renewal_canceled', $actorType, $actorId, 'Subscription', $subscription->id, 'success', [
                'ends_at' => $subscription->current_period_ends_at?->toIso8601String(),
            ]);
        }

        return $subscription;
    }

    public function resumeRenewal(Subscription $subscription, string $actorType = 'customer', ?int $actorId = null): Subscription
    {
        if ($subscription->renewal_canceled_at !== null) {
            $subscription->update(['renewal_canceled_at' => null]);
            AuditLogger::log('subscription.renewal_resumed', $actorType, $actorId, 'Subscription', $subscription->id, 'success');
        }

        return $subscription;
    }

    public function approveUpgrade(PlanUpgradeRequest $request, int $adminId): Subscription
    {
        return DB::transaction(function () use ($request, $adminId) {
            $subscription = $this->grant($request->user, $request->plan, $adminId);
            $request->update(['status' => 'approved', 'handled_by' => $adminId, 'handled_at' => now()]);

            return $subscription;
        });
    }

    public function rejectUpgrade(PlanUpgradeRequest $request, int $adminId): void
    {
        $request->update(['status' => 'rejected', 'handled_by' => $adminId, 'handled_at' => now()]);

        AuditLogger::log('admin.upgrade_rejected', 'admin', $adminId, 'PlanUpgradeRequest', $request->id, 'success', [
            'user_id' => $request->user_id,
        ]);
    }
}
