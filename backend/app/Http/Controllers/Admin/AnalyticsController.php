<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsDailyStat;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Reads exclusively from analytics_daily_stats (section 43) — never
 * aggregates the raw scans/audit_logs tables at request time. If a day
 * hasn't been aggregated yet (no scheduler running), it simply shows as
 * zero rather than falling back to a live query, so the chart honestly
 * reflects what the aggregation job has actually produced.
 */
class AnalyticsController extends Controller
{
    private const METRICS = [
        'scans' => 'عمليات المسح',
        'registrations' => 'التسجيلات الجديدة',
        'qr_activations' => 'تفعيلات الرموز',
        'contact_requests' => 'طلبات التواصل',
        'notifications' => 'الإشعارات',
    ];

    public function index(Request $request): View
    {
        [$from, $to] = $this->resolveRange($request);

        $rows = AnalyticsDailyStat::whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->where('dimension', 'all')
            ->orderBy('date')
            ->get()
            ->groupBy('metric_key');

        $dates = collect();
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $dates->push($d->toDateString());
        }

        $charts = [];
        foreach (self::METRICS as $key => $labelAr) {
            $byDate = $rows->get($key, collect())->keyBy('date');
            $charts[$key] = [
                'label' => $labelAr,
                'total' => (int) $byDate->sum('value'),
                'labels' => $dates->map(fn ($d) => Carbon::parse($d)->translatedFormat('d M'))->all(),
                'datasets' => [[
                    'label' => $labelAr,
                    'data' => $dates->map(fn ($d) => (int) ($byDate->get($d)->value ?? 0))->all(),
                ]],
            ];
        }

        $period = $request->string('period', '30d')->toString();

        return view('admin.analytics.index', compact('charts', 'period', 'from', 'to'));
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function resolveRange(Request $request): array
    {
        $period = $request->string('period', '30d')->toString();
        $today = Carbon::today();

        return match ($period) {
            'today' => [$today->copy(), $today->copy()],
            '7d' => [$today->copy()->subDays(6), $today->copy()],
            '90d' => [$today->copy()->subDays(89), $today->copy()],
            'custom' => [
                $request->filled('from') ? Carbon::parse($request->string('from')) : $today->copy()->subDays(29),
                $request->filled('to') ? Carbon::parse($request->string('to')) : $today->copy(),
            ],
            default => [$today->copy()->subDays(29), $today->copy()],
        };
    }
}
