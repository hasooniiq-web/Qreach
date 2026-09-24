<x-layouts.guest title="التحقق بخطوتين">
    <h1 class="text-lg font-semibold text-ink-900 mb-1">التحقق بخطوتين</h1>
    <p class="text-sm text-ink-500 mb-6">أدخل الرمز الظاهر في تطبيق المصادقة الخاص بك.</p>

    <form method="POST" action="{{ route('admin.totp.verify') }}" class="space-y-4" novalidate>
        @csrf
        <div>
            <label for="code" class="block text-sm font-medium text-ink-700 mb-1.5">رمز التحقق</label>
            <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" autofocus required
                   class="block w-full rounded-md border border-ink-200 px-3 py-2.5 text-center text-lg tracking-[0.5em] text-ink-900 focus:border-brand-yellow focus:ring-2 focus:ring-brand-yellow/40 outline-none">
        </div>

        <button type="submit"
                class="w-full rounded-md bg-brand-black text-white text-sm font-medium py-2.5 hover:bg-ink-800 transition-colors">
            تأكيد
        </button>
    </form>
</x-layouts.guest>
