@php
    $canRefund = auth('admin')->user()->hasPermission('payments.refund');
    $remaining = $payment->refundableCents();
@endphp

<x-layouts.admin title="تفاصيل الدفعة">
    <x-page-header title="دفعة {{ $payment->receipt_number ?? 'بدون إيصال' }}" :back="route('admin.payments.index')" backLabel="كل المدفوعات">
        <x-slot:actions><x-payment-status :status="$payment->status" /></x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <h3 class="mb-4 text-sm font-semibold text-ink-900">البيانات</h3>
            <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                <div><dt class="text-xs text-ink-400">العميل</dt><dd class="mt-0.5"><a href="{{ route('admin.users.show', $payment->user) }}" class="font-medium text-ink-900 hover:underline">{{ $payment->user->name ?? 'بدون اسم' }}</a> <span class="tabular-nums text-ink-400" dir="ltr">{{ $payment->user->phone }}</span></dd></div>
                <div><dt class="text-xs text-ink-400">الباقة</dt><dd class="mt-0.5 text-ink-900">{{ $payment->plan?->name_ar ?? '—' }}</dd></div>
                <div><dt class="text-xs text-ink-400">المبلغ</dt><dd class="mt-0.5 font-semibold tabular-nums text-ink-900">{{ number_format($payment->amount_cents) }} {{ $payment->currency }}</dd></div>
                <div><dt class="text-xs text-ink-400">المُسترد</dt><dd class="mt-0.5 tabular-nums text-ink-900">{{ number_format($payment->refunded_amount_cents) }} {{ $payment->currency }}</dd></div>
                <div><dt class="text-xs text-ink-400">البوابة</dt><dd class="mt-0.5 text-ink-900">{{ $driver?->label() ?? $payment->gateway }} @if ($payment->metadata['test_mode'] ?? false)<span class="text-ink-400">(وضع اختبار)</span>@endif</dd></div>
                <div><dt class="text-xs text-ink-400">مرجع البوابة</dt><dd class="mt-0.5 break-all text-ink-900" dir="ltr">{{ $payment->gateway_reference ?? '—' }}</dd></div>
                <div><dt class="text-xs text-ink-400">أُنشئت</dt><dd class="mt-0.5 text-ink-900">{{ $payment->created_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i') }}</dd></div>
                <div><dt class="text-xs text-ink-400">تاريخ الدفع</dt><dd class="mt-0.5 text-ink-900">{{ $payment->paid_at?->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i') ?? '—' }}</dd></div>
                @if ($payment->failure_reason)
                    <div class="sm:col-span-2"><dt class="text-xs text-ink-400">سبب الفشل</dt><dd class="mt-0.5 text-status-danger">{{ $payment->failure_reason }}</dd></div>
                @endif
                @if ($payment->subscription)
                    <div class="sm:col-span-2"><dt class="text-xs text-ink-400">الاشتراك الناتج</dt><dd class="mt-0.5 text-ink-900">حتى {{ $payment->subscription->current_period_ends_at?->copy()->timezone(config('app.display_timezone'))->format('Y-m-d') ?? '—' }} ({{ $payment->subscription->status }})</dd></div>
                @endif
            </dl>
        </x-card>

        <x-card>
            <h3 class="mb-4 text-sm font-semibold text-ink-900">استرداد</h3>
            @if ($remaining > 0 && $canRefund && $driver?->supportsRefunds())
                <form method="POST" action="{{ route('admin.payments.refund', $payment) }}" class="space-y-3"
                      data-confirm-title="تنفيذ الاسترداد؟"
                      data-confirm="سيُعاد المبلغ المُدخل إلى العميل عبر البوابة. إذا استُرد المبلغ بالكامل تنتهي الباقة التي اشتراها."
                      data-confirm-label="استرداد">
                    @csrf
                    <x-field label="المبلغ ({{ $payment->currency }})" name="amount" :hint="'المتاح للاسترداد: '.number_format($remaining)">
                        <input type="number" id="amount" name="amount" min="1" max="{{ $remaining }}" value="{{ old('amount', $remaining) }}" required class="form-control">
                    </x-field>
                    <button class="btn-primary w-full">استرداد</button>
                </form>
            @elseif ($payment->status === 'succeeded' && $driver && ! $driver->supportsRefunds())
                <p class="text-sm text-ink-500">هذه البوابة لا تدعم الاسترداد من النظام. استرد المبلغ من لوحة مزوّد الدفع.</p>
            @elseif ($payment->status === 'refunded')
                <p class="text-sm text-ink-500">استُرد المبلغ بالكامل.</p>
            @else
                <p class="text-sm text-ink-500">لا يوجد ما يمكن استرداده.</p>
            @endif
        </x-card>
    </div>

    <h3 class="mb-3 mt-8 text-sm font-semibold text-ink-900">سجل الأحداث</h3>
    <x-data-table :headers="['الوقت', 'النوع', 'النتيجة', 'التوقيع']">
        @forelse ($payment->events as $event)
            <tr>
                <td class="px-4 py-3 text-ink-500">{{ $event->created_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i:s') }}</td>
                <td class="px-4 py-3 text-ink-900">{{ ['callback' => 'إشعار من البوابة', 'status_check' => 'استعلام عن الحالة', 'refund' => 'استرداد'][$event->type] ?? $event->type }}</td>
                <td class="px-4 py-3 text-ink-600" dir="ltr">{{ $event->outcome }}</td>
                <td class="px-4 py-3">{!! $event->signature_valid ? '<span class="text-status-success">صالح</span>' : '<span class="text-status-danger">مرفوض</span>' !!}</td>
            </tr>
        @empty
            <x-table-empty :cols="4" icon="audit" title="لا أحداث بعد" description="تظهر هنا إشعارات البوابة وعمليات الاسترداد." />
        @endforelse
    </x-data-table>
</x-layouts.admin>
