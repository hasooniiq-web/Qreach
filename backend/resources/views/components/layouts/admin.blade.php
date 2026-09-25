<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'لوحة التحكم' }} — Qreach</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/admin-nav.js', 'resources/js/admin-ui.js'])
</head>
<body class="min-h-dvh bg-ink-50 text-ink-900">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-[60] focus:rounded-md focus:bg-brand-yellow focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-brand-black">تخطّي إلى المحتوى</a>
    @php
        // Grouped by function rather than by "everything that isn't
        // analytics or security" (see docs/UI_UX_REDESIGN_AUDIT.md finding
        // #1) — platform data (users/vehicles/tags/batches) is separate
        // from visitor interaction (contact requests, notifications), which
        // is separate from analytics, security, and admin/system settings.
        // Every entry below routes to a real screen or an explicit,
        // honestly-labeled "coming soon" page — none are invented.
        $nav = [
            'الرئيسية' => [
                ['icon' => 'dashboard', 'label' => 'لوحة التحكم', 'route' => 'admin.dashboard'],
            ],
            'إدارة المنصة' => [
                ['icon' => 'users', 'label' => 'المستخدمون', 'route' => 'admin.users.index'],
                ['icon' => 'car', 'label' => 'المركبات', 'route' => 'admin.vehicles.index'],
                ['icon' => 'qrcode', 'label' => 'رموز QR', 'route' => 'admin.tags.index'],
                ['icon' => 'nfc', 'label' => 'رموز NFC', 'route' => 'admin.tags.index', 'query' => ['type' => 'nfc']],
                ['icon' => 'batch', 'label' => 'دفعات الرموز', 'route' => 'admin.tag-batches.index'],
            ],
            'التفاعل' => [
                ['icon' => 'contact', 'label' => 'طلبات التواصل', 'route' => 'admin.contact-requests.index'],
                ['icon' => 'bell', 'label' => 'التنبيهات والإشعارات', 'route' => 'admin.coming-soon', 'arg' => 'notifications'],
            ],
            'التحليلات' => [
                ['icon' => 'chart', 'label' => 'التحليلات', 'route' => 'admin.analytics.index'],
                ['icon' => 'audit', 'label' => 'التقارير', 'route' => 'admin.reports.index'],
            ],
            'الأمان' => [
                ['icon' => 'shield', 'label' => 'الأحداث الأمنية', 'route' => 'admin.security.index'],
                ['icon' => 'audit', 'label' => 'سجلات التدقيق', 'route' => 'admin.audit.index'],
            ],
            'الإدارة' => [
                ['icon' => 'star', 'label' => 'الاشتراكات والباقات', 'route' => 'admin.subscriptions.index'],
                ['icon' => 'users', 'label' => 'المسؤولون والصلاحيات', 'route' => 'admin.admins.index'],
                ['icon' => 'settings', 'label' => 'إعدادات النظام', 'route' => 'admin.settings.index'],
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

            <nav aria-label="التنقل الرئيسي" class="flex-1 overflow-y-auto py-4 px-3 space-y-6 text-sm">
                @include('components.layouts.partials.admin-nav-items')
            </nav>
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
            <nav aria-label="التنقل الرئيسي" class="flex-1 overflow-y-auto py-4 px-3 space-y-6 text-sm">
                @include('components.layouts.partials.admin-nav-items')
            </nav>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="h-16 border-b border-ink-200 bg-white flex items-center justify-between px-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <button type="button" id="mobile-nav-open" aria-label="فتح القائمة" class="lg:hidden flex size-11 -ms-2 items-center justify-center rounded-md text-ink-600 hover:bg-ink-100">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                    </button>
                    <h1 class="text-base font-semibold text-ink-900">{{ $title ?? 'لوحة التحكم' }}</h1>
                </div>
                @php $me = auth('admin')->user(); @endphp
                <details data-menu class="relative">
                    <summary class="flex cursor-pointer list-none items-center gap-2.5 rounded-full py-1 ps-3 pe-1 hover:bg-ink-50" aria-label="قائمة الحساب">
                        <span class="hidden text-sm font-medium text-ink-700 sm:inline">{{ $me->name }}</span>
                        <x-admin-avatar :admin="$me" />
                    </summary>
                    <div class="absolute end-0 top-full z-30 mt-3 w-80 overflow-hidden rounded-xl border border-ink-200 bg-white shadow-xl">
                        <div class="flex items-center gap-4 border-b border-ink-100 px-5 py-5">
                            <x-admin-avatar :admin="$me" size="size-14" class="!text-lg" />
                            <div class="min-w-0">
                                <p class="truncate text-base font-semibold text-ink-900">{{ $me->name }}</p>
                                <p class="truncate text-sm text-ink-400" dir="ltr">{{ $me->email }}</p>
                            </div>
                        </div>
                        <div class="p-2">
                            <a href="{{ route('admin.profile.edit') }}" class="flex items-center gap-3 rounded-lg px-4 py-3.5 text-base text-ink-800 hover:bg-ink-50">
                                <x-icon name="users" class="size-5 text-ink-500" />
                                الملف الشخصي
                            </a>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-4 py-3.5 text-base text-status-danger hover:bg-status-danger/5">
                                    <x-icon name="logout" class="size-5" />
                                    تسجيل الخروج
                                </button>
                            </form>
                        </div>
                    </div>
                </details>
            </header>

            <main id="main-content" tabindex="-1" class="flex-1 p-4 outline-none sm:p-6">
                @if (session('status'))
                    <div class="mb-6 rounded-md bg-status-success/10 border border-status-success/30 px-4 py-3 text-sm text-status-success">
                        {{ session('status') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Confirmation dialog driven by admin-ui.js: any form with data-confirm
         opens this instead of a native confirm(), so the wording can say what
         will happen and whether it can be undone. --}}
    <dialog id="confirm-dialog" class="m-auto w-[calc(100%-2rem)] max-w-sm rounded-lg border border-ink-200 bg-white p-0 text-ink-900 shadow-xl backdrop:bg-black/50">
        <div class="p-6">
            <h2 data-confirm-title class="text-base font-semibold"></h2>
            <p data-confirm-message class="mt-2 text-sm text-ink-600"></p>
        </div>
        <div class="flex justify-end gap-2 border-t border-ink-100 bg-ink-50 px-6 py-3">
            <button type="button" data-confirm-cancel class="btn-secondary">إلغاء</button>
            <button type="button" data-confirm-accept class="btn-danger"></button>
        </div>
    </dialog>
</body>
</html>
