<x-layouts.public title="بوابة دفع تجريبية">
    <div class="pt-8">
        <p class="mb-4 rounded-md bg-status-warning/15 px-3 py-2 text-center text-xs font-medium text-ink-900">
            بوابة تجريبية — لا يتم خصم أي مبلغ حقيقي.
        </p>

        <h1 class="mb-1 text-center text-xl font-bold text-ink-900">إتمام الدفع</h1>
        <p class="mb-6 text-center text-sm text-ink-500">اشتراك {{ $payment->plan?->name_ar }}</p>

        <div class="mb-6 rounded-lg border border-ink-200 bg-white p-5 text-center">
            <p class="text-xs text-ink-400">المبلغ</p>
            <p class="mt-1 text-3xl font-bold tabular-nums text-ink-900">{{ number_format($payment->amount_cents) }} <span class="text-base font-medium text-ink-500">{{ $payment->currency }}</span></p>
        </div>

        <form method="POST" action="{{ route('payments.sandbox.complete', $payment->gateway_reference) }}" class="space-y-3">
            @csrf
            <button name="result" value="pay" class="btn-primary w-full">دفع (تجريبي)</button>
            <button name="result" value="fail" class="w-full rounded-md border border-ink-200 bg-white px-4 py-2.5 text-sm font-medium text-ink-700 hover:bg-ink-50">محاكاة فشل الدفع</button>
        </form>
    </div>
</x-layouts.public>
