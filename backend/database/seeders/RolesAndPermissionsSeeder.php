<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Section 20 roles + a granular permission set. Super Admin gets every
 * permission; the rest are scoped narrowly by responsibility so the "default
 * deny" principle holds even for privileged staff.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            // 'create'/'delete' are seeded for spec completeness even though no
            // admin-facing "create a customer" or "hard-delete a user" screen
            // exists yet (customers self-register via OTP) — see the
            // implementation report.
            'users' => ['view', 'create', 'update', 'delete', 'suspend'],
            'vehicles' => ['view', 'create', 'update', 'delete'],
            'qr' => ['view', 'generate_batch', 'activate_override', 'assign', 'revoke', 'cancel', 'replace', 'export'],
            'nfc' => ['view', 'provision', 'assign', 'revoke', 'cancel', 'replace'],
            'printing' => ['view', 'create', 'export'],
            'contact_requests' => ['view', 'respond'],
            'emergency' => ['view', 'resolve'],
            'notifications' => ['view', 'send'],
            'reports' => ['view', 'export'],
            'analytics' => ['view'],
            'security' => ['view', 'resolve'],
            'audit' => ['view'],
            'audit_logs' => ['view'],
            'admins' => ['view', 'create', 'update', 'delete'],
            'roles' => ['view', 'create', 'update', 'delete'],
            'permissions' => ['view'],
            'settings' => ['view', 'update'],
            'subscriptions' => ['view', 'manage'],
            'payments' => ['view', 'refund'],
            'payment_gateways' => ['view', 'manage'],
            'trade' => ['view', 'manage'],
            'vouchers' => ['view', 'create', 'export', 'revoke'],
        ];

        $groupNamesAr = [
            'users' => 'المستخدمون', 'vehicles' => 'المركبات', 'qr' => 'رموز QR',
            'nfc' => 'رموز NFC', 'printing' => 'الطباعة', 'contact_requests' => 'طلبات التواصل', 'emergency' => 'التنبيهات الطارئة',
            'notifications' => 'الإشعارات', 'reports' => 'التقارير', 'analytics' => 'التحليلات',
            'security' => 'الأمان', 'audit' => 'سجلات التدقيق', 'audit_logs' => 'سجلات التدقيق', 'admins' => 'المسؤولون',
            'roles' => 'الأدوار والصلاحيات', 'permissions' => 'الصلاحيات', 'settings' => 'الإعدادات', 'subscriptions' => 'الاشتراكات',
            'payments' => 'المدفوعات', 'payment_gateways' => 'بوابات الدفع', 'trade' => 'المخزون والحسابات', 'vouchers' => 'قسائم التفعيل',
        ];

        $permissions = [];
        foreach ($groups as $group => $actions) {
            foreach ($actions as $action) {
                $permissions[] = Permission::updateOrCreate(
                    ['key' => "{$group}.{$action}"],
                    [
                        'group' => $group,
                        'name_ar' => $groupNamesAr[$group].' - '.$action,
                        'name_en' => "{$group}.{$action}",
                    ]
                );
            }
        }

        $superAdmin = Role::updateOrCreate(
            ['slug' => 'super_admin'],
            ['name_ar' => 'مدير عام', 'name_en' => 'Super Admin', 'is_system' => true]
        );
        $superAdmin->permissions()->sync(collect($permissions)->pluck('id'));

        $securityAdmin = Role::updateOrCreate(
            ['slug' => 'security_admin'],
            ['name_ar' => 'مدير الأمان', 'name_en' => 'Security Admin', 'is_system' => true]
        );
        $securityAdmin->permissions()->sync(
            Permission::whereIn('group', ['security', 'audit', 'audit_logs', 'admins', 'roles', 'permissions', 'emergency'])
                ->orWhere('key', 'users.view')
                ->pluck('id')
        );

        $operationsAdmin = Role::updateOrCreate(
            ['slug' => 'operations_admin'],
            ['name_ar' => 'مدير العمليات', 'name_en' => 'Operations Admin', 'is_system' => true]
        );
        $operationsAdmin->permissions()->sync(
            Permission::whereIn('group', ['vehicles', 'qr', 'nfc', 'printing', 'contact_requests', 'notifications'])
                ->orWhere('key', 'users.view')
                ->pluck('id')
        );

        $supportAdmin = Role::updateOrCreate(
            ['slug' => 'support_admin'],
            ['name_ar' => 'مسؤول الدعم', 'name_en' => 'Support Admin', 'is_system' => true]
        );
        $supportAdmin->permissions()->sync(
            Permission::whereIn('key', [
                'users.view', 'vehicles.view', 'qr.view', 'nfc.view',
                'contact_requests.view', 'contact_requests.respond', 'emergency.view', 'subscriptions.view',
            ])->pluck('id')
        );

        $reportsAnalyst = Role::updateOrCreate(
            ['slug' => 'reports_analyst'],
            ['name_ar' => 'محلل تقارير', 'name_en' => 'Reports Analyst', 'is_system' => true]
        );
        $reportsAnalyst->permissions()->sync(
            Permission::whereIn('group', ['reports', 'analytics'])->pluck('id')
        );

        // Spec's suggested default roles, added alongside the ones above rather
        // than replacing them (renaming an existing role would silently change
        // what every admin currently holding it can do).
        $manager = Role::updateOrCreate(
            ['slug' => 'manager'],
            ['name_ar' => 'مدير', 'name_en' => 'Manager', 'is_system' => true,
                'description' => 'يدير المستخدمين والمركبات والرموز والطباعة، دون صلاحيات أمنية على مستوى النظام.']
        );
        $manager->permissions()->sync(
            Permission::whereIn('group', ['users', 'vehicles', 'qr', 'nfc', 'printing', 'contact_requests', 'emergency', 'subscriptions', 'reports', 'analytics'])
                ->pluck('id')
        );

        $operator = Role::updateOrCreate(
            ['slug' => 'operator'],
            ['name_ar' => 'مُشغّل', 'name_en' => 'Operator', 'is_system' => true,
                'description' => 'يدير المستخدمين والمركبات وعمليات الربط والاستبدال.']
        );
        $operator->permissions()->sync(
            Permission::whereIn('key', [
                'users.view', 'vehicles.view', 'vehicles.update',
                'qr.view', 'qr.assign', 'qr.replace', 'nfc.view', 'nfc.assign', 'nfc.replace',
                'contact_requests.view', 'contact_requests.respond',
            ])->pluck('id')
        );

        $printer = Role::updateOrCreate(
            ['slug' => 'printer'],
            ['name_ar' => 'مسؤول الطباعة', 'name_en' => 'Printer', 'is_system' => true,
                'description' => 'يصل إلى دفعات الطباعة وتصدير الرموز فقط، دون بيانات المستخدمين الحساسة.']
        );
        $printer->permissions()->sync(
            Permission::whereIn('group', ['printing'])->orWhereIn('key', ['qr.view', 'qr.generate_batch', 'qr.export', 'nfc.view'])->pluck('id')
        );

        $viewer = Role::updateOrCreate(
            ['slug' => 'viewer'],
            ['name_ar' => 'مُشاهد', 'name_en' => 'Viewer', 'is_system' => true, 'description' => 'اطّلاع فقط، دون أي إجراء.']
        );
        $viewer->permissions()->sync(
            Permission::where('key', 'like', '%.view')->pluck('id')
        );
    }
}
