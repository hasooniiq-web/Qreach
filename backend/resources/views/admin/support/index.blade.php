@php
    $tone = ['open' => 'bg-brand-yellow/15 text-ink-900 ring-brand-yellow/50', 'answered' => 'bg-status-success/10 text-status-success ring-status-success/25', 'closed' => 'bg-ink-100 text-ink-600 ring-ink-200'];
@endphp

<x-layouts.admin title="الدعم الفني">
    <x-page-header title="الدعم الفني" subtitle="تذاكر يفتحها العملاء من التطبيق (نص وصور). الأقدم انتظارًا في الأعلى." />

    <x-filter-bar>
        <select name="status" data-auto-submit aria-label="الحالة" class="form-control w-auto">
            <option value="">كل الحالات</option>
            @foreach ($statusLabels as $key => $label)
                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </x-filter-bar>

    <x-data-table :headers="['العميل', 'العنوان', 'النوع', 'الحالة', 'آخر نشاط']">
        @forelse ($tickets as $ticket)
            <tr class="hover:bg-ink-50">
                <td class="px-4 py-3 text-sm">
                    <p class="font-medium text-ink-900">{{ $ticket->user?->display_name ?? 'حساب محذوف' }}</p>
                    <p class="tabular-nums text-xs text-ink-500" dir="ltr" style="text-align: start">{{ $ticket->user?->phone }}</p>
                </td>
                <td class="px-4 py-3 text-sm"><a href="{{ route('admin.support.show', $ticket) }}" class="font-medium text-ink-900 hover:underline">{{ $ticket->subject }}</a></td>
                <td class="px-4 py-3 text-sm text-ink-600">{{ $categoryLabels[$ticket->category] ?? $ticket->category }}</td>
                <td class="px-4 py-3"><span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $tone[$ticket->status] ?? $tone['closed'] }}">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</span></td>
                <td class="whitespace-nowrap px-4 py-3 text-xs text-ink-500">{{ \App\Support\Export\Labels::date($ticket->last_activity_at, true) }}</td>
            </tr>
        @empty
            <x-table-empty :cols="5" icon="contact" title="لا توجد تذاكر"
                           description="تظهر هنا التذاكر التي يفتحها العملاء من «حسابي ← الدعم الفني» في التطبيق." />
        @endforelse
    </x-data-table>

    <div class="mt-4">{{ $tickets->links() }}</div>
</x-layouts.admin>
