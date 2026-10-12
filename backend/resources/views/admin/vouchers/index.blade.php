@php $can = fn ($k) => auth('admin')->user()->hasPermission($k); @endphp

<x-layouts.admin title="قسائم التفعيل">
    <x-page-header title="قسائم التفعيل" subtitle="أرقام من 16 خانة، كل رقم يُفعّل الباقة مرة واحدة عند إدخاله في التطبيق. تُباع عبر شركات البطاقات الرقمية.">
        @if ($can('vouchers.create'))
            <x-slot:actions>
                <a href="{{ route('admin.vouchers.create') }}" class="btn-accent">+ دفعة قسائم جديدة</a>
            </x-slot:actions>
        @endif
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-kpi label="إجمالي القسائم" :value="$totals['total']" icon="star" />
        <x-kpi label="جاهزة (لم تُصدَّر)" :value="$totals['available']" icon="batch" />
        <x-kpi label="سُلّمت للجهات" :value="$totals['distributed']" icon="users" tone="accent" />
        <x-kpi label="فُعّلت" :value="$totals['redeemed']" icon="chart" tone="success" />
    </div>

    <x-filter-bar>
        <input type="search" aria-label="بحث" name="q" value="{{ request('q') }}" placeholder="رمز الدفعة أو الجهة المستلمة" class="form-control !w-72">
        <button class="btn-primary">بحث</button>
    </x-filter-bar>

    <x-data-table :headers="['الدفعة', 'الجهة المستلمة', 'الباقة والمدة', 'الكمية', 'جاهزة', 'سُلّمت', 'فُعّلت', 'ملغاة', 'تاريخ الإنشاء', '']">
        @forelse ($batches as $batch)
            <tr class="hover:bg-ink-50">
                <td class="px-4 py-3 font-medium tabular-nums text-ink-900" dir="ltr">
                    <a href="{{ route('admin.vouchers.show', $batch) }}" class="hover:underline">{{ $batch->batch_code }}</a>
                </td>
                <td class="px-4 py-3 text-ink-700">{{ $batch->recipient ?: '—' }}</td>
                <td class="px-4 py-3 text-ink-600">{{ $batch->plan->name_ar }} — {{ $batch->duration_days }} يوم</td>
                <td class="px-4 py-3 tabular-nums text-ink-900">{{ number_format($batch->quantity) }}</td>
                <td class="px-4 py-3 tabular-nums text-ink-600">{{ number_format($batch->available_count) }}</td>
                <td class="px-4 py-3 tabular-nums text-ink-600">{{ number_format($batch->distributed_count) }}</td>
                <td class="px-4 py-3 font-semibold tabular-nums text-status-success">{{ number_format($batch->redeemed_count) }}</td>
                <td class="px-4 py-3 tabular-nums text-ink-500">{{ number_format($batch->revoked_count) }}</td>
                <td class="px-4 py-3 text-ink-500">{{ \App\Support\Export\Labels::date($batch->created_at) }}</td>
                <td class="px-4 py-3 text-end"><a href="{{ route('admin.vouchers.show', $batch) }}" class="text-sm font-medium text-ink-900 hover:underline">عرض</a></td>
            </tr>
        @empty
            <x-table-empty :cols="10" icon="star" title="لا توجد دفعات قسائم بعد"
                           description="أنشئ دفعة، ثم صدّرها وسلّمها للشركة التي ستبيعها.">
                @if ($can('vouchers.create'))
                    <a href="{{ route('admin.vouchers.create') }}" class="btn-accent mt-4">إنشاء دفعة</a>
                @endif
            </x-table-empty>
        @endforelse
    </x-data-table>

    <div class="mt-4">{{ $batches->links() }}</div>
</x-layouts.admin>
