<x-layouts.admin title="الأمان">
    <form method="GET" class="flex flex-wrap items-center gap-3 mb-5">
        <select name="severity" class="rounded-md border border-ink-200 px-3 py-2 text-sm" onchange="this.form.submit()">
            <option value="">كل المستويات</option>
            <option value="critical" @selected(request('severity') === 'critical')>حرج</option>
            <option value="warning" @selected(request('severity') === 'warning')>تحذير</option>
            <option value="info" @selected(request('severity') === 'info')>معلومات</option>
        </select>
        <select name="event_type" class="rounded-md border border-ink-200 px-3 py-2 text-sm" onchange="this.form.submit()">
            <option value="">كل الأنواع</option>
            @foreach ($eventTypes as $type)
                <option value="{{ $type }}" @selected(request('event_type') === $type)>{{ $type }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 text-sm text-ink-600">
            <input type="checkbox" name="unresolved_only" value="1" @checked(request()->boolean('unresolved_only')) onchange="this.form.submit()"
                   class="rounded border-ink-300 text-brand-yellow focus:ring-brand-yellow">
            غير المحلولة فقط
        </label>
    </form>

    <div class="rounded-lg border border-ink-200 bg-white overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-ink-50 text-ink-500 text-xs">
                <tr>
                    <th class="text-start font-medium px-4 py-3">النوع</th>
                    <th class="text-start font-medium px-4 py-3">المستوى</th>
                    <th class="text-start font-medium px-4 py-3">الجهة الفاعلة</th>
                    <th class="text-start font-medium px-4 py-3">عنوان IP</th>
                    <th class="text-start font-medium px-4 py-3">التاريخ</th>
                    <th class="text-start font-medium px-4 py-3">الحالة</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                @forelse ($events as $event)
                    <tr class="hover:bg-ink-50">
                        <td class="px-4 py-3 font-medium text-ink-900">{{ $event->event_type }}</td>
                        <td class="px-4 py-3">
                            @php
                                $sevClass = match($event->severity) {
                                    'critical' => 'bg-status-danger/10 text-status-danger',
                                    'warning' => 'bg-brand-yellow/20 text-ink-900',
                                    default => 'bg-ink-100 text-ink-500',
                                };
                                $sevLabel = match($event->severity) { 'critical' => 'حرج', 'warning' => 'تحذير', default => 'معلومات' };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $sevClass }}">{{ $sevLabel }}</span>
                        </td>
                        <td class="px-4 py-3 text-ink-600">{{ $event->actor_type }}</td>
                        <td class="px-4 py-3 text-ink-500 tabular-nums" dir="ltr">{{ $event->request_ip }}</td>
                        <td class="px-4 py-3 text-ink-500">{{ $event->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">
                            @if ($event->resolved)
                                <span class="text-xs text-status-success">تم الحل</span>
                            @else
                                <span class="text-xs text-ink-400">قيد المراجعة</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if (!$event->resolved)
                                <form method="POST" action="{{ route('admin.security.resolve', $event) }}">
                                    @csrf
                                    <button class="text-sm text-ink-900 font-medium hover:underline">تحديد كمحلول</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-ink-400">لا توجد أحداث أمنية مطابقة.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $events->links() }}</div>
</x-layouts.admin>
