@props(['status'])

@php
    $map = [
        'active' => ['bg-status-success/10 text-status-success', 'نشط'],
        'inactive' => ['bg-ink-100 text-ink-500', 'غير نشط'],
        'archived' => ['bg-ink-100 text-ink-500', 'مؤرشف'],
        'suspended' => ['bg-status-danger/10 text-status-danger', 'موقوف'],
        'deleted' => ['bg-status-danger/10 text-status-danger', 'محذوف'],
        'generated' => ['bg-ink-100 text-ink-500', 'مُنشأ'],
        'available' => ['bg-ink-100 text-ink-600', 'متاح'],
        'activated' => ['bg-brand-yellow/20 text-ink-900', 'مفعّل'],
        'assigned' => ['bg-status-success/10 text-status-success', 'مربوط'],
        'revoked' => ['bg-status-danger/10 text-status-danger', 'ملغى'],
        'replaced' => ['bg-ink-100 text-ink-500', 'مستبدل'],
        'disabled' => ['bg-ink-100 text-ink-500', 'معطل'],
        'new' => ['bg-brand-yellow/20 text-ink-900', 'جديد'],
        'read' => ['bg-ink-100 text-ink-600', 'مقروء'],
        'responded' => ['bg-status-success/10 text-status-success', 'تم الرد'],
        'closed' => ['bg-ink-100 text-ink-500', 'مغلق'],
        'critical' => ['bg-status-danger/10 text-status-danger', 'حرج'],
        'warning' => ['bg-brand-yellow/20 text-ink-900', 'تحذير'],
        'info' => ['bg-ink-100 text-ink-500', 'معلومات'],
        'success' => ['bg-status-success/10 text-status-success', 'نجاح'],
        'failure' => ['bg-status-danger/10 text-status-danger', 'فشل'],
        'resolved' => ['bg-status-success/10 text-status-success', 'تم الحل'],
        'expired' => ['bg-ink-100 text-ink-500', 'منتهي'],
        'canceled' => ['bg-status-danger/10 text-status-danger', 'ملغى'],
        'trialing' => ['bg-brand-yellow/20 text-ink-900', 'تجريبي'],
        'past_due' => ['bg-status-danger/10 text-status-danger', 'متأخر السداد'],
        'unresolved' => ['bg-ink-100 text-ink-600', 'قيد المراجعة'],
    ];
    [$classes, $label] = $map[$status] ?? ['bg-ink-100 text-ink-500', $status];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium $classes"]) }}>
    {{ $label }}
</span>
