<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Models\PlanUpgradeRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\AuditLogger;
use App\Services\PlanEntitlements;
use App\Services\SubscriptionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The customer's view of their plan (what they're on, what it costs, what
 * it allows) and the way to ask for Pro. There is no payment gateway, so
 * upgrading is a request an admin approves — see docs/ARCHITECTURE_DECISIONS.md.
 */
class SubscriptionController extends Controller
{
    public function __construct(
        private readonly PlanEntitlements $plans,
        private readonly SubscriptionManager $manager,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $active = $this->plans->activeSubscription($user);
        $latest = $user->subscriptions()->latest('id')->first();

        return response()->json([
            'plan' => new PlanResource($this->plans->planFor($user)),
            'source' => $active ? 'subscription' : 'default',
            // Latest subscription record even if lapsed, so the app can say "your Pro expired".
            'subscription' => $latest ? [
                // active | grace (period over, still working for a few days) | expired | canceled | ...
                'status' => $active && $active->id === $latest->id
                    ? ($latest->isInGrace() ? 'grace' : $latest->status)
                    : $this->lapsedStatus($latest),
                'current_period_ends_at' => $latest->current_period_ends_at?->toIso8601String(),
                'grace_ends_at' => $latest->isInGrace() ? $latest->graceEndsAt()?->toIso8601String() : null,
                // True once the customer stopped the renewal; the plan still runs to the end date.
                'renewal_canceled' => $latest->renewalCanceled(),
                'can_cancel_renewal' => $active !== null && $active->id === $latest->id && ! $latest->renewalCanceled()
                    && ! $latest->plan->isFree(),
            ] : null,
            'plans' => PlanResource::collection(SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get()),
            'pending_upgrade' => PlanUpgradeRequest::where('user_id', $user->id)->where('status', 'pending')->exists(),
        ]);
    }

    public function requestUpgrade(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan' => ['required', 'string', 'exists:subscription_plans,slug'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $target = SubscriptionPlan::where('slug', $data['plan'])->where('is_active', true)->first();

        if (! $target || $target->isFree()) {
            return $this->error('PLAN_NOT_AVAILABLE', 'هذه الباقة غير متاحة للترقية.', 422);
        }

        if ($this->plans->planFor($user)->id === $target->id) {
            return $this->error('ALREADY_ON_PLAN', 'أنت مشترك في هذه الباقة بالفعل.', 409);
        }

        if (PlanUpgradeRequest::where('user_id', $user->id)->where('status', 'pending')->exists()) {
            return $this->error('UPGRADE_PENDING', 'لديك طلب ترقية قيد المراجعة بالفعل.', 409);
        }

        $upgrade = PlanUpgradeRequest::create([
            'user_id' => $user->id, 'plan_id' => $target->id, 'note' => $data['note'] ?? null,
        ]);

        AuditLogger::log('subscription.upgrade_requested', 'customer', $user->id, 'PlanUpgradeRequest', $upgrade->id, 'success', ['plan' => $target->slug], $request);

        return response()->json(['message' => 'تم إرسال طلب الترقية. سيراجعه فريقنا قريبًا.'], 201);
    }

    /**
     * Stops the yearly renewal. The plan stays on until the end of the period that is
     * already paid for — the remaining days are kept.
     */
    public function cancelRenewal(Request $request): JsonResponse
    {
        $subscription = $this->plans->activeSubscription($request->user());

        if (! $subscription || $subscription->plan->isFree()) {
            return $this->error('NO_SUBSCRIPTION', 'ليس لديك اشتراك مدفوع.', 404);
        }

        $this->manager->cancelRenewal($subscription, 'customer', $request->user()->id);

        return $this->show($request);
    }

    public function resumeRenewal(Request $request): JsonResponse
    {
        $subscription = $this->plans->activeSubscription($request->user());

        if (! $subscription || $subscription->plan->isFree()) {
            return $this->error('NO_SUBSCRIPTION', 'ليس لديك اشتراك مدفوع.', 404);
        }

        $this->manager->resumeRenewal($subscription, 'customer', $request->user()->id);

        return $this->show($request);
    }

    private function lapsedStatus(Subscription $subscription): string
    {
        return in_array($subscription->status, ['canceled', 'past_due'], true) ? $subscription->status : 'expired';
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message]], $status);
    }
}
