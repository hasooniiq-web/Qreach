<x-layouts.guest title="تحديث كلمة المرور">
    <h1 class="mb-1.5 text-2xl font-bold tracking-tight text-ink-900">يجب تحديث كلمة المرور</h1>
    <p class="mb-6 text-sm text-ink-500">لأسباب أمنية، عيّن كلمة مرور جديدة قبل المتابعة.</p>

    <form method="POST" action="{{ route('admin.password.update') }}" class="space-y-4" novalidate>
        @csrf
        @method('PUT')
        <x-field label="كلمة المرور الحالية" name="current_password">
            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required class="form-control">
        </x-field>
        <x-field label="كلمة المرور الجديدة" name="password" hint="12 حرفًا على الأقل.">
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" required class="form-control">
        </x-field>
        <x-field label="تأكيد كلمة المرور" name="password_confirmation">
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="form-control">
        </x-field>

        <button type="submit" class="btn-primary w-full !py-2.5">تحديث كلمة المرور</button>
    </form>
</x-layouts.guest>
