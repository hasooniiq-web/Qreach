@php $canRespond = auth('admin')->user()->hasPermission('contact_requests.respond'); @endphp

<x-layouts.admin title="بلاغات المحادثات">
    <x-page-header title="بلاغات المحادثات" subtitle="بلاغات يرسلها العملاء من داخل المحادثة عن زائر أو حساب يضايقهم. لا يعرف المُبلَّغ عنه من أبلغ." />

    <x-filter-bar>
        <select name="status" data-auto-submit aria-label="الحالة" class="form-control w-auto">
            <option value="">كل الحالات</option>
            <option value="new" @selected(request('status') === 'new')>جديدة</option>
            <option value="reviewed" @selected(request('status') === 'reviewed')>تمت المراجعة</option>
        </select>
    </x-filter-bar>

    <x-data-table :headers="['من أبلغ', 'عن من', 'السبب', 'التفاصيل', 'المركبة', 'التاريخ', '']">
        @forelse ($reports as $report)
            <tr class="align-top hover:bg-ink-50">
                <td class="px-4 py-3 text-sm">
                    <p class="font-medium text-ink-900">{{ $report->filedBy?->display_name ?? '—' }}</p>
                    <p class="text-xs text-ink-500">{{ $report->reported_by_role === 'owner' ? 'مالك المركبة' : 'زائر من التطبيق' }}</p>
                    <p class="tabular-nums text-xs text-ink-400" dir="ltr" style="text-align: start">{{ $report->filedBy?->phone }}</p>
                </td>
                <td class="px-4 py-3 text-sm">
                    @if ($report->reportedUser)
                        <p class="font-medium text-ink-900">{{ $report->reportedUser->display_name }}</p>
                        <p class="tabular-nums text-xs text-ink-400" dir="ltr" style="text-align: start">{{ $report->reportedUser->phone }}</p>
                    @else
                        <p class="text-ink-600">زائر من صفحة الويب</p>
                        <p class="tabular-nums text-xs text-ink-400" dir="ltr" style="text-align: start">{{ $report->conversation?->request_ip }}</p>
                    @endif
                </td>
                <td class="px-4 py-3 text-sm text-ink-800">{{ $reasonLabels[$report->reason] ?? $report->reason }}</td>
                <td class="max-w-xs whitespace-pre-line break-words px-4 py-3 text-sm text-ink-700">{{ $report->details ?: '—' }}</td>
                <td class="px-4 py-3 text-sm text-ink-600">
                    @if ($report->conversation?->vehicle)
                        <a href="{{ route('admin.contact-requests.show', $report->conversation) }}" class="hover:underline">{{ $report->conversation->vehicle->plate_number }}</a>
                    @else — @endif
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-xs text-ink-500">{{ \App\Support\Export\Labels::date($report->created_at, true) }}</td>
                <td class="px-4 py-3 text-end">
                    @if ($report->status === 'new')
                        @if ($canRespond)
                            <form method="POST" action="{{ route('admin.conversation-reports.review', $report) }}">
                                @csrf
                                <button class="text-sm text-ink-600 hover:underline">تمت المراجعة</button>
                            </form>
                        @endif
                    @else
                        <span class="text-xs text-ink-400">تمت المراجعة</span>
                    @endif
                </td>
            </tr>
        @empty
            <x-table-empty :cols="7" icon="shield" title="لا توجد بلاغات"
                           description="تظهر هنا البلاغات التي يرسلها العملاء من أيقونة «إبلاغ» داخل المحادثة." />
        @endforelse
    </x-data-table>

    <div class="mt-4">{{ $reports->links() }}</div>
</x-layouts.admin>
