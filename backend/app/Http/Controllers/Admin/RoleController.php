<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Custom roles (spec: "administrators must be able to create custom roles and
 * assign individual permissions without changing source code"). The seeded
 * roles (Super Admin, etc.) are marked `is_system` and cannot be renamed or
 * deleted here, but their permissions can still be adjusted — everything
 * else is a normal role an admin creates and edits freely.
 */
class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::withCount(['admins', 'permissions'])->orderBy('name_ar')->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('admin.roles.create', ['permissionGroups' => $this->permissionGroups()]);
    }

    public function store(Request $request): RedirectResponse
    {

        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = Role::create([
            // The internal key is made by the system; nobody is asked to write one.
            'slug' => 'role_'.Str::lower(Str::random(10)),
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_ar'],
            'description' => $data['description'] ?? null,
            'is_system' => false,
        ]);
        $role->permissions()->sync($data['permissions'] ?? []);

        AuditLogger::log('admin.role_created', 'admin', $request->user('admin')->id, 'Role', $role->id, 'success', ['name' => $role->name_ar], $request);

        return redirect()->route('admin.roles.index')->with('status', 'تم إنشاء الدور بنجاح.');
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.edit', ['role' => $role, 'permissionGroups' => $this->permissionGroups(), 'currentIds' => $role->permissions->pluck('id')->all()]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $before = $role->permissions->pluck('key')->all();

        // A system role's name is fixed (it is referenced by slug in seeders and
        // docs); its permission set can still be tuned by an administrator.
        if (! $role->is_system) {
            $role->update(['name_ar' => $data['name_ar'], 'description' => $data['description'] ?? null]);
        }
        $role->permissions()->sync($data['permissions'] ?? []);

        AuditLogger::log('admin.role_updated', 'admin', $request->user('admin')->id, 'Role', $role->id, 'success', [
            'before' => $before, 'after' => $role->fresh()->permissions->pluck('key')->all(),
        ], $request);

        return back()->with('status', 'تم حفظ الدور.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return back()->withErrors(['role' => 'لا يمكن حذف دور أساسي في النظام.']);
        }
        if ($role->admins()->exists()) {
            return back()->withErrors(['role' => 'لا يمكن حذف دور مُسند إلى مسؤولين. أزل الدور منهم أولًا.']);
        }

        $role->delete();
        AuditLogger::log('admin.role_deleted', 'admin', $request->user('admin')->id, 'Role', $role->id, 'success', ['name' => $role->name_en], $request);

        return redirect()->route('admin.roles.index')->with('status', 'تم حذف الدور.');
    }

    /** @return Collection<string, Collection<int, Permission>> */
    private function permissionGroups()
    {
        return Permission::orderBy('key')->get()->groupBy('group');
    }
}
