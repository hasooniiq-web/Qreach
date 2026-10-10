<x-layouts.admin title="الملف الشخصي">
    <div class="max-w-2xl space-y-4">
        {{-- Photo + display name --}}
        <x-card padding="p-6">
            <h3 class="mb-4 text-sm font-semibold text-ink-900">الصورة والاسم</h3>

            <div class="mb-6 flex flex-wrap items-center gap-5">
                <x-admin-avatar :admin="$admin" size="size-20" class="!text-2xl" />
                <div class="space-y-2">
                    <form method="POST" action="{{ route('admin.profile.avatar.store') }}" enctype="multipart/form-data">
                        @csrf
                        {{-- Choosing a file submits the form right away (data-auto-submit, handled in admin-ui.js). --}}
                        <input id="avatar" type="file" name="avatar" accept="image/png,image/jpeg,image/webp" required data-auto-submit class="peer sr-only">
                        <label for="avatar" class="btn-secondary cursor-pointer peer-focus-visible:ring-2 peer-focus-visible:ring-brand-yellow">
                            {{ $admin->avatar_path ? 'تغيير الصورة' : 'اختيار صورة' }}
                        </label>
                    </form>
                    @error('avatar')<p class="text-xs text-status-danger" role="alert">{{ $message }}</p>@enderror
                    <p class="text-xs text-ink-400">PNG أو JPG أو WebP، حتى 2 ميجابايت. تُقتصّ الصورة إلى مربع تلقائيًا.</p>
                    @if ($admin->avatar_path)
                        <form method="POST" action="{{ route('admin.profile.avatar.destroy') }}"
                              data-confirm-title="إزالة الصورة؟" data-confirm="ستُستبدل صورتك بالحرف الأول من اسمك." data-confirm-label="إزالة">
                            @csrf
                            @method('DELETE')
                            <button class="text-sm text-status-danger hover:underline">إزالة الصورة</button>
                        </form>
                    @endif
                </div>
            </div>

            <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-4 border-t border-ink-100 pt-6">
                @csrf
                @method('PUT')
                <x-field label="الاسم الظاهر" name="name" hint="يظهر في الشريط العلوي وفي سجلات النشاط.">
                    <input id="name" type="text" name="name" value="{{ old('name', $admin->name) }}" required minlength="2" maxlength="100" class="form-control">
                </x-field>
                <button type="submit" class="btn-primary">حفظ الاسم</button>
            </form>
        </x-card>

        {{-- Password --}}
        <x-card padding="p-6">
            <h3 class="mb-1 text-sm font-semibold text-ink-900">كلمة المرور</h3>
            <p class="mb-4 text-xs text-ink-500">
                @if ($admin->password_changed_at)
                    آخر تغيير: {{ $admin->password_changed_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i') }}.
                @endif
                12 حرفًا على الأقل. نطلب كلمة المرور الحالية للتأكد أنك أنت.
            </p>
            <form method="POST" action="{{ route('admin.profile.password') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <x-field label="كلمة المرور الحالية" name="current_password">
                    <input id="current_password" type="password" name="current_password" autocomplete="current-password" required class="form-control">
                </x-field>
                <x-field label="كلمة المرور الجديدة" name="password">
                    <input id="password" type="password" name="password" autocomplete="new-password" minlength="12" required class="form-control">
                </x-field>
                <x-field label="تأكيد كلمة المرور الجديدة" name="password_confirmation">
                    <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required class="form-control">
                </x-field>
                <button type="submit" class="btn-primary">تغيير كلمة المرور</button>
            </form>
        </x-card>

        {{-- Read-only account facts --}}
        <x-card padding="p-6">
            <h3 class="mb-4 text-sm font-semibold text-ink-900">معلومات الحساب</h3>
            <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                <div><dt class="text-xs text-ink-400">البريد الإلكتروني</dt><dd class="mt-1 text-ink-900" dir="ltr">{{ $admin->email }}</dd></div>
                <div>
                    <dt class="text-xs text-ink-400">الأدوار</dt>
                    <dd class="mt-1">
                        @forelse ($admin->roles as $role)
                            <span class="me-1 inline-flex items-center rounded-full bg-ink-100 px-2 py-0.5 text-xs text-ink-700">{{ $role->name_ar }}</span>
                        @empty
                            <span class="text-ink-400">—</span>
                        @endforelse
                    </dd>
                </div>
                <div><dt class="text-xs text-ink-400">آخر تسجيل دخول</dt><dd class="mt-1 text-ink-900">{{ $admin->last_login_at?->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i') ?? '—' }}</dd></div>
            </dl>
            <p class="mt-4 text-xs text-ink-400">البريد والأدوار يديرها مدير النظام من صفحة المسؤولين.</p>
        </x-card>
    </div>
</x-layouts.admin>
