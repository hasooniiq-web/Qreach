<x-layouts.admin title="التحليلات والتقارير">
    @vite(['resources/js/analytics-charts.js'])

    <div class="mb-2 flex flex-wrap items-center justify-between gap-4">
        <nav aria-label="الفترة الزمنية" class="inline-flex gap-1 rounded-lg bg-ink-100 p-1 text-sm">
            @foreach (['today' => 'اليوم', '7d' => '7 أيام', '30d' => '30 يومًا', '90d' => '90 يومًا'] as $value => $label)
                <a href="{{ route('admin.analytics.index', ['period' => $value]) }}"
                   @if ($period === $value) aria-current="true" @endif
                   class="rounded-md px-3.5 py-1.5 font-medium transition-all {{ $period === $value ? 'bg-white text-ink-900 shadow-xs' : 'text-ink-500 hover:text-ink-900' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="date" name="from" value="{{ $period === 'custom' ? $from->toDateString() : '' }}" aria-label="من تاريخ" class="form-control w-auto">
            <span class="text-sm text-ink-400">إلى</span>
            <input type="date" name="to" value="{{ $period === 'custom' ? $to->toDateString() : '' }}" aria-label="إلى تاريخ" class="form-control w-auto">
            <input type="hidden" name="period" value="custom">
            <button type="submit" class="btn-primary">تطبيق</button>
        </form>
    </div>

    <p class="mb-6 text-xs text-ink-400">
        الفترة: {{ $from->translatedFormat('d M Y') }} — {{ $to->translatedFormat('d M Y') }}.
        البيانات مُجمّعة يوميًا بواسطة مهمة مجدولة (<code class="font-mono">analytics:aggregate</code>) — إذا لم يعمل المجدول بعد فقد تظهر بعض الأيام صفرًا.
    </p>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        @foreach ($charts as $key => $chart)
            <x-card>
                <div class="mb-4 flex items-baseline justify-between">
                    <h2 class="text-sm font-semibold text-ink-900">{{ $chart['label'] }}</h2>
                    <div class="text-end">
                        <span class="text-2xl font-bold tabular-nums text-ink-900">{{ number_format($chart['total']) }}</span>
                        <span class="block text-xs text-ink-400">إجمالي الفترة</span>
                    </div>
                </div>
                <div class="h-56">
                    <canvas role="img" aria-label="{{ $chart['label'] }}: إجمالي {{ number_format($chart['total']) }} خلال الفترة المحددة"
                            data-chart="{{ json_encode(['type' => 'line', 'labels' => $chart['labels'], 'datasets' => $chart['datasets']]) }}"></canvas>
                </div>
            </x-card>
        @endforeach
    </div>
</x-layouts.admin>
