<x-layouts.public title="عملية الدفع">
    <div class="pt-10 text-center">
        <h1 class="mb-2 text-xl font-bold text-ink-900">
            @if ($status === 'succeeded') تمت عملية الدفع
            @elseif ($status === 'failed') لم تكتمل عملية الدفع
            @else جارٍ التحقق من عملية الدفع
            @endif
        </h1>
        <p class="text-sm leading-relaxed text-ink-500">
            @if ($status === 'succeeded') عُد إلى تطبيق Qreach لرؤية الإيصال وتفعيل باقتك.
            @elseif ($status === 'failed') لم يُخصم أي مبلغ. عُد إلى التطبيق وحاول مرة أخرى.
            @else عُد إلى تطبيق Qreach؛ ستظهر النتيجة هناك خلال لحظات.
            @endif
        </p>
    </div>
</x-layouts.public>
