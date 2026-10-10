<x-layouts.guest title="تسجيل الدخول">
    <h1 class="text-2xl font-bold tracking-tight text-ink-900">تسجيل دخول المسؤولين</h1>
    <p class="mb-8 mt-1.5 text-sm text-ink-500">أدخل بيانات الدخول للوصول إلى لوحة التحكم.</p>

    <form method="POST" action="{{ route('admin.login.attempt') }}" class="space-y-5" novalidate>
        @csrf
        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-ink-800">البريد الإلكتروني</label>
            <input id="email" name="email" type="email" inputmode="email" autocomplete="username"
                   value="{{ old('email') }}" required autofocus placeholder="name@example.com" dir="ltr"
                   class="form-control text-start" aria-describedby="email-error">
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-ink-800">كلمة المرور</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required dir="ltr"
                   class="form-control text-start">
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-600">
            <input type="checkbox" name="remember" class="size-4 rounded border-ink-300 accent-brand-yellow">
            تذكرني على هذا الجهاز
        </label>

        @php $recaptcha = app(\App\Services\Recaptcha::class); @endphp
        @if ($recaptcha->enabled())
            {{-- Google reCAPTCHA v2 checkbox. Loaded from google.com (allowed for this page only by the CSP). --}}
            <div class="g-recaptcha" data-sitekey="{{ $recaptcha->siteKey() }}" data-theme="light"></div>
            <script src="https://www.google.com/recaptcha/api.js?hl=ar" async defer></script>
        @endif

        <button type="submit" class="btn-primary !h-10 w-full">دخول</button>
    </form>
</x-layouts.guest>