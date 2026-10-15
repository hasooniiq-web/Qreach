<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SubscriptionManager;
use App\Support\NumberInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Plan price/description and — the part that matters for permissions — which
 * contact-detail types each plan may show publicly. Messaging is always on
 * (it's the baseline every plan has), so it isn't editable.
 */
class PlanController extends Controller
{
    public function index(): View
    {
        $plans = SubscriptionPlan::orderBy('sort_order')->get();

        $subscriberCounts = Subscription::query()
            ->whereIn('status', ['active', 'trialing'])
            ->where(fn ($q) => $q->whereNull('current_period_ends_at')->orWhere('current_period_ends_at', '>', now()))
            ->selectRaw('plan_id, count(distinct user_id) as total')
            ->groupBy('plan_id')
            ->pluck('total', 'plan_id');

        $trial = [
            'days' => app(SubscriptionManager::class)->trialDays(),
            'used' => User::whereNotNull('plus_trial_used_at')->count(),
            'running' => Subscription::where('status', 'trialing')->where('current_period_ends_at', '>', now())->count(),
        ];

        return view('admin.subscriptions.plans', compact('plans', 'subscriberCounts', 'trial'));
    }

    /** The length of the one-time free trial of Plus, in days (0 switches the trial off). */
    public function updateTrial(Request $request): RedirectResponse
    {
        $request->merge(['trial_days' => NumberInput::clean($request->input('trial_days'))]);

        $data = $request->validate([
            'trial_days' => ['required', 'integer', 'min:0', 'max:90'],
        ], [], ['trial_days' => 'مدة التجربة المجانية']);

        $before = (int) SystemSetting::get('plus_trial_days', 7);
        $days = (int) $data['trial_days'];

        SystemSetting::set('plus_trial_days', $days, $request->user('admin')->id);

        AuditLogger::log('admin.trial_days_updated', 'admin', $request->user('admin')->id, 'SystemSetting', null, 'success', [
            'before' => $before, 'after' => $days,
        ], $request);

        return back()->with('status', $days === 0
            ? 'أُوقفت التجربة المجانية. لن تظهر لأي عميل جديد.'
            : "صارت التجربة المجانية لباقة بلس {$days} يومًا. تسري على من يبدأها من الآن.");
    }
    public function update(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'name_ckb' => ['nullable', 'string', 'max:100'],
            'description_ar' => ['nullable', 'string', 'max:255'],
            'description_en' => ['nullable', 'string', 'max:255'],
            'description_ckb' => ['nullable', 'string', 'max:255'],
            'price_cents' => ['required', 'integer', 'min:0', 'max:100000000'],
            'billing_period_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'is_active' => ['nullable', 'boolean'],
            'public_contacts' => ['nullable', 'array'],
            'public_contacts.*' => [Rule::in(SubscriptionPlan::PUBLIC_CONTACT_TYPES)],
        ]);

        // The free default must stay active and free — every customer falls back to it.
        if ($plan->slug === SubscriptionPlan::BASIC) {
            $data['price_cents'] = 0;
            $data['is_active'] = true;
        }

        $before = $plan->only(['price_cents', 'billing_period_days', 'features']);

        $plan->update([
            'name_ar' => $data['name_ar'],
            // An empty language falls back to Arabic in the app, so a blank is stored as null.
            'name_en' => $data['name_en'] ?? null,
            'name_ckb' => $data['name_ckb'] ?? null,
            'description_ar' => $data['description_ar'] ?? null,
            'description_en' => $data['description_en'] ?? null,
            'description_ckb' => $data['description_ckb'] ?? null,
            'price_cents' => $data['price_cents'],
            'billing_period_days' => $data['billing_period_days'],
            'is_active' => (bool) ($data['is_active'] ?? false),
            'features' => [
                'messaging' => true,
                'emergency_contact' => $plan->allowsEmergencyContact(),
                // Any platform beyond the phone number means the plan may create extra methods.
                'extra_contact_methods' => array_diff($data['public_contacts'] ?? [], ['phone']) !== [],
                'public_contacts' => array_values($data['public_contacts'] ?? []),
            ],
        ]);

        AuditLogger::log('admin.plan_updated', 'admin', $request->user('admin')->id, 'SubscriptionPlan', $plan->id, 'success', [
            'before' => $before, 'after' => $plan->only(['price_cents', 'billing_period_days', 'features']),
        ], $request);

        return back()->with('status', 'تم حفظ الباقة "'.$plan->name_ar.'".');
    }
}
