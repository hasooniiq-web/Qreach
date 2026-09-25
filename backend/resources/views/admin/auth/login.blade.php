<x-layouts.guest title="تسجيل الدخول">
    <h1 class="text-lg font-semibold text-ink-900 mb-1">تسجيل دخول المسؤولين</h1>
    <p class="text-sm text-ink-500 mb-6">أدخل بيانات الدخول للوصول إلى لوحة التحكم.</p>

    <form method="POST" action="{{ route('admin.login.attempt') }}" class="space-y-4" novalidate>
        @csrf
        <div>
            <label for="email" class="block text-sm font-medium text-ink-700 mb-1.5">البريد الإلكتروني</label>
            <input id="email" name="email" type="email" inputmode="email" autocomplete="username"
                   value="{{ old('email') }}" required autofocus
                   class="form-control"
                   aria-describedby="email-error">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-ink-700 mb-1.5">كلمة المرور</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required
                   class="form-control">
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-600">
            <input type="checkbox" name="remember" class="rounded border-ink-300 text-brand-yellow focus:ring-brand-yellow">
            تذكرني
        </label>

        <button type="submit"
                class="w-full rounded-md bg-brand-black text-white text-sm font-medium py-2.5 hover:bg-ink-800 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-yellow focus-visible:ring-offset-2">
            دخول
        </button>
    </form>
</x-layouts.guest>
