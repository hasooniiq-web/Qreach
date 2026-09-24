<x-layouts.guest title="تحديث كلمة المرور">
    <h1 class="text-lg font-semibold text-ink-900 mb-1">يجب تحديث كلمة المرور</h1>
    <p class="text-sm text-ink-500 mb-6">لأسباب أمنية، يرجى تعيين كلمة مرور جديدة قبل المتابعة.</p>

    <form method="POST" action="{{ route('admin.password.update') }}" class="space-y-4" novalidate>
        @csrf
        @method('PUT')
        <div>
            <label for="current_password" class="block text-sm font-medium text-ink-700 mb-1.5">كلمة المرور الحالية</label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required
                   class="block w-full rounded-md border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-yellow focus:ring-2 focus:ring-brand-yellow/40 outline-none">
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-ink-700 mb-1.5">كلمة المرور الجديدة</label>
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" required
                   class="block w-full rounded-md border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-yellow focus:ring-2 focus:ring-brand-yellow/40 outline-none">
            <p class="mt-1 text-xs text-ink-400">12 حرفًا على الأقل.</p>
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-ink-700 mb-1.5">تأكيد كلمة المرور</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                   class="block w-full rounded-md border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-yellow focus:ring-2 focus:ring-brand-yellow/40 outline-none">
        </div>

        <button type="submit"
                class="w-full rounded-md bg-brand-black text-white text-sm font-medium py-2.5 hover:bg-ink-800 transition-colors">
            تحديث كلمة المرور
        </button>
    </form>
</x-layouts.guest>
