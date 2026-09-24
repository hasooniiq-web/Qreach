<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'لوحة التحكم' }} — Qreach</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/admin-nav.js'])
</head>
<body class="min-h-dvh bg-ink-50 text-ink-900">
    @php
        $nav = [
            'عام' => [
                ['icon' => 'dashboard', 'label' => 'لوحة التحكم', 'route' => 'admin.dashboard'],
            ],
            'الإدارة' => [
                ['icon' => 'users', 'label' => 'المستخدمون', 'route' => 'admin.users.index'],
                ['icon' => 'car', 'label' => 'المركبات', 'route' => 'admin.vehicles.index'],
                ['icon' => 'qrcode', 'label' => 'رموز QR', 'route' => 'admin.tags.index'],
                ['icon' => 'nfc', 'label' => 'رموز NFC', 'route' => 'admin.coming-soon', 'arg' => 'nfc'],
                ['icon' => 'batch', 'label' => 'دفعات الرموز', 'route' => 'admin.tag-batches.index'],
                ['icon' => 'contact', 'label' => 'طلبات التواصل', 'route' => 'admin.contact-requests.index'],
                ['icon' => 'bell', 'label' => 'التنبيهات والإشعارات', 'route' => 'admin.coming-soon', 'arg' => 'notifications'],
            ],
            'التحليلات' => [
                ['icon' => 'chart', 'label' => 'التقارير والتحليلات', 'route' => 'admin.coming-soon', 'arg' => 'reports'],
            ],
            'الأمان' => [
                ['icon' => 'shield', 'label' => 'الأمان', 'route' => 'admin.coming-soon', 'arg' => 'security'],
                ['icon' => 'audit', 'label' => 'سجلات التدقيق', 'route' => 'admin.coming-soon', 'arg' => 'audit'],
                ['icon' => 'users', 'label' => 'المسؤولون والصلاحيات', 'route' => 'admin.coming-soon', 'arg' => 'admins'],
            ],
            'النظام' => [
                ['icon' => 'settings', 'label' => 'الإعدادات', 'route' => 'admin.coming-soon', 'arg' => 'settings'],
            ],
        ];
    @endphp

    <div class="flex min-h-dvh">
        <!-- Desktop sidebar (RTL: sits on the right by virtue of dir=rtl + flex order) -->
        <aside class="hidden lg:flex w-64 shrink-0 flex-col border-e border-ink-200 bg-brand-black text-white">
            <div class="flex items-center gap-2.5 px-5 h-16 border-b border-white/10">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-yellow">
                    <span class="text-sm font-bold text-brand-black">Q</span>
                </div>
                <span class="font-semibold">Qreach <span class="text-white/50 font-normal text-sm">SmartTag</span></span>
            </div>

            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-6 text-sm">
                @include('components.layouts.partials.admin-nav-items')
            </nav>

            <div class="border-t border-white/10 p-3">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm text-white/80 hover:bg-white/10 hover:text-white transition-colors">
                        <x-icon name="logout" class="size-5" />
                        تسجيل الخروج
                    </button>
                </form>
            </div>
        </aside>

        <!-- Mobile/tablet drawer -->
        <div id="mobile-nav-backdrop" hidden class="lg:hidden fixed inset-0 z-40 bg-black/50"></div>
        <aside id="mobile-nav" class="lg:hidden fixed inset-y-0 right-0 z-50 w-72 flex flex-col bg-brand-black text-white translate-x-full transition-transform duration-200">
            <div class="flex items-center justify-between px-4 h-16 border-b border-white/10">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-yellow">
                        <span class="text-sm font-bold text-brand-black">Q</span>
                    </div>
                    <span class="font-semibold">Qreach <span class="text-white/50 font-normal text-sm">SmartTag</span></span>
                </div>
                <button type="button" id="mobile-nav-close" aria-label="إغلاق القائمة" class="flex size-11 items-center justify-center rounded-md text-white/80 hover:bg-white/10">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-6 text-sm">
                @include('components.layouts.partials.admin-nav-items')
            </nav>
            <div class="border-t border-white/10 p-3">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-sm text-white/80 hover:bg-white/10 hover:text-white transition-colors">
                        <x-icon name="logout" class="size-5" />
                        تسجيل الخروج
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="h-16 border-b border-ink-200 bg-white flex items-center justify-between px-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <button type="button" id="mobile-nav-open" aria-label="فتح القائمة" class="lg:hidden flex size-11 -ms-2 items-center justify-center rounded-md text-ink-600 hover:bg-ink-100">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                    </button>
                    <h1 class="text-base font-semibold text-ink-900">{{ $title ?? 'لوحة التحكم' }}</h1>
                </div>
                <div class="flex items-center gap-3">
                    <span class="hidden sm:inline text-sm text-ink-500">{{ auth('admin')->user()->name }}</span>
                    <div class="size-8 rounded-full bg-ink-100 flex items-center justify-center text-xs font-medium text-ink-600">
                        {{ mb_substr(auth('admin')->user()->name, 0, 1) }}
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6">
                @if (session('status'))
                    <div class="mb-6 rounded-md bg-status-success/10 border border-status-success/30 px-4 py-3 text-sm text-status-success">
                        {{ session('status') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
