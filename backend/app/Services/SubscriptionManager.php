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

            AuditLogger::log($action, 'system', null, 'Subscription', $subscription->id, 'success', [
                'user_id' => $payment->user_id, 'plan' => $plan->slug, 'days' => $days, 'payment_id' => $payment->id,
            ]);

            return $subscription;
        });
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

        if ($existing && $existing->isCurrentlyActive()) {
            $existing->update([
                'current_period_ends_at' => ($existing->current_period_ends_at ?? now())->copy()->addDays($days),
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
