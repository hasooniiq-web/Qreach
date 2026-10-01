<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Qreach SmartTag' }}</title>
    <script src="{{ asset('theme-init.js') }}"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lateef:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    @stack('head')
</head>
<body class="min-h-dvh bg-ink-50 text-ink-900">
    <div class="mx-auto max-w-md min-h-dvh flex flex-col">
        <header class="flex items-center gap-2.5 px-5 h-14 shrink-0">
            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-yellow">
                <span class="text-xs font-bold text-brand-black">Q</span>
            </div>
            <span class="text-sm font-semibold text-ink-900">Qreach <span class="text-ink-400 font-normal">SmartTag</span></span>
        </header>

        <main class="flex-1 px-5 pb-10">
            {{ $slot }}
        </main>

        <footer class="px-5 py-4 text-center text-xs text-ink-400">
            هذه الصفحة عامة ولا تتطلب تسجيل الدخول
        </footer>
    </div>
</body>
</html>
