<x-layouts.admin title="إضافة مسؤول جديد">
    <x-page-header title="مسؤول جديد" :back="route('admin.admins.index')" backLabel="كل المسؤولين" />

    <form method="POST" action="{{ route('admin.admins.store') }}" class="max-w-lg space-y-4">
        @csrf

        <x-card padding="p-6" class="space-y-5">
            <h3 class="text-sm font-semibold text-ink-900">البيانات الأساسية</h3>
            <x-field label="الاسم الكامل" name="name">
                <input type="text" id="name" name="name" required value="{{ old('name') }}" class="form-control">
            </x-field>
            <x-field label="البريد الإلكتروني" name="email" hint="يُستخدم لتسجيل الدخول.">
                <input type="email" id="email" name="email" required value="{{ old('email') }}" dir="ltr" class="form-control">
            </x-field>
            <x-field label="كلمة المرور" name="password" hint="12 حرفًا على الأقل.">
                <input type="text" id="password" name="password" required minlength="12" autocomplete="off" dir="ltr" class="form-control">
            </x-field>
            <label class="flex items-center gap-2.5 text-sm text-ink-700">
                <input type="checkbox" name="must_change_password" value="1" @checked(old('must_change_password', true))
                       class="rounded border-ink-300 text-brand-yellow focus:ring-brand-yellow">
                <span>إلزام المسؤول بتغيير كلمة المرور عند أول تسجيل دخول</span>
            </label>
        </x-card>

        <x-card padding="p-6">
            <fieldset>
                <legend class="mb-3 text-sm font-semibold text-ink-900">الأدوار والصلاحيات</legend>
                <div class="space-y-3">
                    @foreach ($roles as $role)
                        <label class="flex items-start gap-2.5 text-sm text-ink-700">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, old('roles', [])))
                                   class="mt-0.5 rounded border-ink-300 text-brand-yellow focus:ring-brand-yellow">
                            <span>
                                <span class="block font-medium text-ink-900">{{ $role->name_ar }}</span>
                                @if ($role->description)
                                    <span class="block text-xs text-ink-400">{{ $role->description }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        </x-card>

        <div class="flex items-center gap-3">
            <button type="submit" class="btn-primary">إنشاء المسؤول</button>
            <a href="{{ route('admin.admins.index') }}" class="text-sm text-ink-500 hover:underline">إلغاء</a>
        </div>
    </form>
</x-layouts.admin>
