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
            'users' => ['view', 'update', 'suspend'],
            'vehicles' => ['view', 'update', 'delete'],
            'qr' => ['view', 'generate_batch', 'activate_override', 'revoke', 'replace', 'export'],
            'nfc' => ['view', 'provision', 'revoke', 'replace'],
            'contact_requests' => ['view', 'respond'],
            'emergency' => ['view', 'resolve'],
            'notifications' => ['view', 'send'],
            'reports' => ['view', 'export'],
            'analytics' => ['view'],
            'security' => ['view', 'resolve'],
            'audit' => ['view'],
            'admins' => ['view', 'create', 'update', 'delete'],
            'roles' => ['view', 'create', 'update', 'delete'],
            'settings' => ['view', 'update'],
        ];

        $groupNamesAr = [
            'users' => 'المستخدمون', 'vehicles' => 'المركبات', 'qr' => 'رموز QR',
            'nfc' => 'رموز NFC', 'contact_requests' => 'طلبات التواصل', 'emergency' => 'التنبيهات الطارئة',
            'notifications' => 'الإشعارات', 'reports' => 'التقارير', 'analytics' => 'التحليلات',
            'security' => 'الأمان', 'audit' => 'سجلات التدقيق', 'admins' => 'المسؤولون',
            'roles' => 'الأدوار والصلاحيات', 'settings' => 'الإعدادات',
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
            Permission::whereIn('group', ['security', 'audit', 'admins', 'roles', 'emergency'])
                ->orWhere('key', 'users.view')
                ->pluck('id')
        );

        $operationsAdmin = Role::updateOrCreate(
            ['slug' => 'operations_admin'],
            ['name_ar' => 'مدير العمليات', 'name_en' => 'Operations Admin', 'is_system' => true]
        );
        $operationsAdmin->permissions()->sync(
            Permission::whereIn('group', ['vehicles', 'qr', 'nfc', 'contact_requests', 'notifications'])
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
                'contact_requests.view', 'contact_requests.respond', 'emergency.view',
            ])->pluck('id')
        );

        $reportsAnalyst = Role::updateOrCreate(
            ['slug' => 'reports_analyst'],
            ['name_ar' => 'محلل تقارير', 'name_en' => 'Reports Analyst', 'is_system' => true]
        );
        $reportsAnalyst->permissions()->sync(
            Permission::whereIn('group', ['reports', 'analytics'])->pluck('id')
        );
    }
}
