@php
    // Human-readable Arabic names for the technical event codes. Unknown
    // codes fall back to the raw code rather than being hidden.
    $typeLabels = [
        'AUTH_FAILURE' => 'فشل تسجيل الدخول',
        'AUTH_SUCCESS' => 'تسجيل دخول ناجح',
        'OTP_ABUSE' => 'إساءة استخدام رمز التحقق',
        'ACCOUNT_LOCKED' => 'قفل حساب',
        'SUSPICIOUS_SCAN' => 'عملية مسح مشبوهة',
        'QR_GUESSING' => 'محاولة تخمين رمز QR',
        'RATE_LIMIT_TRIGGERED' => 'تجاوز حد الطلبات',
        'PRIVILEGE_CHANGE' => 'تغيير صلاحيات',
        'MASS_EXPORT' => 'تصدير بيانات مجمّع',
        'ADMIN_SECURITY_EVENT' => 'حدث أمني إداري',
        'SESSION_ANOMALY' => 'نشاط جلسة غير معتاد',
        'TOKEN_ANOMALY' => 'نشاط رمز وصول غير معتاد',
        'UNUSUAL_REQUEST_PATTERN' => 'نمط طلبات غير معتاد',
        'EMERGENCY_TRIGGERED' => 'تنبيه طوارئ',
    ];
    $actorLabels = ['customer' => 'عميل', 'admin' => 'مسؤول', 'system' => 'النظام', 'unknown' => 'زائر / غير معروف'];
@endphp

<x-layouts.admin title="الأحداث الأمنية">
    <p class="mb-5 max-w-3xl text-sm leading-relaxed text-ink-500">
        يسجّل النظام هنا كل ما يخص الأمان (تسجيلات الدخول، محاولات التخمين، تغيير الصلاحيات...). الحدث «غير مُراجَع» ما دام لم يطّلع عليه أحد من الفريق؛
        عند مراجعته والتأكد أنه لا يحتاج إجراءً اضغط «تمت المراجعة» فيُغلق ويخرج من العدّادات أعلاه.
    </p>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-kpi label="أحداث حرجة مفتوحة" :value="$summary['critical_open']" icon="shield" :tone="$summary['critical_open'] > 0 ? 'danger' : 'success'" />
        <x-kpi label="تحذيرات مفتوحة" :value="$summary['warning_open']" icon="shield" />
        <x-kpi label="أحداث آخر 24 ساعة" :value="$summary['last_24h']" icon="audit" />
    </div>

    <x-filter-bar>
        <select name="severity" data-auto-submit aria-label="المستوى" class="form-control w-auto">
            <option value="">كل المستويات</option>
            <option value="critical" @selected(request('severity') === 'critical')>حرج</option>
            <option value="warning" @selected(request('severity') === 'warning')>تحذير</option>
            <option value="info" @selected(request('severity') === 'info')>معلومات</option>
        </select>
        <select name="event_type" data-auto-submit aria-label="النوع" class="form-control w-auto">
            <option value="">كل الأنواع</option>
            @foreach ($eventTypes as $type)
                <option value="{{ $type }}" @selected(request('event_type') === $type)>{{ $typeLabels[$type] ?? $type }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 text-sm text-ink-600">
            <input type="checkbox" name="unresolved_only" value="1" data-auto-submit @checked(request()->boolean('unresolved_only'))
                   class="rounded border-ink-300 text-brand-yellow focus:ring-brand-yellow">
            الأحداث التي لم تُراجَع فقط
        </label>
    </x-filter-bar>

    <x-data-table :headers="['الحدث', 'المستوى', 'الجهة الفاعلة', 'عنوان IP', 'التاريخ', 'الحالة', '']">
        @forelse ($events as $event)
            <tr class="hover:bg-ink-50">
                <td class="px-4 py-3">
                    <p class="font-medium text-ink-900">{{ $typeLabels[$event->event_type] ?? $event->event_type }}</p>
                </td>
                <td class="px-4 py-3"><x-status-badge :status="$event->severity" /></td>
                <td class="px-4 py-3 text-ink-600">{{ $actorLabels[$event->actor_type] ?? $event->actor_type }}</td>
                <td class="px-4 py-3 tabular-nums text-ink-500" dir="ltr">{{ $event->request_ip }}</td>
                <td class="px-4 py-3 text-ink-500">{{ $event->created_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i') }}</td>
                <td class="px-4 py-3"><x-status-badge :status="$event->resolved ? 'resolved' : 'unresolved'" /></td>
                <td class="px-4 py-3 text-end">
                    @if (!$event->resolved)
                        <form method="POST" action="{{ route('admin.security.resolve', $event) }}">
                            @csrf
                            <button class="text-sm font-medium text-ink-900 hover:underline" title="اضغط بعد أن تراجع الحدث وتتأكد أنه لا يحتاج إجراءً">تمت المراجعة</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <x-table-empty :cols="7" icon="shield" title="لا توجد أحداث أمنية مطابقة"
                           description="هذا خبر جيد: لم يسجّل النظام أي حدث يطابق هذه التصفية." />
        @endforelse
    </x-data-table>

    <div class="mt-4">{{ $events->links() }}</div>
</x-layouts.admin>
