@props(['status'])

@php
    $map = [
        'active' => ['ok', 'نشط'],
        'inactive' => ['muted', 'غير نشط'],
        'archived' => ['muted', 'مؤرشف'],
        'suspended' => ['bad', 'موقوف'],
        'deleted' => ['bad', 'محذوف'],
        'generated' => ['muted', 'أُنشئ'],
        'printing' => ['accent', 'أُرسل للطباعة'],
        'available' => ['muted', 'غير مُعيّن'],
        'unassigned' => ['muted', 'غير مُعيّن'],
        'activated' => ['accent', 'مفعّل'],
        'assigned' => ['ok', 'مُعيّن'],
        'revoked' => ['bad', 'مُلغى'],
        'replaced' => ['muted', 'استُبدل'],
        'disabled' => ['muted', 'معطل'],
        // Printing batch statuses (tag_batches.status)
        'pending' => ['muted', 'قيد الإنشاء'],
        'printed' => ['accent', 'أُرسل للطباعة'],
        'completed' => ['ok', 'مكتملة'],
        'exported' => ['muted', 'صُدّرت'],
        'new' => ['accent', 'جديد'],
        'read' => ['muted', 'مقروء'],
        'responded' => ['ok', 'تم الرد'],
        'closed' => ['muted', 'مغلق'],
        'critical' => ['bad', 'حرج'],
        'warning' => ['accent', 'تحذير'],
        'info' => ['muted', 'معلومات'],
        'success' => ['ok', 'نجاح'],
        'failure' => ['bad', 'فشل'],
        'resolved' => ['ok', 'تم الحل'],
        'expired' => ['muted', 'منتهي'],
        'canceled' => ['bad', 'ملغى'],
        'trialing' => ['accent', 'تجريبي'],
        'past_due' => ['bad', 'متأخر السداد'],
        'unresolved' => ['muted', 'قيد المراجعة'],
        'grace' => ['accent', 'فترة سماح'],
    ];
    [$classes, $label] = $map[$status] ?? ['muted', $status];
@endphp

@php
    $tone = [
        'ok' => 'bg-status-success/10 text-status-success ring-status-success/25',
        'bad' => 'bg-status-danger/10 text-status-danger ring-status-danger/25',
        'accent' => 'bg-brand-yellow/15 text-ink-900 ring-brand-yellow/50',
        'muted' => 'bg-ink-100 text-ink-600 ring-ink-200',
    ][$classes] ?? 'bg-ink-100 text-ink-600 ring-ink-200';
@endphp

<span class="badge {{ $tone }}">{{ $label }}</span>