<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'تسجيل الدخول' }} — Qreach</title>
    <script src="{{ asset('theme-init.js') }}"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-dvh bg-canvas text-ink-900">
    <div class="grid min-h-dvh lg:grid-cols-2">
        {{-- Brand panel --}}
        <aside class="relative hidden overflow-hidden bg-brand-black p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="pointer-events-none absolute -top-32 -end-32 size-[28rem] rounded-full bg-brand-yellow/15 blur-3xl"></div>
            <div class="pointer-events-none absolute inset-0 opacity-[0.07]" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 22px 22px;"></div>

            <div class="relative flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand-yellow"><x-icon name="qrcode" class="size-6 text-brand-black" /></span>
                <span class="text-lg font-bold">Qreach <span class="font-normal text-white/50">SmartTag</span></span>
            </div>

            <div class="relative max-w-md">
                <h2 class="text-4xl font-bold leading-tight">تواصل ذكي مع مالك أي مركبة، بمسحة واحدة.</h2>
                <p class="mt-4 text-base leading-relaxed text-white/60">لوحة تحكم واحدة لإدارة الرموز والمركبات والمستخدمين وطلبات التواصل، مع أمان وصلاحيات دقيقة.</p>
                <ul class="mt-8 space-y-3 text-sm text-white/80">
                    @foreach ([['qrcode', 'رموز QR وNFC تُنشأ وتُربط وتُلغى بسهولة'], ['contact', 'محادثات آمنة بين الزوار والمالكين'], ['shield', 'أحداث أمنية وسجلات تدقيق لكل إجراء']] as [$icon, $text])
                        <li class="flex items-center gap-3">
                            <span class="flex size-8 items-center justify-center rounded-lg bg-white/10"><x-icon :name="$icon" class="size-4 text-brand-yellow" /></span>
                            {{ $text }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="relative text-xs text-white/40">© {{ date('Y') }} Qreach — منصة SmartTag لاتصال المركبات</p>
        </aside>

        {{-- Form --}}
        <main class="flex items-center justify-center p-6 sm:p-10">
            <div class="w-full max-w-sm">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-brand-yellow"><x-icon name="qrcode" class="size-6 text-brand-black" /></span>
                    <span class="text-lg font-bold">Qreach <span class="font-normal text-ink-500">SmartTag</span></span>
                </div>

                @if (session('status'))
                    <div class="mb-4 rounded-lg border border-status-success/30 bg-status-success/10 px-4 py-3 text-sm text-status-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-status-danger/30 bg-status-danger/10 px-4 py-3 text-sm text-status-danger" role="alert">
                        <ul class="list-disc space-y-1 ps-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
