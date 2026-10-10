<x-layouts.guest title="تفعيل التحقق بخطوتين">
    <h1 class="mb-1.5 text-2xl font-bold tracking-tight text-ink-900">تفعيل التحقق بخطوتين</h1>
    <p class="mb-5 text-sm leading-relaxed text-ink-500">
        لحماية لوحة التحكم، يلزم كل مسؤول تطبيق مصادقة (Google Authenticator أو Microsoft Authenticator أو Authy).
        بعد التفعيل يُطلب منك رمز من التطبيق عند كل تسجيل دخول.
    </p>

    <ol class="mb-5 space-y-2 text-sm text-ink-700">
        <li>1. ثبّت تطبيق المصادقة على هاتفك.</li>
        <li>2. اختر «إضافة حساب» ثم «مسح رمز QR» ووجّه الكاميرا إلى الرمز أدناه.</li>
        <li>3. أدخل الرمز المكوّن من 6 أرقام الذي يظهر في التطبيق.</li>
    </ol>

    <div class="mb-4 flex flex-col items-center gap-3 rounded-lg border border-ink-200 bg-white p-4">
        <img src="{{ $qr }}" alt="رمز QR لإضافة الحساب إلى تطبيق المصادقة" width="220" height="220" class="rounded bg-white">
        <div class="text-center">
            <p class="text-xs text-ink-500">لا تستطيع المسح؟ أدخل هذا المفتاح يدويًا:</p>
            <p class="mt-1 select-all font-mono text-sm tracking-wider text-ink-900" dir="ltr">{{ $secret }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.totp.setup.confirm') }}" class="space-y-4" novalidate>
        @csrf
        <x-field label="رمز التحقق" name="code">
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]*" maxlength="7" autofocus required dir="ltr"
                   class="form-control text-center text-xl tracking-[0.5em]">
        </x-field>

        <button type="submit" class="btn-primary w-full !py-2.5">تفعيل</button>
    </form>

    <form method="POST" action="{{ route('admin.logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm text-ink-500 hover:underline">تسجيل الخروج</button>
    </form>
</x-layouts.guest>
