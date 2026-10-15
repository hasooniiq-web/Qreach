<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = ['key', 'group', 'name_ar', 'name_en'];

    public const GROUP_LABELS = [
        'users' => 'المستخدمون', 'vehicles' => 'المركبات', 'qr' => 'رموز QR', 'nfc' => 'رموز NFC',
        'printing' => 'الطباعة', 'contact_requests' => 'طلبات التواصل', 'emergency' => 'التنبيهات الطارئة',
        'notifications' => 'الإشعارات', 'reports' => 'التقارير', 'analytics' => 'التحليلات', 'security' => 'الأمان',
        'audit' => 'سجلات التدقيق', 'audit_logs' => 'سجلات التدقيق', 'admins' => 'المسؤولون',
        'roles' => 'الأدوار', 'permissions' => 'الصلاحيات', 'settings' => 'الإعدادات', 'subscriptions' => 'الاشتراكات',
        'payments' => 'المدفوعات', 'payment_gateways' => 'بوابات الدفع', 'trade' => 'المخزون والحسابات',
        'vouchers' => 'قسائم التفعيل',
    ];

    public const ACTION_LABELS = [
        'view' => 'عرض', 'create' => 'إنشاء', 'update' => 'تعديل', 'delete' => 'حذف', 'suspend' => 'إيقاف',
        'generate_batch' => 'إنشاء دفعة', 'activate_override' => 'تفعيل استثنائي', 'assign' => 'ربط',
        'revoke' => 'إلغاء الربط', 'cancel' => 'إلغاء', 'replace' => 'استبدال', 'export' => 'تصدير',
        'provision' => 'تجهيز', 'respond' => 'الرد', 'resolve' => 'إغلاق', 'send' => 'إرسال',
        'manage' => 'إدارة', 'refund' => 'استرداد',
    ];

    /** The Arabic heading of a group of permissions. */
    public static function groupLabel(string $group): string
    {
        return self::GROUP_LABELS[$group] ?? $group;
    }

    /** "رموز NFC — ربط": every part in Arabic, whatever name_ar was seeded with. */
    public function getLabelAttribute(): string
    {
        [$group, $action] = array_pad(explode('.', (string) $this->key, 2), 2, '');

        return self::groupLabel($group).' — '.(self::ACTION_LABELS[$action] ?? $action);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }
}
