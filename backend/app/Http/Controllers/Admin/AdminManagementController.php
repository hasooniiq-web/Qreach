<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Models\Admin;
use App\Models\Role;
use App\Services\AuditLogger;
use App\Services\SecurityEventLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Section 20: admins and roles. Scope decision (documented in
 * docs/ARCHITECTURE_DECISIONS.md): this manages which of the seeded
 * system roles an admin holds, not a full custom role/permission builder —
 * the spec's example roles are fixed "is_system" roles seeded up front,
 * and building a generic permission-matrix editor was judged lower value
 * than the rest of the platform for this pass.
 */
class AdminManagementController extends Controller
{
    use \App\Http\Controllers\Concerns\ExportsTables;

    public function index(Request $request): View|\Illuminate\Http\Response
    {
        $query = Admin::with('roles')->latest();

        if ($export = $this->exportRows($request, 'admins', 'المسؤولون', ['الاسم', 'البريد الإلكتروني', 'الأدوار', 'الحالة'], $query,
            fn ($a) => [
                (string) $a->name,
                (string) $a->email,
                $a->roles->pluck('name_ar')->implode('، ') ?: '—',
                \App\Support\Export\Labels::status($a->status),
            ])) {
            return $export;
        }

        $admins = $query->paginate(20);

        return view('admin.admins.index', compact('admins'));
    }

    public function create(): View
    {
        $roles = Role::orderBy('name_ar')->get();

        return view('admin.admins.create', compact('roles'));
    }

    public function store(StoreAdminRequest $request): RedirectResponse
    {
        $mustChange = $request->boolean('must_change_password');

        $admin = DB::transaction(function () use ($request, $mustChange) {
            $admin = Admin::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => $request->input('password'),
                'status' => 'active',
                'must_change_password' => $mustChange,
            ]);

            $admin->roles()->sync($request->input('roles'));

            return $admin;
        });

        AuditLogger::log(
            'admin.created', 'admin', $request->user('admin')->id, 'Admin', $admin->id,
            'success', ['roles' => $request->input('roles')], $request
        );
        SecurityEventLogger::log(
            SecurityEventLogger::PRIVILEGE_CHANGE, 'warning', 'admin', $request->user('admin')->id,
            ['created_admin_id' => $admin->id], $request
        );

        return redirect()->route('admin.admins.index')
            ->with('status', 'تم إنشاء المسؤول بنجاح.'.($mustChange ? ' سيُطلب منه تغيير كلمة المرور عند أول تسجيل دخول.' : ''));
    }

    public function edit(Admin $admin): View
    {
        $roles = Role::orderBy('name_ar')->get();
        $admin->load('roles');

        return view('admin.admins.edit', compact('admin', 'roles'));
    }

    public function update(Request $request, Admin $admin): RedirectResponse
    {
        $request->validate(['roles' => ['required', 'array', 'min:1'], 'roles.*' => ['exists:roles,id']]);

        $actingAdmin = $request->user('admin');

        if ($admin->id === $actingAdmin->id && ! in_array(Role::where('slug', 'super_admin')->value('id'), $request->input('roles'))) {
            return back()->withErrors(['roles' => 'لا يمكنك إزالة صلاحية المدير العام عن نفسك.']);
        }

        $admin->roles()->sync($request->input('roles'));

        AuditLogger::log(
            'admin.roles_updated', 'admin', $actingAdmin->id, 'Admin', $admin->id,
            'success', ['roles' => $request->input('roles')], $request
        );
        SecurityEventLogger::log(
            SecurityEventLogger::PRIVILEGE_CHANGE, 'warning', 'admin', $actingAdmin->id,
            ['target_admin_id' => $admin->id], $request
        );

        return redirect()->route('admin.admins.index')->with('status', 'تم تحديث صلاحيات المسؤول.');
    }

    /**
     * For an admin who lost their phone: removes the authenticator so the next
     * sign-in forces a fresh enrollment (password still required first).
     */
    public function resetTotp(Request $request, Admin $admin): RedirectResponse
    {
        if ($admin->id === $request->user('admin')->id) {
            return back()->withErrors(['admin' => 'لا يمكنك إعادة ضبط التحقق بخطوتين لحسابك من هنا.']);
        }

        $admin->forceFill(['totp_secret' => null, 'totp_enabled' => false])->save();

        AuditLogger::log('admin.totp_reset', 'admin', $request->user('admin')->id, 'Admin', $admin->id, 'success', [], $request);
        SecurityEventLogger::log(
            SecurityEventLogger::PRIVILEGE_CHANGE, 'warning', 'admin', $request->user('admin')->id,
            ['target_admin_id' => $admin->id, 'change' => 'totp_reset'], $request
        );

        return back()->with('status', 'أُعيد ضبط التحقق بخطوتين. سيُطلب من المسؤول تفعيله من جديد عند دخوله التالي.');
    }

    public function suspend(Request $request, Admin $admin): RedirectResponse
    {
        if ($admin->id === $request->user('admin')->id) {
            return back()->withErrors(['admin' => 'لا يمكنك إيقاف حسابك الخاص.']);
        }

        $admin->update(['status' => 'suspended']);

        AuditLogger::log('admin.suspended', 'admin', $request->user('admin')->id, 'Admin', $admin->id, 'success', [], $request);

        return back()->with('status', 'تم إيقاف حساب المسؤول.');
    }

    public function activate(Request $request, Admin $admin): RedirectResponse
    {
        $admin->update(['status' => 'active']);

        AuditLogger::log('admin.activated', 'admin', $request->user('admin')->id, 'Admin', $admin->id, 'success', [], $request);

        return back()->with('status', 'تم تفعيل حساب المسؤول.');
    }
}
