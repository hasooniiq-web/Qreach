<!DOCTYPE html>
<html lang="ar" dir="rtl" data-dashboard>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'لوحة التحكم' }} — Qreach</title>
    <meta name="poll-url" content="{{ route('admin.poll') }}">
    @stack('head')
    <script src="{{ asset('theme-init.js') }}"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/admin-nav.js', 'resources/js/admin-ui.js'])
</head>
<body class="h-dvh overflow-hidden bg-canvas text-ink-900">
    <div id="nav-progress" aria-hidden="true"></div>
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-[90] focus:rounded-md focus:bg-brand-yellow focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-brand-black">تخطّي إلى المحتوى</a>

    @php
        $me = auth('admin')->user();

        // Grouped by function: platform data, visitor interaction, insight,
        // security, and administration. Every entry routes to a real screen.
        $nav = [
            'الرئيسية' => [
                ['icon' => 'dashboard', 'label' => 'لوحة التحكم', 'route' => 'admin.dashboard'],
            ],
            'إدارة المنصة' => [
                ['icon' => 'users', 'label' => 'المستخدمون', 'route' => 'admin.users.index', 'permission' => 'users.view'],
                ['icon' => 'car', 'label' => 'المركبات', 'route' => 'admin.vehicles.index', 'permission' => 'vehicles.view'],
                ['icon' => 'qrcode', 'label' => 'رموز QR و NFC', 'route' => 'admin.tags.index', 'permission' => 'qr.view,nfc.view'],
                ['icon' => 'batch', 'label' => 'دفعات الرموز', 'route' => 'admin.tag-batches.index', 'permission' => 'qr.view,printing.view'],
            ],
            'التفاعل' => [
                ['icon' => 'contact', 'label' => 'طلبات التواصل', 'route' => 'admin.contact-requests.index', 'badge' => 'contact_requests', 'permission' => 'contact_requests.view'],
                ['icon' => 'contact', 'label' => 'رسائل الموقع', 'route' => 'admin.site-messages.index', 'badge' => 'site_messages', 'permission' => 'contact_requests.view'],
                ['icon' => 'shield', 'label' => 'بلاغات المحادثات', 'route' => 'admin.conversation-reports.index', 'badge' => 'conversation_reports', 'permission' => 'contact_requests.view'],
                ['icon' => 'contact', 'label' => 'الدعم الفني', 'route' => 'admin.support.index', 'badge' => 'support', 'permission' => 'contact_requests.view'],
                ['icon' => 'warning', 'label' => 'التنبيهات الطارئة', 'route' => 'admin.emergencies.index', 'permission' => 'emergency.view'],
            ],
            'التحليلات' => [
                ['icon' => 'chart', 'label' => 'التحليلات', 'route' => 'admin.analytics.index', 'permission' => 'analytics.view'],
                ['icon' => 'audit', 'label' => 'التقارير', 'route' => 'admin.reports.index', 'permission' => 'reports.view'],
            ],
            'الأمان' => [
                ['icon' => 'shield', 'label' => 'الأحداث الأمنية', 'route' => 'admin.security.index', 'permission' => 'security.view'],
                ['icon' => 'audit', 'label' => 'سجلات التدقيق', 'route' => 'admin.audit.index', 'permission' => 'audit.view,audit_logs.view'],
            ],
            'الإدارة' => [
                ['icon' => 'star', 'label' => 'الاشتراكات والباقات', 'route' => 'admin.subscriptions.index', 'badge' => 'upgrade_requests', 'permission' => 'subscriptions.view'],
                ['icon' => 'audit', 'label' => 'المدفوعات', 'route' => 'admin.payments.index', 'permission' => 'payments.view'],
                ['icon' => 'batch', 'label' => 'المخزون والحسابات', 'route' => 'admin.trade.index', 'permission' => 'trade.view'],
                ['icon' => 'star', 'label' => 'قسائم التفعيل', 'route' => 'admin.vouchers.index', 'permission' => 'vouchers.view'],
                ['icon' => 'settings', 'label' => 'بوابات الدفع', 'route' => 'admin.payment-gateways.index', 'permission' => 'payment_gateways.view'],
                ['icon' => 'users', 'label' => 'المسؤولون', 'route' => 'admin.admins.index', 'permission' => 'admins.view'],
                ['icon' => 'shield', 'label' => 'الأدوار والصلاحيات', 'route' => 'admin.roles.index', 'permission' => 'roles.view'],
                ['icon' => 'audit', 'label' => 'قائمة الصلاحيات', 'route' => 'admin.permissions.index', 'permission' => 'permissions.view'],
                ['icon' => 'settings', 'label' => 'إعدادات النظام', 'route' => 'admin.settings.index', 'permission' => 'settings.view'],
            ],
        ];

        // The nav only ever shows a link the admin can actually open — hiding a
        // link is never the only guard (every route still checks the same
        // permission server-side), but showing a link that 403s is bad UX.
        $nav = collect($nav)->map(fn ($items) => collect($items)->filter(
            fn ($item) => ! isset($item['permission'])
                || collect(explode(',', $item['permission']))->contains(fn ($key) => $me->hasPermission(trim($key)))
        ))->filter(fn ($items) => $items->isNotEmpty())->all();

        // TEMPORARY dev aid, shown only while DEV_OTP_INBOX=true (never in production).
        if (\App\Http\Controllers\Admin\DevOtpController::enabled()) {
            $nav['مؤقت — تطوير'] = [['icon' => 'audit', 'label' => 'رموز OTP', 'route' => 'admin.dev-otp']];
        }

        // Breadcrumbs from the route name: Home > Section > (current page).
        $sections = [
            'admin.users' => ['المستخدمون', 'admin.users.index'],
            'admin.vehicles' => ['المركبات', 'admin.vehicles.index'],
            'admin.tags' => ['رموز QR و NFC', 'admin.tags.index'],
            'admin.tag-batches' => ['دفعات الرموز', 'admin.tag-batches.index'],
            'admin.contact-requests' => ['طلبات التواصل', 'admin.contact-requests.index'],
            'admin.site-messages' => ['رسائل الموقع', 'admin.site-messages.index'],
            'admin.support' => ['الدعم الفني', 'admin.support.index'],
            'admin.conversation-reports' => ['بلاغات المحادثات', 'admin.conversation-reports.index'],
            'admin.emergencies' => ['التنبيهات الطارئة', 'admin.emergencies.index'],
            'admin.analytics' => ['التحليلات', 'admin.analytics.index'],
            'admin.reports' => ['التقارير', 'admin.reports.index'],
            'admin.security' => ['الأحداث الأمنية', 'admin.security.index'],
            'admin.audit' => ['سجلات التدقيق', 'admin.audit.index'],
            'admin.subscriptions' => ['الاشتراكات والباقات', 'admin.subscriptions.index'],
            'admin.plans' => ['الاشتراكات والباقات', 'admin.subscriptions.index'],
            'admin.payments' => ['المدفوعات', 'admin.payments.index'],
            'admin.trade' => ['المخزون والحسابات', 'admin.trade.index'],
            'admin.vouchers' => ['قسائم التفعيل', 'admin.vouchers.index'],
            'admin.payment-gateways' => ['بوابات الدفع', 'admin.payment-gateways.index'],
            'admin.admins' => ['المسؤولون والصلاحيات', 'admin.admins.index'],
            'admin.settings' => ['إعدادات النظام', 'admin.settings.index'],
            'admin.profile' => ['الملف الشخصي', null],
            'admin.search' => ['البحث', null],
        ];
        $routeName = \Illuminate\Support\Facades\Route::currentRouteName() ?? '';
        $sectionKey = implode('.', array_slice(explode('.', $routeName), 0, 2));
        $crumbs = [];
        if ($routeName !== 'admin.dashboard') {
            $crumbs[] = ['لوحة التحكم', route('admin.dashboard')];
            if (isset($sections[$sectionKey])) {
                [$sectionLabel, $sectionRoute] = $sections[$sectionKey];
                $isIndex = $sectionRoute && $routeName === $sectionRoute;
                $crumbs[] = [$sectionLabel, ($isIndex || ! $sectionRoute) ? null : route($sectionRoute)];
                if (! $isIndex && ($title ?? $sectionLabel) !== $sectionLabel) {
                    $crumbs[] = [$title ?? '', null];
                }
            } else {
                $crumbs[] = [$title ?? '', null];
            }
        }

        // What needs attention, for the bell.
        $alerts = [
            'contact_requests' => \App\Models\ContactRequest::where('status', 'new')->count(),
            'site_messages' => \App\Models\SiteMessage::where('status', 'new')->count(),
            'support' => \App\Models\SupportTicket::where('status', 'open')->count(),
            'conversation_reports' => \App\Models\ConversationReport::where('status', 'new')->count(),
            'upgrade_requests' => \App\Models\PlanUpgradeRequest::where('status', 'pending')->count(),
            'security' => \App\Models\SecurityEvent::where('resolved', false)->whereIn('severity', ['warning', 'critical'])->count(),
        ];
    @endphp

    <div class="flex h-dvh">
        <!-- Desktop sidebar: full width, or icon-only when collapsed (state on <html data-sidebar>) -->
        <aside id="desktop-sidebar" class="hidden h-dvh shrink-0 flex-col border-e border-ink-200 bg-surface lg:flex" aria-label="القائمة الجانبية">
            @include('components.layouts.partials.admin-sidebar')
        </aside>

        <!-- Mobile/tablet drawer -->
        <div id="mobile-nav-backdrop" hidden class="fixed inset-0 z-40 bg-black/50 backdrop-blur-[2px] lg:hidden"></div>
        <aside id="mobile-nav" class="fixed inset-y-0 start-0 z-50 flex w-72 -translate-x-full flex-col border-e border-ink-200 bg-surface shadow-(--shadow-pop) transition-transform duration-200 lg:hidden rtl:translate-x-full" aria-label="القائمة الجانبية">
            @include('components.layouts.partials.admin-sidebar', ['drawer' => true])
        </aside>

        <div class="flex h-dvh min-w-0 flex-1 flex-col">
            @if (config('app.test_banner'))
                <div class="shrink-0 bg-brand-yellow px-3 py-1 text-center text-xs font-bold text-brand-black" role="status">
                    بيئة اختبار — البيانات هنا غير حقيقية
                </div>
            @endif
            <header class="sticky top-0 z-[35] flex h-14 shrink-0 items-center gap-2 border-b border-ink-200 bg-surface/90 px-3 backdrop-blur sm:px-5">
                <button type="button" id="mobile-nav-open" aria-label="فتح القائمة" class="btn-ghost !size-9 !px-0 lg:hidden">
                    <x-icon name="menu" class="size-5" />
                </button>
                <button type="button" id="sidebar-toggle" aria-label="إظهار/إخفاء القائمة الجانبية" aria-controls="desktop-sidebar" class="btn-ghost hidden !size-9 !px-0 lg:inline-flex">
                    <x-icon name="sidebar" class="size-[18px]" />
                </button>
                <span class="mx-1 hidden h-5 w-px bg-ink-200 lg:block"></span>

                <nav aria-label="مسار التنقل" class="hidden min-w-0 items-center gap-1.5 text-sm sm:flex">
                    @forelse ($crumbs as $i => [$label, $url])
                        @if ($i > 0)<x-icon name="chevron-left" class="size-3.5 shrink-0 text-ink-300" />@endif
                        @if ($url)
                            <a href="{{ $url }}" class="truncate text-ink-500 transition-colors hover:text-ink-900">{{ $label }}</a>
                        @else
                            <span class="truncate font-medium text-ink-900" @if ($loop->last) aria-current="page" @endif>{{ $label }}</span>
                        @endif
                    @empty
                        <span class="font-medium text-ink-900" aria-current="page">{{ $title ?? 'لوحة التحكم' }}</span>
                    @endforelse
                </nav>
                <h1 class="truncate text-sm font-semibold sm:hidden">{{ $title ?? 'لوحة التحكم' }}</h1>

                <div class="ms-auto flex items-center gap-1">
                    <form method="GET" action="{{ route('admin.search') }}" role="search" class="relative me-1 hidden md:block">
                        <x-icon name="search" class="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-ink-400" />
                        <input id="global-search" type="search" name="q" value="{{ request()->routeIs('admin.search') ? request('q') : '' }}" autocomplete="off"
                               placeholder="بحث سريع..." aria-label="بحث عام"
                               class="form-control !h-9 w-56 !bg-ink-50 ps-9 pe-9 transition-[width] focus:w-72">
                        <span class="kbd pointer-events-none absolute end-2.5 top-1/2 -translate-y-1/2">/</span>
                    </form>
                    <a href="{{ route('admin.search') }}" class="btn-ghost !size-9 !px-0 md:hidden" aria-label="بحث"><x-icon name="search" class="size-[18px]" /></a>

                    {{-- Quick actions --}}
                    @if ($me->hasPermission('qr.generate_batch') || $me->hasPermission('admins.create'))
                        <details data-menu class="relative">
                            <summary class="btn-ghost !size-9 cursor-pointer list-none !px-0" aria-label="إجراءات سريعة"><x-icon name="plus" class="size-[18px]" /></summary>
                            <div class="menu-panel absolute end-0 top-full z-40 mt-2 w-56 rounded-xl border border-ink-200 bg-surface p-1.5 shadow-(--shadow-pop)">
                                <p class="px-2.5 py-1.5 text-xs font-medium text-ink-400">إجراءات سريعة</p>
                                @if ($me->hasPermission('qr.generate_batch'))
                                    <a href="{{ route('admin.tag-batches.create') }}" class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm text-ink-800 hover:bg-ink-100"><x-icon name="batch" class="size-4 text-ink-500" /> دفعة رموز جديدة</a>
                                @endif
                                @if ($me->hasPermission('admins.create'))
                                    <a href="{{ route('admin.admins.create') }}" class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm text-ink-800 hover:bg-ink-100"><x-icon name="users" class="size-4 text-ink-500" /> مسؤول جديد</a>
                                @endif
                            </div>
                        </details>
                    @endif

                    {{-- Theme --}}
                    <details data-menu class="relative">
                        <summary class="btn-ghost !size-9 cursor-pointer list-none !px-0" aria-label="المظهر">
                            <span data-theme-icon="system"><x-icon name="monitor" class="size-[18px]" /></span>
                            <span data-theme-icon="light" class="hidden"><x-icon name="sun" class="size-[18px]" /></span>
                            <span data-theme-icon="dark" class="hidden"><x-icon name="moon" class="size-[18px]" /></span>
                        </summary>
                        <div class="menu-panel absolute end-0 top-full z-40 mt-2 w-44 rounded-xl border border-ink-200 bg-surface p-1.5 shadow-(--shadow-pop)">
                            @foreach (['light' => ['فاتح', 'sun'], 'dark' => ['داكن', 'moon'], 'system' => ['حسب الجهاز', 'monitor']] as $mode => [$modeLabel, $modeIcon])
                                <button type="button" data-theme-set="{{ $mode }}" class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm text-ink-800 hover:bg-ink-100">
                                    <x-icon :name="$modeIcon" class="size-4 text-ink-500" /> {{ $modeLabel }}
                                    <x-icon name="check" class="ms-auto hidden size-4 text-ink-900" data-theme-check="{{ $mode }}" />
                                </button>
                            @endforeach
                        </div>
                    </details>

                    {{-- Notifications: what needs attention right now --}}
                    <details data-menu class="relative">
                        <summary class="btn-ghost relative !size-9 cursor-pointer list-none !px-0" aria-label="التنبيهات">
                            <x-icon name="bell" class="size-[18px]" />
                            <span data-bell-dot @class(['absolute end-2 top-2 size-2 rounded-full bg-brand-yellow ring-2 ring-surface', 'hidden' => array_sum($alerts) === 0])></span>
                        </summary>
                        <div class="menu-panel absolute end-0 top-full z-40 mt-2 w-80 max-w-[calc(100vw-1.5rem)] rounded-xl border border-ink-200 bg-surface p-1.5 shadow-(--shadow-pop)">
                            <p class="px-2.5 py-1.5 text-xs font-medium text-ink-400">يحتاج انتباهك</p>
                            @php
                                $items = [
                                    ['contact_requests', 'طلبات تواصل جديدة', 'contact', route('admin.contact-requests.index', ['status' => 'new'])],
                                    ['site_messages', 'رسائل جديدة من الموقع', 'contact', route('admin.site-messages.index', ['status' => 'new'])],
                                    ['support', 'تذاكر دعم فني بانتظار الرد', 'contact', route('admin.support.index', ['status' => 'open'])],
                                    ['conversation_reports', 'بلاغات محادثات جديدة', 'shield', route('admin.conversation-reports.index', ['status' => 'new'])],
                                    ['upgrade_requests', 'طلبات ترقية بانتظار المراجعة', 'star', route('admin.subscriptions.requests')],
                                    ['security', 'أحداث أمنية غير محلولة', 'shield', route('admin.security.index')],
                                ];
                            @endphp
                            @foreach ($items as [$key, $text, $ico, $url])
                                <a href="{{ $url }}" data-alert-row="{{ $key }}" @class(['flex items-center gap-3 rounded-lg px-2.5 py-2.5 text-sm hover:bg-ink-100', 'hidden' => $alerts[$key] === 0])>
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-ink-100 text-ink-600"><x-icon :name="$ico" class="size-4" /></span>
                                    <span class="flex-1 text-ink-800">{{ $text }}</span>
                                    <span data-alert-count="{{ $key }}" class="rounded-full bg-brand-yellow px-2 text-xs font-bold text-brand-black">{{ $alerts[$key] }}</span>
                                </a>
                            @endforeach
                            <p data-alert-empty @class(['px-2.5 py-6 text-center text-sm text-ink-400', 'hidden' => array_sum($alerts) > 0])>لا شيء يحتاج انتباهك الآن</p>
                        </div>
                    </details>

                    <span class="mx-1 hidden h-5 w-px bg-ink-200 sm:block"></span>

                    {{-- Account --}}
                    <details data-menu class="relative">
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg py-1 ps-2 pe-1 hover:bg-ink-100" aria-label="قائمة الحساب">
                            <span class="hidden text-sm font-medium text-ink-700 sm:inline">{{ $me->name }}</span>
                            <x-admin-avatar :admin="$me" />
                        </summary>
                        <div class="menu-panel absolute end-0 top-full z-40 mt-2 w-64 overflow-hidden rounded-xl border border-ink-200 bg-surface shadow-(--shadow-pop)">
                            <div class="flex items-center gap-3 border-b border-ink-100 px-4 py-4">
                                <x-admin-avatar :admin="$me" size="size-10" class="!text-sm" />
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-ink-900">{{ $me->name }}</p>
                                    <p class="truncate text-xs text-ink-500" dir="ltr">{{ $me->email }}</p>
                                </div>
                            </div>
                            <div class="p-1.5">
                                <a href="{{ route('admin.profile.edit') }}" class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm text-ink-800 hover:bg-ink-100">
                                    <x-icon name="users" class="size-4 text-ink-500" /> الملف الشخصي
                                </a>
                                <form method="POST" action="{{ route('admin.logout') }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm text-status-danger hover:bg-status-danger/10">
                                        <x-icon name="logout" class="size-4" /> تسجيل الخروج
                                    </button>
                                </form>
                            </div>
                        </div>
                    </details>
                </div>
            </header>

            <main id="main-content" tabindex="-1" class="min-h-0 flex-1 overflow-y-auto outline-none">
                @php $body = (string) $slot; @endphp
                <div class="mx-auto w-full max-w-[110rem] p-4 sm:p-6 lg:px-10 lg:py-8">
                    {{-- Pages that render their own <x-page-header> (marked data-page-header) keep it;
                         every other page gets the same large title so no page starts without one. --}}
                    @unless (str_contains($body, 'data-page-header'))
                        <div data-page-header class="mb-6">
                            <h2 class="text-2xl font-bold tracking-tight text-ink-900">{{ $title ?? 'لوحة التحكم' }}</h2>
                        </div>
                    @endunless
                    {!! $body !!}
                </div>
            </main>
        </div>
    </div>

    {{-- Toasts: flash messages slide in and dismiss themselves. --}}
    <div id="toasts" class="pointer-events-none fixed inset-x-0 top-4 z-[90] flex flex-col items-center gap-2 px-4" aria-live="polite">
        @if (session('status'))
            <div class="toast pointer-events-auto flex max-w-md items-start gap-3 rounded-xl border border-ink-200 bg-surface px-4 py-3 shadow-(--shadow-pop)" role="status" data-toast>
                <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-status-success text-white"><x-icon name="check" class="size-3.5" /></span>
                <p class="text-sm text-ink-800">{{ session('status') }}</p>
                <button type="button" data-toast-close class="ms-2 text-ink-400 hover:text-ink-900" aria-label="إغلاق"><x-icon name="x" class="size-4" /></button>
            </div>
        @endif
    </div>

    {{-- Confirmation dialog driven by admin-ui.js: any form with data-confirm
         opens this instead of a native confirm(), so the wording can say what
         will happen and whether it can be undone. --}}
    <dialog id="confirm-dialog" class="m-auto w-[calc(100%-2rem)] max-w-md rounded-xl border border-ink-200 bg-surface p-0 text-ink-900 shadow-(--shadow-pop)">
        <div class="flex gap-4 p-6">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-status-danger/10 text-status-danger"><x-icon name="warning" class="size-5" /></span>
            <div>
                <h2 data-confirm-title class="text-base font-semibold"></h2>
                <p data-confirm-message class="mt-1.5 text-sm text-ink-600"></p>
            </div>
        </div>
        {{-- Shown only for forms that declare data-reason-name (e.g. cancelling a code). --}}
        <div data-confirm-reason-wrap class="hidden px-6 pb-5">
            <label for="confirm-reason" data-confirm-reason-label class="mb-1.5 block text-sm font-medium text-ink-700"></label>
            <textarea id="confirm-reason" data-confirm-reason rows="3" maxlength="255" class="form-control"></textarea>
            <p data-confirm-reason-error class="mt-1 hidden text-xs text-status-danger" role="alert">هذا الحقل مطلوب.</p>
        </div>
        <div class="flex justify-end gap-2 border-t border-ink-100 bg-ink-50/70 px-6 py-3">
            <button type="button" data-confirm-cancel class="btn-secondary">إلغاء</button>
            <button type="button" data-confirm-accept class="btn-primary"></button>
        </div>
    </dialog>
</body>
</html>
