@php $canRespond = auth('admin')->user()->hasPermission('contact_requests.respond'); @endphp

<x-layouts.admin title="رسائل الموقع">
    <x-page-header title="رسائل الموقع" subtitle="رسائل أرسلها الزوار من نموذج «تواصل معنا» في الصفحة العامة. تُحفظ هنا دائمًا، ويُرسل نسخة إلى بريد الدعم عند ضبط البريد." />

    <x-filter-bar>
        <select name="status" data-auto-submit aria-label="الحالة" class="form-control w-auto">
            <option value="">كل الحالات</option>
            <option value="new" @selected(request('status') === 'new')>جديدة</option>
            <option value="read" @selected(request('status') === 'read')>مقروءة</option>
        </select>
    </x-filter-bar>

    <div class="mb-3 flex justify-end"><x-export-buttons /></div>

    <x-data-table :headers="['المرسل', 'للرد عليه', 'الرسالة', 'التاريخ', '']">
        @forelse ($messages as $message)
            <tr class="align-top hover:bg-ink-50">
                <td class="px-4 py-3">
                    <p class="font-medium text-ink-900">{{ $message->name }}</p>
                    <p class="mt-0.5">
                        @if ($message->status === 'new')
                            <span class="inline-flex rounded-full bg-brand-yellow/15 px-2 py-0.5 text-xs font-medium text-ink-900 ring-1 ring-inset ring-brand-yellow/50">جديدة</span>
                        @else
                            <span class="inline-flex rounded-full bg-ink-100 px-2 py-0.5 text-xs font-medium text-ink-600 ring-1 ring-inset ring-ink-200">مقروءة</span>
                        @endif
                    </p>
                </td>
                <td class="px-4 py-3 text-sm text-ink-700">
                    @if ($message->email)<p dir="ltr" style="text-align: start"><a href="mailto:{{ $message->email }}" class="hover:underline">{{ $message->email }}</a></p>@endif
                    @if ($message->phone)<p class="tabular-nums" dir="ltr" style="text-align: start"><a href="tel:{{ preg_replace('/[^0-9+]/', '', $message->phone) }}" class="hover:underline">{{ $message->phone }}</a></p>@endif
                </td>
                <td class="max-w-md whitespace-pre-line break-words px-4 py-3 text-sm leading-7 text-ink-800">{{ $message->message }}</td>
                <td class="whitespace-nowrap px-4 py-3 text-xs text-ink-500">{{ \App\Support\Export\Labels::date($message->created_at, true) }}</td>
                <td class="px-4 py-3 text-end">
                    @if ($canRespond && $message->status === 'new')
                        <form method="POST" action="{{ route('admin.site-messages.read', $message) }}">
                            @csrf
                            <button class="text-sm text-ink-600 hover:underline">تمت القراءة</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <x-table-empty :cols="5" icon="contact" title="لا توجد رسائل بعد"
                           description="تظهر هنا الرسائل التي يرسلها الزوار من نموذج «تواصل معنا» في الصفحة الرئيسية." />
        @endforelse
    </x-data-table>

    <div class="mt-4">{{ $messages->links() }}</div>
</x-layouts.admin>
