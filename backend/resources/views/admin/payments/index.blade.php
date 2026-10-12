<x-layouts.admin title="المدفوعات">
    <x-page-header title="المدفوعات" subtitle="كل عمليات الدفع الإلكتروني للاشتراكات وحالتها.">
        @if (auth('admin')->user()->hasPermission('payment_gateways.view'))
            <x-slot:actions>
                <a href="{{ route('admin.payment-gateways.index') }}" class="btn-ghost">إعداد بوابات الدفع</a>
            </x-slot:actions>
        @endif
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-kpi label="صافي المبالغ المحصّلة" :value="$totals['succeeded']" hint="د.ع بعد الاستردادات" icon="star" tone="success" />
        <x-kpi label="عمليات ناجحة" :value="$totals['count_succeeded']" icon="audit" />
        <x-kpi label="عمليات فاشلة" :value="$totals['count_failed']" icon="warning" tone="danger" />
        <x-kpi label="بانتظار الدفع" :value="$totals['count_pending']" icon="bell" tone="accent" />
    </div>

    <x-filter-bar>
        <input type="search" aria-label="بحث" name="q" value="{{ $q }}" placeholder="رقم الإيصال أو المرجع أو هاتف العميل" class="form-control !w-72">
        <select name="status" class="form-control !w-auto" data-auto-submit aria-label="تصفية">
            <option value="">كل الحالات</option>
            @foreach (['succeeded' => 'ناجحة', 'pending' => 'بانتظار الدفع', 'failed' => 'فاشلة', 'refunded' => 'مستردّة'] as $value => $label)
                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="gateway" class="form-control !w-auto" data-auto-submit aria-label="تصفية">
            <option value="">كل البوابات</option>
            @foreach ($gateways as $key => $driver)
                <option value="{{ $key }}" @selected(request('gateway') === $key)>{{ $driver->label() }}</option>
            @endforeach
        </select>
        <button class="btn-primary">بحث</button>
    </x-filter-bar>

    <div class="mb-3 flex justify-end"><x-export-buttons /></div>

    <x-data-table :headers="['العميل', 'الباقة', 'المبلغ', 'البوابة', 'الحالة', 'التاريخ', '']">
        @forelse ($payments as $payment)
            <tr class="hover:bg-ink-50">
                <td class="px-4 py-3">
                    <a href="{{ route('admin.users.show', $payment->user) }}" class="font-medium text-ink-900 hover:underline">{{ $payment->user->display_name }}</a>
                    <p class="text-xs tabular-nums text-ink-400" dir="ltr">{{ $payment->user->phone }}</p>
                </td>
                <td class="px-4 py-3 text-ink-900">{{ $payment->plan?->name_ar ?? '—' }}</td>
                <td class="px-4 py-3 tabular-nums text-ink-900">
                    {{ number_format($payment->amount_cents) }} {{ $payment->currency }}
                    @if ($payment->refunded_amount_cents > 0)
                        <p class="text-xs text-ink-400">مُسترد: {{ number_format($payment->refunded_amount_cents) }}</p>
                    @endif
                </td>
                <td class="px-4 py-3 text-ink-600">
                    {{ $gateways[$payment->gateway]?->label() ?? $payment->gateway }}
                    @if ($payment->metadata['test_mode'] ?? false)<span class="text-xs text-ink-400"> (اختبار)</span>@endif
                </td>
                <td class="px-4 py-3">
                    <x-payment-status :status="$payment->status" />
                    @if ($payment->status === 'failed' && $payment->failure_reason)
                        <p class="mt-1 text-xs text-ink-400">{{ $payment->failure_reason }}</p>
                    @endif
                </td>
                <td class="px-4 py-3 text-ink-500">{{ $payment->created_at->diffForHumans() }}</td>
                <td class="px-4 py-3 text-end"><a href="{{ route('admin.payments.show', $payment) }}" class="text-sm font-medium text-ink-900 hover:underline">تفاصيل</a></td>
            </tr>
        @empty
            <x-table-empty :cols="7" icon="star" title="لا توجد مدفوعات" description="تظهر هنا عمليات الدفع عندما يشترك العملاء من التطبيق عبر وسيلة دفع إلكترونية." />
        @endforelse
    </x-data-table>

    <div class="mt-4">{{ $payments->links() }}</div>
</x-layouts.admin>
