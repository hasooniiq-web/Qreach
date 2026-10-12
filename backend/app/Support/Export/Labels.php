<?php

namespace App\Support\Export;

/** Arabic labels for the status words that appear in exported tables (same wording as the dashboard badges). */
final class Labels
{
    private const STATUS = [
        'active' => 'نشط', 'inactive' => 'غير نشط', 'archived' => 'مؤرشف', 'suspended' => 'موقوف', 'deleted' => 'محذوف',
        'generated' => 'أُنشئ', 'printing' => 'أُرسل للطباعة', 'available' => 'غير مُعيّن', 'unassigned' => 'غير مُعيّن',
        'activated' => 'مفعّل', 'assigned' => 'مُعيّن', 'revoked' => 'مُلغى', 'replaced' => 'استُبدل', 'disabled' => 'معطل',
        'pending' => 'قيد الإنشاء', 'printed' => 'أُرسل للطباعة', 'completed' => 'مكتملة', 'exported' => 'صُدّرت',
        'new' => 'جديد', 'read' => 'مقروء', 'responded' => 'تم الرد', 'closed' => 'مغلق',
        'critical' => 'حرج', 'warning' => 'تحذير', 'info' => 'معلومات', 'success' => 'نجاح', 'failure' => 'فشل',
        'resolved' => 'تم الحل', 'unresolved' => 'قيد المراجعة', 'expired' => 'منتهي', 'canceled' => 'ملغى',
        'trialing' => 'تجريبي', 'past_due' => 'متأخر السداد', 'grace' => 'فترة سماح',
        'succeeded' => 'ناجحة', 'failed' => 'فاشلة', 'refunded' => 'مستردّة', 'partially_refunded' => 'مستردّة جزئيًا',
        'approved' => 'تمت الموافقة', 'rejected' => 'مرفوضة',
    ];

    public static function status(?string $status): string
    {
        return $status === null || $status === '' ? '—' : (self::STATUS[$status] ?? $status);
    }

    public static function date(?\DateTimeInterface $date, bool $time = false): string
    {
        if ($date === null) {
            return '—';
        }

        return \Illuminate\Support\Carbon::instance($date)->timezone(config('app.display_timezone'))->format($time ? 'Y-m-d H:i' : 'Y-m-d');
    }
}
