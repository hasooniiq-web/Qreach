<x-layouts.admin title="لوحة التحكم">
    @vite(['resources/js/analytics-charts.js'])

    @php $me = auth('admin')->user(); @endphp

    {{-- Overview / welcome --}}
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4" data-page-header>
        <div>
            <p class="text-sm text-ink-500">{{ now()->translatedFormat('l، d F Y') }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">مرحبًا، {{ $me->name }}</h2>
            <p class="mt-1 text-sm text-ink-500">هذه لمحة عن منصة Qreach اليوم.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($me->hasPermission('qr.generate_batch'))
                <a href="{{ route('admin.tag-batches.create') }}" class="btn-accent"><x-icon name="plus" class="size-4" /> دفعة رموز جديدة</a>
            @endif
            <a href="{{ route('admin.analytics.index') }}" class="btn-secondary"><x-icon name="chart" class="size-4" /> التحليلات</a>
        </div>
    </div>

    {{-- KPIs: real numbers, real change versus the previous 7 days, real 14-day sparkline. --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-kpi label="المستخدمون" :value="$kpis['users']['value']" icon="users" size="lg" :trend="$kpis['users']['trend']" :spark="$kpis['users']['spark']" hint="مقابل الأسبوع السابق" :href="route('admin.users.index')" />
        <x-kpi label="المركبات" :value="$kpis['vehicles']['value']" icon="car" size="lg" :trend="$kpis['vehicles']['trend']" :spark="$kpis['vehicles']['spark']" hint="مقابل الأسبوع السابق" :href="route('admin.vehicles.index')" />
        <x-kpi label="الرموز المربوطة" :value="$kpis['tags']['value']" icon="qrcode" size="lg" :trend="$kpis['tags']['trend']" :spark="$kpis['tags']['spark']" hint="مقابل الأسبوع السابق" :href="route('admin.tags.index', ['status' => 'assigned'])" />
        <x-kpi label="عمليات المسح (7 أيام)" :value="$kpis['scans']['value']" icon="chart" size="lg" tone="accent" :trend="$kpis['scans']['trend']" :spark="$kpis['scans']['spark']" hint="مقابل الأسبوع السابق" :href="route('admin.analytics.index')" />
    </div>

    {{-- Analytics --}}
    <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <div class="mb-1 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold text-ink-900">النشاط خلال 30 يومًا</h3>
                    <p class="text-sm text-ink-500">عمليات المسح وطلبات التواصل يوميًا</p>
                </div>
                <a href="{{ route('admin.analytics.index') }}" class="btn-ghost !h-8 !px-2.5 text-xs">التفاصيل <x-icon name="arrow-up-right" class="size-3.5" /></a>
            </div>
            <div class="skeleton mt-4 h-64" data-chart-box>
                <canvas role="img" aria-label="عمليات المسح وطلبات التواصل خلال آخر 30 يومًا"
                        data-chart="{{ json_encode($chart) }}"></canvas>
            </div>
        </x-card>

        <x-card>
            <h3 class="text-base font-semibold text-ink-900">حالة الرموز</h3>
            <p class="text-sm text-ink-500">توزيع {{ number_format($stats['tags_total']) }} رمزًا</p>
            <div class="skeleton mx-auto mt-4 h-48 max-w-[12rem] !rounded-full" data-chart-box>
                <canvas role="img" aria-label="توزيع الرموز حسب الحالة" data-chart="{{ json_encode($tagBreakdown) }}"></canvas>
            </div>
            <ul class="mt-5 space-y-2 text-sm">
                @foreach ($tagBreakdown['labels'] as $i => $label)
                    <li class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-ink-600"><span class="size-2.5 rounded-full" data-legend-dot="{{ $i }}"></span>{{ $label }}</span>
                        <span class="font-medium tabular-nums text-ink-900">{{ number_format($tagBreakdown['datasets'][0]['data'][$i]) }}</span>
                    </li>
                @endforeach
            </ul>
        </x-card>
    </div>

    {{-- Needs attention + activity --}}
    <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-5">
        <x-card class="lg:col-span-2" padding="p-0">
            <div class="border-b border-ink-100 px-5 py-4">
                <h3 class="text-base font-semibold text-ink-900">يحتاج انتباهك</h3>
                <p class="text-sm text-ink-500">أهم ما يستحق إجراءً الآن</p>
            </div>
            @forelse ($insights as $item)
                @php
                    $tone = ['accent' => 'bg-brand-yellow/20 text-ink-900', 'danger' => 'bg-status-danger/10 text-status-danger', 'muted' => 'bg-ink-100 text-ink-600'][$item['tone']];
                @endphp
                <a href="{{ $item['href'] }}" class="group flex items-center gap-3 border-b border-ink-100 px-5 py-3.5 transition-colors last:border-0 hover:bg-ink-50">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $tone }}"><x-icon :name="$item['icon']" class="size-4" /></span>
                    <span class="flex-1 text-sm text-ink-800">{{ $item['text'] }}</span>
                    <x-icon name="chevron-left" class="size-4 text-ink-300 transition-transform group-hover:-translate-x-0.5" />
                </a>
            @empty
                <div class="flex flex-col items-center px-6 py-12 text-center">
                    <span class="mb-3 flex size-11 items-center justify-center rounded-full bg-status-success/10 text-status-success"><x-icon name="check" class="size-5" /></span>
                    <p class="text-sm font-semibold text-ink-900">كل شيء على ما يرام</p>
                    <p class="mt-1 text-sm text-ink-500">لا توجد عناصر تحتاج إجراءً حاليًا.</p>
                </div>
            @endforelse
        </x-card>

        <x-card class="lg:col-span-3" padding="p-0">
            <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
                <div>
                    <h3 class="text-base font-semibold text-ink-900">آخر النشاط</h3>
                    <p class="text-sm text-ink-500">تفاعلات الزوار مع الرموز</p>
                </div>
                <a href="{{ route('admin.reports.index') }}" class="btn-ghost !h-8 !px-2.5 text-xs">التقارير <x-icon name="arrow-up-right" class="size-3.5" /></a>
            </div>
            @forelse ($activity as $item)
                <div class="flex items-center gap-3 border-b border-ink-100 px-5 py-3 last:border-0">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $item['urgent'] ? 'bg-brand-yellow text-brand-black' : 'bg-ink-100 text-ink-600' }}">
                        <x-icon :name="$item['urgent'] ? 'warning' : 'contact'" class="size-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm {{ $item['urgent'] ? 'font-semibold' : 'font-medium' }} text-ink-900">{{ $item['label'] }}</p>
                        @if ($item['vehicle'])<p class="truncate text-xs text-ink-500">{{ $item['vehicle'] }}</p>@endif
                    </div>
                    <time class="shrink-0 text-xs text-ink-400" datetime="{{ $item['at']->toIso8601String() }}">{{ $item['at']->diffForHumans() }}</time>
                </div>
            @empty
                <div class="flex flex-col items-center px-6 py-12 text-center">
                    <span class="mb-3 flex size-11 items-center justify-center rounded-full bg-ink-100 text-ink-500"><x-icon name="inbox" class="size-5" /></span>
                    <p class="text-sm font-semibold text-ink-900">لا يوجد نشاط بعد</p>
                    <p class="mt-1 text-sm text-ink-500">تظهر هنا تفاعلات الزوار مع الرموز فور حدوثها.</p>
                </div>
            @endforelse
        </x-card>
    </div>

    {{-- Recent records --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-card padding="p-0">
            <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
                <h3 class="text-base font-semibold text-ink-900">أحدث المستخدمين</h3>
                <a href="{{ route('admin.users.index') }}" class="btn-ghost !h-8 !px-2.5 text-xs">عرض الكل</a>
            </div>
            @forelse ($recentUsers as $u)
                <a href="{{ route('admin.users.show', $u) }}" class="flex items-center gap-3 border-b border-ink-100 px-5 py-3 transition-colors last:border-0 hover:bg-ink-50">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-ink-100 text-sm font-semibold text-ink-600">{{ mb_substr($u->name ?? '؟', 0, 1) }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-ink-900">{{ $u->display_name }}</p>
                        <p class="text-xs tabular-nums text-ink-500" dir="ltr">{{ $u->phone }}</p>
                    </div>
                    <span class="text-xs text-ink-400">{{ $u->vehicles_count }} مركبة</span>
                </a>
            @empty
                <p class="px-5 py-10 text-center text-sm text-ink-500">لا يوجد مستخدمون بعد.</p>
            @endforelse
        </x-card>

        <x-card padding="p-0">
            <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
                <h3 class="text-base font-semibold text-ink-900">أحدث المركبات</h3>
                <a href="{{ route('admin.vehicles.index') }}" class="btn-ghost !h-8 !px-2.5 text-xs">عرض الكل</a>
            </div>
            @forelse ($recentVehicles as $v)
                <a href="{{ route('admin.vehicles.show', $v) }}" class="flex items-center gap-3 border-b border-ink-100 px-5 py-3 transition-colors last:border-0 hover:bg-ink-50">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-ink-100 text-ink-600"><x-icon name="car" class="size-4" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-ink-900">{{ $v->nickname ?? (trim($v->make.' '.$v->model) ?: 'مركبة') }}</p>
                        <p class="truncate text-xs text-ink-500">{{ $v->user?->name ?? $v->user?->phone }}</p>
                    </div>
                    <span class="rounded-md border border-ink-200 px-2 py-0.5 text-xs font-medium tabular-nums text-ink-700">{{ $v->plate_number }}</span>
                </a>
            @empty
                <p class="px-5 py-10 text-center text-sm text-ink-500">لا توجد مركبات بعد.</p>
            @endforelse
        </x-card>
    </div>
</x-layouts.admin>
