<x-layouts.admin title="التقارير والتحليلات">
    @vite(['resources/js/analytics-charts.js'])

    <form method="GET" class="flex flex-wrap items-center gap-2 mb-6">
        @foreach (['today' => 'اليوم', '7d' => '7 أيام', '30d' => '30 يومًا', '90d' => '90 يومًا'] as $value => $label)
            <a href="{{ route('admin.analytics.index', ['period' => $value]) }}"
               class="rounded-md px-3 py-1.5 text-sm font-medium {{ $period === $value ? 'bg-brand-yellow text-brand-black' : 'bg-white border border-ink-200 text-ink-600 hover:bg-ink-50' }}">
                {{ $label }}
            </a>
        @endforeach

        <div class="flex items-center gap-2 ms-2">
            <input type="date" name="from" value="{{ $period === 'custom' ? $from->toDateString() : '' }}"
                   class="rounded-md border border-ink-200 px-2.5 py-1.5 text-sm">
            <span class="text-ink-400 text-sm">إلى</span>
            <input type="date" name="to" value="{{ $period === 'custom' ? $to->toDateString() : '' }}"
                   class="rounded-md border border-ink-200 px-2.5 py-1.5 text-sm">
            <input type="hidden" name="period" value="custom">
            <button type="submit" class="rounded-md bg-brand-black text-white text-sm font-medium px-3 py-1.5">تطبيق</button>
        </div>
    </form>

    <p class="text-xs text-ink-400 mb-6">
        الفترة: {{ $from->translatedFormat('d M Y') }} — {{ $to->translatedFormat('d M Y') }}.
        البيانات مُجمّعة يوميًا بواسطة مهمة مجدولة (<code class="font-mono">analytics:aggregate</code>) — إذا لم يعمل المجدول بعد فقد تظهر بعض الأيام صفرًا.
    </p>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @foreach ($charts as $key => $chart)
            <div class="rounded-lg border border-ink-200 bg-white p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-ink-900">{{ $chart['label'] }}</h2>
                    <span class="text-lg font-semibold text-ink-900 tabular-nums">{{ number_format($chart['total']) }}</span>
                </div>
                <div class="h-56">
                    <canvas data-chart="{{ json_encode(['type' => 'line', 'labels' => $chart['labels'], 'datasets' => $chart['datasets']]) }}"></canvas>
                </div>
            </div>
        @endforeach
    </div>
</x-layouts.admin>
