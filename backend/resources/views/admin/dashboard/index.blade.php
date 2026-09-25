<x-layouts.admin title="لوحة التحكم">
    {{-- Primary metrics: the three numbers that answer "how big is the
         platform right now" at a glance — larger than the rest, per
         docs/UI_UX_DESIGN_SYSTEM_V2.md's hierarchy rule (§23 of the redesign
         brief: not every KPI deserves equal visual weight). --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
        <x-kpi label="إجمالي المستخدمين" :value="$stats['users_total']" icon="users" size="lg" />
        <x-kpi label="إجمالي المركبات" :value="$stats['vehicles_total']" icon="car" size="lg" />
        <x-kpi label="الرموز المربوطة" :value="$stats['tags_assigned']" icon="qrcode" size="lg" />
    </div>

    {{-- Secondary metrics: supporting detail, visually subordinate. --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
        <x-kpi label="المستخدمون النشطون" :value="$stats['users_active']" icon="users" />
        <x-kpi label="المركبات النشطة" :value="$stats['vehicles_active']" icon="car" />
        <x-kpi label="الرموز المتاحة" :value="$stats['tags_available']" icon="qrcode" />
        <x-kpi label="الرموز الملغاة" :value="$stats['tags_revoked']" icon="qrcode" />
    </div>

    <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-semibold text-ink-900">يحتاج انتباهك</h2>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
        <x-card>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-ink-900">طلبات التواصل الجديدة</h3>
                <span class="inline-flex items-center rounded-full bg-brand-yellow/20 text-ink-900 text-xs font-medium px-2.5 py-1">
                    {{ $stats['contact_requests_new'] }}
                </span>
            </div>
            @if ($stats['contact_requests_new'] === 0)
                <p class="text-sm text-ink-400">لا توجد طلبات تواصل جديدة حاليًا.</p>
            @else
                <p class="text-sm text-ink-600 mb-3">يوجد {{ $stats['contact_requests_new'] }} طلب تواصل بانتظار المراجعة.</p>
                <a href="{{ route('admin.contact-requests.index') }}" class="text-sm font-medium text-ink-900 hover:underline">عرض الطلبات ←</a>
            @endif
        </x-card>

        <x-card>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-ink-900">أحداث أمنية غير محلولة</h3>
                <span class="inline-flex items-center rounded-full {{ $stats['security_events_unresolved'] > 0 ? 'bg-status-danger/10 text-status-danger' : 'bg-status-success/10 text-status-success' }} text-xs font-medium px-2.5 py-1">
                    {{ $stats['security_events_unresolved'] }}
                </span>
            </div>
            @if ($stats['security_events_unresolved'] === 0)
                <p class="text-sm text-ink-400">لا توجد أحداث أمنية غير محلولة.</p>
            @else
                <p class="text-sm text-ink-600 mb-3">يتطلب {{ $stats['security_events_unresolved'] }} حدثًا أمنيًا المراجعة.</p>
                <a href="{{ route('admin.security.index') }}" class="text-sm font-medium text-status-danger hover:underline">مراجعة الأحداث ←</a>
            @endif
        </x-card>
    </div>

    <x-card class="mb-8" padding="p-0">
        <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
            <h3 class="text-sm font-semibold text-ink-900">آخر النشاط</h3>
            <a href="{{ route('admin.reports.index') }}" class="text-xs font-medium text-ink-500 hover:text-ink-900 hover:underline">التقارير ←</a>
        </div>
        @forelse ($activity as $item)
            <div class="flex items-center gap-3 border-b border-ink-100 px-5 py-3 last:border-0">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-full {{ $item['urgent'] ? 'bg-brand-yellow' : 'bg-ink-100' }}">
                    <x-icon :name="$item['urgent'] ? 'warning' : 'contact'" class="size-4 text-ink-800" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm {{ $item['urgent'] ? 'font-semibold' : 'font-medium' }} text-ink-900">{{ $item['label'] }}</p>
                    @if ($item['vehicle'])<p class="truncate text-xs text-ink-400">{{ $item['vehicle'] }}</p>@endif
                </div>
                <time class="shrink-0 text-xs text-ink-400" datetime="{{ $item['at']->toIso8601String() }}">{{ $item['at']->diffForHumans() }}</time>
            </div>
        @empty
            <p class="px-5 py-8 text-center text-sm text-ink-400">لا يوجد نشاط بعد. تظهر هنا تفاعلات الزوار مع الرموز فور حدوثها.</p>
        @endforelse
    </x-card>

    {{-- Contextual link into the real analytics/charts page instead of
         duplicating charts on the home page (audit finding: charts exist
         but were unreachable from the dashboard home). --}}
    <x-card>
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <x-icon name="chart" class="size-5 text-ink-400" />
                <div>
                    <h3 class="text-sm font-semibold text-ink-900">التحليلات والاتجاهات</h3>
                    <p class="text-xs text-ink-500">عمليات المسح، التسجيلات، التفعيلات، وطلبات التواصل عبر الزمن</p>
                </div>
            </div>
            <a href="{{ route('admin.analytics.index') }}" class="rounded-md bg-brand-black text-white text-sm font-medium px-4 py-2 hover:bg-ink-800 transition-colors">
                عرض التحليلات الكاملة
            </a>
        </div>
    </x-card>
</x-layouts.admin>
