<x-layouts.guest title="التحقق بخطوتين">
    <h1 class="mb-1.5 text-2xl font-bold tracking-tight text-ink-900">التحقق بخطوتين</h1>
    <p class="mb-6 text-sm text-ink-500">أدخل الرمز المكوّن من 6 أرقام الظاهر في تطبيق المصادقة.</p>

    <form method="POST" action="{{ route('admin.totp.verify') }}" class="space-y-4" novalidate>
        @csrf
        <x-field label="رمز التحقق" name="code">
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*" maxlength="6" autofocus required dir="ltr"
                   class="form-control text-center text-xl tracking-[0.5em]">
        </x-field>

        <button type="submit" class="btn-primary w-full !py-2.5">تأكيد</button>
    </form>
</x-layouts.guest>
