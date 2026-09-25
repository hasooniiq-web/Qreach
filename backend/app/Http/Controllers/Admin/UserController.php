<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\PlanEntitlements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(fn ($q) => $q->where('phone', 'like', $term)->orWhere('name', 'like', $term));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->with('subscriptions.plan')
            ->withCount('vehicles')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user, PlanEntitlements $plans): View
    {
        $user->load(['profile', 'contactMethods', 'vehicles.tags', 'devices', 'subscriptions.plan']);

        return view('admin.users.show', [
            'user' => $user,
            'plan' => $plans->planFor($user),
            'activeSubscription' => $plans->activeSubscription($user),
            'paidPlans' => SubscriptionPlan::where('is_active', true)->where('price_cents', '>', 0)->orderBy('sort_order')->get(),
        ]);
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $user->update(['status' => 'suspended']);

        AuditLogger::log('admin.user_suspended', 'admin', $request->user('admin')->id, 'User', $user->id, 'success', [], $request);

        return back()->with('status', 'تم إيقاف حساب المستخدم.');
    }

    public function activate(Request $request, User $user): RedirectResponse
    {
        $user->update(['status' => 'active']);

        AuditLogger::log('admin.user_activated', 'admin', $request->user('admin')->id, 'User', $user->id, 'success', [], $request);

        return back()->with('status', 'تم تفعيل حساب المستخدم.');
    }
}
