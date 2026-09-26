<x-layouts.admin title="رموز OTP (مؤقت)">
    {{-- Refreshes itself so a code appears seconds after it is requested from the app. --}}
    @push('head')<meta http-equiv="refresh" content="8">@endpush

    <x-page-header title="آخر رمز OTP لكل رقم"
                   subtitle="أداة مؤقتة للتطوير: لا يوجد مزوّد SMS بعد. تتحدّث الصفحة تلقائيًا كل 8 ثوانٍ." />

    <div class="mb-4 rounded-md border border-status-warning/40 bg-status-warning/10 px-4 py-3 text-sm text-status-warning">
        هذه الصفحة تعرض رموز الدخول كنص صريح. أوقفها بضبط DEV_OTP_INBOX=false قبل أي نشر حقيقي.
    </div>

    <x-data-table :headers="['رقم الهاتف', 'آخر رمز', 'وقت الإرسال', 'مرات الطلب']">
        @forelse ($codes as $row)
            @php $age = \Illuminate\Support\Carbon::parse($row->sent_at); @endphp
            <tr class="hover:bg-ink-50">
                <td class="px-4 py-3 tabular-nums text-ink-900" dir="ltr">{{ $row->phone }}</td>
                <td class="px-4 py-3">
                    <span class="rounded-md bg-brand-yellow px-3 py-1 font-mono text-lg font-bold tracking-widest text-brand-black" dir="ltr">{{ $row->code }}</span>
                </td>
                <td class="px-4 py-3 text-ink-500">
                    {{ $age->format('Y-m-d H:i:s') }}
                    <span class="text-xs {{ $age->lt(now()->subMinutes(5)) ? 'text-status-danger' : 'text-status-success' }}">
                        ({{ $age->diffForHumans() }}{{ $age->lt(now()->subMinutes(5)) ? ' — قد يكون منتهيًا' : '' }})
                    </span>
                </td>
                <td class="px-4 py-3 tabular-nums text-ink-500">{{ $row->sent_count }}</td>
            </tr>
        @empty
            <x-table-empty :cols="4" icon="users" title="لم يُطلب أي رمز بعد"
                           description="اطلب رمز دخول من التطبيق وسيظهر هنا خلال ثوانٍ." />
        @endforelse
    </x-data-table>
</x-layouts.admin>
