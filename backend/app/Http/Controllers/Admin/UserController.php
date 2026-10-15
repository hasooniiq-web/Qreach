<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ContactRequest;
use App\Models\SubscriptionPlan;
use App\Models\TagLifecycleEvent;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\PlanEntitlements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    use \App\Http\Controllers\Concerns\ExportsTables;

    /** @var array<string, string> */
    private const AUDIT_LABELS = [
        'admin.user_suspended' => 'أوقف مسؤول الحساب', 'admin.user_activated' => 'أعاد مسؤول تفعيل الحساب',
        'profile.name_updated' => 'حدّث الاسم', 'profile.avatar_updated' => 'غيّر الصورة',
        'profile.contact_updated' => 'حدّث بيانات التواصل', 'profile.visibility_updated' => 'غيّر ما يظهر للزوار',
        'profile.method_added' => 'أضاف طريقة تواصل', 'profile.emergency_updated' => 'حدّث جهة اتصال الطوارئ',
    ];

    /** @var array<string, string> */
    private const STATUS_LABELS = [
        'generated' => 'أُنشئ', 'printing' => 'أُرسل للطباعة', 'unassigned' => 'أصبح غير مُعيّن', 'available' => 'أصبح غير مُعيّن',
        'assigned' => 'رُبط بالمركبة', 'replaced' => 'استُبدل', 'revoked' => 'أُلغي',
    ];

    public function index(Request $request): View|\Illuminate\Http\Response
    {
        $users = User::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                // A number is matched on its national digits: 07727009040, 7727009040 and +964… are one.
                $phone = \App\Support\PhoneSearch::pattern($request->string('q')->toString());
                $query->where(fn ($q) => $q->where('phone', 'like', $phone ?? $term)->orWhere('name', 'like', $term));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->with('subscriptions.plan')
            ->withCount('vehicles')
            ->latest();

        if ($export = $this->exportRows($request, 'users', 'المستخدمون', ['المستخدم', 'رقم الهاتف', 'الباقة', 'المركبات', 'الحالة', 'تاريخ التسجيل'], $users,
            fn ($u) => [
                $u->display_name,
                (string) $u->phone,
                $u->subscriptions->first(fn ($s) => $s->isCurrentlyActive())?->plan?->name_ar ?? 'الأساسية',
                (int) $u->vehicles_count,
                \App\Support\Export\Labels::status($u->status),
                \App\Support\Export\Labels::date($u->created_at),
            ],
            filters: $this->filterLines($request, ['q' => 'بحث', 'status' => 'الحالة'], ['status' => ['active' => 'نشط', 'suspended' => 'موقوف']]))) {
            return $export;
        }

        $users = $users->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user, PlanEntitlements $plans): View
    {
        $user->load(['profile', 'contactMethods', 'vehicles.tags.lifecycleEvents', 'devices', 'subscriptions.plan']);

        $vehicleIds = $user->vehicles->pluck('id');
        $tagIds = $user->vehicles->flatMap(fn ($v) => $v->tags->pluck('id'));

        // A single, merged "what happened to this account" timeline: general
        // admin/audit actions on the user, plus every QR/NFC lifecycle event
        // for their vehicles' tags (spec: "User 360", not several disconnected pages).
        $timeline = AuditLog::where('resource_type', 'User')->where('resource_id', $user->id)
            ->get()->map(fn ($log) => [
                'at' => $log->created_at,
                'text' => self::AUDIT_LABELS[$log->action] ?? $log->action,
            ])
            ->concat(
                TagLifecycleEvent::whereIn('tag_id', $tagIds)->orWhereIn('vehicle_id', $vehicleIds)
                    ->with('tag')->get()->map(fn ($event) => [
                        'at' => $event->created_at,
                        'text' => 'رمز '.$event->tag?->serial_number.': '.
                            (self::STATUS_LABELS[$event->new_status] ?? $event->new_status).
                            ($event->reason ? ' — '.$event->reason : ''),
                    ])
            )
            ->concat(
                ContactRequest::whereIn('vehicle_id', $vehicleIds)->get()->map(fn ($c) => [
                    'at' => $c->created_at, 'text' => 'رسالة زائر جديدة على مركبة',
                ])
            )
            ->push(['at' => $user->created_at, 'text' => 'إنشاء الحساب'])
            ->sortByDesc('at')->values();

        return view('admin.users.show', [
            'user' => $user,
            'plan' => $plans->planFor($user),
            'activeSubscription' => $plans->activeSubscription($user),
            'paidPlans' => SubscriptionPlan::where('is_active', true)->where('price_cents', '>', 0)->orderBy('sort_order')->get(),
            'timeline' => $timeline,
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
