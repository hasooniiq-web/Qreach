@php $canManage = auth('admin')->user()->hasPermission('trade.manage'); @endphp

<x-layouts.admin title="المخزون والحسابات">
    <x-page-header title="المخزون والحسابات" subtitle="ما تبقّى من الملصقات، وما سُلّم للتجار، وما دفعوه وما بقي عليهم. بالدينار العراقي.">
        @if ($canManage)
            <x-slot:actions>
                <a href="{{ route('admin.trade.customers.create') }}" class="btn-accent">+ عميل جديد</a>
            </x-slot:actions>
        @endif
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-kpi label="في المخزون" :value="$totals['stock']" hint="ملصق لم يُسلَّم بعد" icon="batch" />
        <x-kpi label="مُسلَّم للتجار" :value="$totals['delivered']" hint="ملصق" icon="qrcode" />
        <x-kpi label="مجموع البيع" :value="$totals['sales']" hint="د.ع" icon="chart" tone="success" />
        <x-kpi label="المتبقي على التجار" :value="$totals['outstanding']" :hint="'د.ع — المستلم '.number_format($totals['received'])" icon="warning" tone="accent" />
    </div>

    <h2 class="mb-3 text-sm font-semibold text-ink-900">العملاء</h2>
    <x-filter-bar>
        <input type="search" aria-label="بحث" name="q" value="{{ request('q') }}" placeholder="اسم العميل أو هاتفه" class="form-control !w-72">
        <button class="btn-primary">بحث</button>
    </x-filter-bar>

    <div class="mb-3 flex justify-end"><x-export-buttons /></div>

    <x-data-table :headers="['العميل', 'الهاتف', 'المُسلَّم', 'مجموع البيع', 'المدفوع', 'المتبقي عليه', '']">
        @forelse ($customers as $customer)
            @php
                $owed = (int) $customer->sales_total - (int) $customer->paid_total;
            @endphp
            <tr class="hover:bg-ink-50">
                <td class="px-4 py-3 font-medium text-ink-900">
                    <a href="{{ route('admin.trade.customers.show', $customer) }}" class="hover:underline">{{ $customer->name }}</a>
                </td>
                <td class="px-4 py-3 tabular-nums text-ink-600" dir="ltr">{{ $customer->phone ?: '—' }}</td>
                <td class="px-4 py-3 tabular-nums text-ink-600">{{ number_format((int) $customer->delivered_qty) }}</td>
                <td class="px-4 py-3 tabular-nums text-ink-600">{{ number_format((int) $customer->sales_total) }}</td>
                <td class="px-4 py-3 tabular-nums text-ink-600">{{ number_format((int) $customer->paid_total) }}</td>
                <td class="px-4 py-3 font-semibold tabular-nums {{ $owed > 0 ? 'text-status-danger' : 'text-status-success' }}">{{ number_format($owed) }}</td>
                <td class="px-4 py-3 text-end"><a href="{{ route('admin.trade.customers.show', $customer) }}" class="text-sm font-medium text-ink-900 hover:underline">عرض</a></td>
            </tr>
        @empty
            <x-table-empty :cols="7" icon="users" title="لا يوجد عملاء بعد"
                           description="أضف أول تاجر، ثم سجّل ما سلّمته له وما دفعه.">
                @if ($canManage)
                    <a href="{{ route('admin.trade.customers.create') }}" class="btn-accent mt-4">إضافة عميل</a>
                @endif
            </x-table-empty>
        @endforelse
    </x-data-table>
    <div class="mt-4">{{ $customers->links() }}</div>

    <h2 class="mb-3 mt-8 text-sm font-semibold text-ink-900">مخزون الدفعات (آخر 12)</h2>
    <x-data-table :headers="['الدفعة', 'الكمية المُنشأة', 'المُسلَّم', 'المتبقي في المخزون']">
        @forelse ($batches as $row)
            <tr class="hover:bg-ink-50">
                <td class="px-4 py-3 font-medium tabular-nums text-ink-900"><a href="{{ route('admin.tag-batches.show', $row['batch']) }}" class="hover:underline">{{ $row['batch']->batch_code }}</a></td>
                <td class="px-4 py-3 tabular-nums text-ink-600">{{ number_format($row['batch']->quantity) }}</td>
                <td class="px-4 py-3 tabular-nums text-ink-600">{{ number_format($row['delivered']) }}</td>
                <td class="px-4 py-3 font-semibold tabular-nums text-ink-900">{{ number_format($row['remaining']) }}</td>
            </tr>
        @empty
            <x-table-empty :cols="4" icon="batch" title="لا توجد دفعات بعد" />
        @endforelse
    </x-data-table>
</x-layouts.admin>
