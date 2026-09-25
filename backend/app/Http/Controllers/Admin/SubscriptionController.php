<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlanUpgradeRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Who is subscribed, to what, at what price, and in what state — plus the
 * manual grant/cancel and upgrade-approval actions (no payment gateway).
 * Every figure is a real query; the "value" KPI is the plans' listed prices
 * for currently-active subscriptions, i.e. expected value, not money received.
 */
class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionManager $manager) {}

    public function index(Request $request): View
    {
        $now = now();

        $subscriptions = Subscription::query()
            ->with(['user', 'plan'])
            ->when($request->filled('plan'), fn ($q) => $q->whereHas('plan', fn ($p) => $p->where('slug', $request->string('plan'))))
            ->when($request->string('status')->toString() === 'active', fn ($q) => $q->whereIn('status', ['active', 'trialing'])->where(fn ($w) => $w->whereNull('current_period_ends_at')->orWhere('current_period_ends_at', '>', $now)))
            ->when($request->string('status')->toString() === 'expired', fn ($q) => $q->whereIn('status', ['active', 'trialing'])->where('current_period_ends_at', '<=', $now))
            ->when($request->string('status')->toString() === 'canceled', fn ($q) => $q->where('status', 'canceled'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $activeSubs = Subscription::query()
            ->with('plan')
            ->whereIn('status', ['active', 'trialing'])
            ->where(fn ($q) => $q->whereNull('current_period_ends_at')->orWhere('current_period_ends_at', '>', $now))
            ->get();

        $paidSubscriberIds = $activeSubs->pluck('user_id')->unique();

        $stats = [
            'total_users' => User::count(),
            'pro_active' => $paidSubscriberIds->count(),
            // Everyone without an active paid subscription is on Basic (the free default).
            'basic' => max(0, User::count() - $paidSubscriberIds->count()),
            'expiring_soon' => $activeSubs->filter(fn ($s) => $s->current_period_ends_at && $s->current_period_ends_at->lte($now->copy()->addDays(30)))->count(),
            'pending_requests' => PlanUpgradeRequest::where('status', 'pending')->count(),
            'active_value' => (int) $activeSubs->sum(fn ($s) => $s->plan->price_cents),
            'currency' => $activeSubs->first()?->plan->currency ?? 'IQD',
        ];

        return view('admin.subscriptions.index', [
            'subscriptions' => $subscriptions,
            'stats' => $stats,
            'plans' => SubscriptionPlan::orderBy('sort_order')->get(),
        ]);
    }

    public function requests(Request $request): View
    {
        $status = in_array($request->string('status')->toString(), ['pending', 'approved', 'rejected'], true)
            ? $request->string('status')->toString()
            : 'pending';

        $requests = PlanUpgradeRequest::with(['user', 'plan'])
            ->where('status', $status)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.subscriptions.requests', [
            'requests' => $requests,
            'status' => $status,
            'pendingCount' => PlanUpgradeRequest::where('status', 'pending')->count(),
        ]);
    }

    public function approve(Request $request, PlanUpgradeRequest $upgradeRequest): RedirectResponse
    {
        abort_unless($upgradeRequest->status === 'pending', 409, 'تمت معالجة هذا الطلب مسبقًا.');

        $this->manager->approveUpgrade($upgradeRequest, $request->user('admin')->id);

        return back()->with('status', 'تمت الموافقة على الطلب وتفعيل الباقة للمستخدم.');
    }

    public function reject(Request $request, PlanUpgradeRequest $upgradeRequest): RedirectResponse
    {
        abort_unless($upgradeRequest->status === 'pending', 409, 'تمت معالجة هذا الطلب مسبقًا.');

        $this->manager->rejectUpgrade($upgradeRequest, $request->user('admin')->id);

        return back()->with('status', 'تم رفض طلب الترقية.');
    }

    public function grant(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'plan' => ['required', 'exists:subscription_plans,slug'],
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        $plan = SubscriptionPlan::where('slug', $data['plan'])->firstOrFail();

        if ($plan->isFree()) {
            return back()->withErrors(['plan' => 'الباقة الأساسية هي الافتراضية ولا تحتاج إلى منح.']);
        }

        $this->manager->grant($user, $plan, $request->user('admin')->id, isset($data['days']) ? (int) $data['days'] : null);

        return back()->with('status', 'تم تفعيل الباقة "'.$plan->name_ar.'" للمستخدم.');
    }

    public function cancel(Request $request, Subscription $subscription): RedirectResponse
    {
        $this->manager->cancel($subscription, $request->user('admin')->id);

        return back()->with('status', 'تم إلغاء الاشتراك. يعود المستخدم إلى الباقة الأساسية فورًا.');
    }
}
