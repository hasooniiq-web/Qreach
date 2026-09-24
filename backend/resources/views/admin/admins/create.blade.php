<x-layouts.admin title="إضافة مسؤول جديد">
    <div class="max-w-lg rounded-lg border border-ink-200 bg-white p-6">
        <form method="POST" action="{{ route('admin.admins.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1.5">الاسم الكامل</label>
                <input type="text" name="name" required value="{{ old('name') }}"
                       class="block w-full rounded-md border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-yellow focus:ring-2 focus:ring-brand-yellow/40 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1.5">البريد الإلكتروني</label>
                <input type="email" name="email" required value="{{ old('email') }}" dir="ltr"
                       class="block w-full rounded-md border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-yellow focus:ring-2 focus:ring-brand-yellow/40 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700 mb-2">الأدوار</label>
                <div class="space-y-2">
                    @foreach ($roles as $role)
                        <label class="flex items-start gap-2 text-sm text-ink-700">
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
            </div>

            <p class="text-xs text-ink-400">سيتم إنشاء كلمة مرور مؤقتة تلقائيًا، وستظهر لك بعد الإنشاء مباشرة. سيُطلب من المسؤول الجديد تغييرها عند أول تسجيل دخول.</p>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="rounded-md bg-brand-black text-white text-sm font-medium px-5 py-2.5 hover:bg-ink-800">إنشاء المسؤول</button>
                <a href="{{ route('admin.admins.index') }}" class="text-sm text-ink-500 hover:underline">إلغاء</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
