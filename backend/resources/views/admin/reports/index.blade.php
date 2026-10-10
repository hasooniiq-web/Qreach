@php $canExport = auth('admin')->user()->hasPermission('reports.export'); @endphp

<x-layouts.admin title="التقارير">
    <nav aria-label="نوع التقرير" class="mb-5 flex gap-1 border-b border-ink-200">
        @foreach ($reports as $key => $label)
            <a href="{{ route('admin.reports.index', ['report' => $key]) }}" @if ($report === $key) aria-current="page" @endif
               class="-mb-px border-b-2 px-4 py-2.5 text-sm font-medium {{ $report === $key ? 'border-brand-black text-ink-900' : 'border-transparent text-ink-500 hover:text-ink-900' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <x-filter-bar>
        <input type="hidden" name="report" value="{{ $report }}">
        <div class="flex items-center gap-2">
            <label for="from" class="text-sm text-ink-500">من</label>
            <input id="from" type="date" name="from" value="{{ $from->toDateString() }}" class="form-control w-auto !py-2">
            <label for="to" class="text-sm text-ink-500">إلى</label>
            <input id="to" type="date" name="to" value="{{ $to->toDateString() }}" class="form-control w-auto !py-2">
        </div>
        @if ($report === 'scans')
            <select name="action" data-auto-submit aria-label="نوع الإجراء" class="form-control w-auto">
                <option value="">كل الإجراءات</option>
                @foreach ($actions as $value => $label)
                    <option value="{{ $value }}" @selected(request('action') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        @else
            <select name="status" data-auto-submit aria-label="الحالة" class="form-control w-auto">
                <option value="">كل الحالات</option>
                @foreach (['new' => 'جديد', 'read' => 'مقروء', 'responded' => 'تم الرد', 'closed' => 'مغلق'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        @endif
        <button type="submit" class="btn-primary">تطبيق</button>
        @if ($canExport)
            <a href="{{ route('admin.reports.export', request()->query()) }}" class="btn-secondary ms-auto">تصدير CSV</a>
        @endif
    </x-filter-bar>

    {{-- Summary: real counts for exactly the filtered range. --}}
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <x-kpi :label="'إجمالي '.$reports[$report].' في الفترة'" :value="$total" icon="chart" size="lg" />
        @if ($report === 'scans')
            @foreach (['view' => 'عرض الصفحة', 'contact_owner' => 'مراسلة المالك', 'emergency' => 'حالات طارئة'] as $type => $label)
                <x-kpi :label="$label" :value="$summary[$type] ?? 0" :icon="$type === 'emergency' ? 'warning' : 'contact'" :tone="$type === 'emergency' && ($summary[$type] ?? 0) > 0 ? 'danger' : 'neutral'" />
            @endforeach
        @else
            @foreach (['new' => 'جديد', 'responded' => 'تم الرد', 'closed' => 'مغلق'] as $type => $label)
                <x-kpi :label="$label" :value="$summary[$type] ?? 0" icon="contact" />
            @endforeach
        @endif
    </div>

    @if ($report === 'scans')
        <x-data-table :headers="['التاريخ', 'الإجراء', 'الحالة', 'اللوحة', 'الرمز']">
            @forelse ($rows as $row)
                <tr class="hover:bg-ink-50">
                    <td class="px-4 py-3 text-ink-500">{{ $row->created_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-3 font-medium text-ink-900">{{ $actions[$row->action_type] ?? $row->action_type }}</td>
                    <td class="px-4 py-3"><x-status-badge :status="$row->status === 'success' ? 'success' : 'failure'" /></td>
                    <td class="px-4 py-3 text-ink-600">{{ $row->vehicle?->plate_number ?? '—' }}</td>
                    <td class="px-4 py-3 tabular-nums text-ink-500" dir="ltr">{{ $row->tag?->serial_number ?? '—' }}</td>
                </tr>
            @empty
                <x-table-empty :cols="5" icon="chart" title="لا توجد عمليات مسح في هذه الفترة" description="غيّر نطاق التاريخ أو الإجراء." />
            @endforelse
        </x-data-table>
    @else
        <x-data-table :headers="['التاريخ', 'الحالة', 'اللوحة', 'الرسالة']">
            @forelse ($rows as $row)
                <tr class="hover:bg-ink-50">
                    <td class="px-4 py-3 text-ink-500">{{ $row->created_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-3"><x-status-badge :status="$row->status" /></td>
                    <td class="px-4 py-3 text-ink-600">{{ $row->vehicle?->plate_number ?? '—' }}</td>
                    <td class="max-w-[320px] truncate px-4 py-3 text-ink-600">{{ $row->message }}</td>
                </tr>
            @empty
                <x-table-empty :cols="4" icon="contact" title="لا توجد طلبات تواصل في هذه الفترة" description="غيّر نطاق التاريخ أو الحالة." />
            @endforelse
        </x-data-table>
    @endif

    <p class="mt-3 text-xs text-ink-400">لا يتضمن التقرير ولا ملف التصدير عناوين IP أو بيانات المتصفح.</p>
    <div class="mt-4">{{ $rows->links() }}</div>
</x-layouts.admin>
