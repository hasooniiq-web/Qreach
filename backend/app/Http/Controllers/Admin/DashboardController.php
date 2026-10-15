<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactRequest;
use App\Models\PlanUpgradeRequest;
use App\Models\Scan;
use App\Models\SecurityEvent;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * All figures here are live counts from the database — never mocked
 * (section 17: "Do not generate fake statistics").
 */
class DashboardController extends Controller
{
    /** Counts for the sidebar badges; polled so admins see new work without reloading. */
    public function poll(): JsonResponse
    {
        return response()->json([
            'contact_requests' => ContactRequest::where('status', 'new')->count(),
            'site_messages' => \App\Models\SiteMessage::where('status', 'new')->count(),
            'upgrade_requests' => PlanUpgradeRequest::where('status', 'pending')->count(),
            'security' => SecurityEvent::where('resolved', false)->whereIn('severity', ['warning', 'critical'])->count(),
        ])->header('Cache-Control', 'no-store');
    }

    public function index(): View
    {
        $stats = [
            'users_total' => User::count(),
            'users_active' => User::where('status', 'active')->count(),
            'vehicles_total' => Vehicle::count(),
            'vehicles_active' => Vehicle::where('status', 'active')->count(),
            'tags_total' => Tag::count(),
            'tags_available' => Tag::where('status', 'available')->count(),
            'tags_activated' => Tag::whereIn('status', ['activated', 'assigned'])->count(),
            'tags_assigned' => Tag::where('status', 'assigned')->count(),
            'tags_revoked' => Tag::where('status', 'revoked')->count(),
            'contact_requests_new' => ContactRequest::where('status', 'new')->count(),
            // Only warning/critical events represent something that needs
            // admin attention — routine info-level events (e.g. AUTH_SUCCESS)
            // are never "unresolved" in a meaningful sense.
            'security_events_unresolved' => SecurityEvent::where('resolved', false)
                ->whereIn('severity', ['warning', 'critical'])
                ->count(),
        ];

        return view('admin.dashboard.index', [
            'stats' => $stats,
            'activity' => $this->recentActivity(),
            'kpis' => $this->kpis(),
            'chart' => $this->activityChart(),
            'tagBreakdown' => $this->tagBreakdown(),
            'recentUsers' => User::withCount('vehicles')->latest()->limit(5)->get(),
            'recentVehicles' => Vehicle::with('user')->latest()->limit(5)->get(),
            'insights' => $this->insights($stats),
        ]);
    }

    /**
     * Daily counts for the last $days days (oldest first, zero-filled).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<*>  $query
     * @return list<int>
     */
    private function daily($query, string $column, int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $rows = $query->where($column, '>=', $start)
            ->selectRaw("date({$column}) as d, count(*) as c")
            ->groupBy('d')->pluck('c', 'd');

        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $out[] = (int) ($rows[$start->copy()->addDays($i)->toDateString()] ?? 0);
        }

        return $out;
    }

    /** @return array<string, array{value: int, trend: ?int, spark: list<int>}> */
    private function kpis(): array
    {
        $build = function ($query, string $column, ?int $total = null) {
            $daily = $this->daily($query, $column, 14);
            $prev = array_sum(array_slice($daily, 0, 7));
            $cur = array_sum(array_slice($daily, 7));

            return [
                'value' => $total ?? $cur,
                'cur' => $cur,
                // Only report a change when there is a previous period to compare with.
                'trend' => $prev > 0 ? (int) round(($cur - $prev) / $prev * 100) : null,
                'spark' => $daily,
            ];
        };

        return [
            'users' => $build(User::query(), 'created_at', User::count()),
            'vehicles' => $build(Vehicle::query(), 'created_at', Vehicle::count()),
            'tags' => $build(Tag::query()->where('status', 'assigned'), 'assigned_at', Tag::where('status', 'assigned')->count()),
            'scans' => $build(Scan::query(), 'created_at'),
        ];
    }

    /** Scans and contact requests per day for the last 30 days. */
    private function activityChart(): array
    {
        $days = 30;
        $start = now()->subDays($days - 1)->startOfDay();

        return [
            'type' => 'line',
            'labels' => collect(range(0, $days - 1))->map(fn ($i) => $start->copy()->addDays($i)->format('m/d'))->all(),
            'datasets' => [
                ['label' => 'عمليات المسح', 'data' => $this->daily(Scan::query(), 'created_at', $days)],
                ['label' => 'طلبات التواصل', 'data' => $this->daily(ContactRequest::query(), 'created_at', $days)],
            ],
        ];
    }

    /** @return array{type: string, labels: list<string>, datasets: list<array<string, mixed>>} */
    private function tagBreakdown(): array
    {
        $labels = ['assigned' => 'مربوط', 'available' => 'متاح', 'generated' => 'مُنشأ', 'revoked' => 'ملغى'];
        $counts = Tag::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        return [
            'type' => 'doughnut',
            'labels' => array_values($labels),
            'datasets' => [['label' => 'الرموز', 'data' => collect($labels)->keys()->map(fn ($k) => (int) ($counts[$k] ?? 0))->all()]],
        ];
    }

    /**
     * Plain-language things worth acting on, from real counts only.
     *
     * @param  array<string, int>  $stats
     * @return list<array{tone: string, text: string, href: string, icon: string}>
     */
    private function insights(array $stats): array
    {
        $out = [];

        $unlinked = Vehicle::where('status', 'active')->whereDoesntHave('tags', fn ($q) => $q->where('status', 'assigned'))->count();
        if ($unlinked > 0) {
            $out[] = ['tone' => 'accent', 'icon' => 'car', 'text' => "{$unlinked} مركبة نشطة بلا رمز مربوط بعد.", 'href' => route('admin.vehicles.index')];
        }
        if ($stats['contact_requests_new'] > 0) {
            $out[] = ['tone' => 'accent', 'icon' => 'contact', 'text' => "{$stats['contact_requests_new']} طلب تواصل جديد بانتظار المراجعة.", 'href' => route('admin.contact-requests.index', ['status' => 'new'])];
        }
        $upgrades = PlanUpgradeRequest::where('status', 'pending')->count();
        if ($upgrades > 0) {
            $out[] = ['tone' => 'accent', 'icon' => 'star', 'text' => "{$upgrades} طلب ترقية إلى بلس بانتظار الموافقة.", 'href' => route('admin.subscriptions.requests')];
        }
        if ($stats['security_events_unresolved'] > 0) {
            $out[] = ['tone' => 'danger', 'icon' => 'shield', 'text' => "{$stats['security_events_unresolved']} حدثًا أمنيًا غير محلول.", 'href' => route('admin.security.index')];
        }
        if ($stats['tags_available'] < 20) {
            $out[] = ['tone' => 'muted', 'icon' => 'qrcode', 'text' => "المخزون منخفض: {$stats['tags_available']} رمزًا متاحًا فقط. أنشئ دفعة جديدة.", 'href' => route('admin.tag-batches.index')];
        }

        return $out;
    }
    /**
     * The latest things a person actually did — visitor actions on tags
     * (page views are excluded as noise) and contact requests — newest
     * first. Real rows only; an empty platform shows an empty feed.
     *
     * @return Collection<int, array{at: Carbon, label: string, vehicle: ?string, urgent: bool}>
     */
    private function recentActivity(): Collection
    {
        $labels = [
            'vehicle_block' => 'إعاقة الطريق',
            'lights_on' => 'الأضواء مضاءة',
            'important_message' => 'رسالة مهمة',
            'emergency' => 'حالة طارئة',
            'contact_owner' => 'مراسلة المالك',
        ];

        $scans = Scan::with('vehicle')->where('action_type', '!=', 'view')->latest('id')->limit(8)->get()
            ->map(fn ($s) => [
                'at' => $s->created_at,
                'label' => $labels[$s->action_type] ?? $s->action_type,
                'vehicle' => $s->vehicle?->nickname ?: $s->vehicle?->plate_number,
                'urgent' => $s->action_type === 'emergency',
            ]);

        $requests = ContactRequest::with('vehicle')->latest('id')->limit(8)->get()
            ->map(fn ($r) => [
                'at' => $r->created_at,
                'label' => 'طلب تواصل جديد',
                'vehicle' => $r->vehicle?->nickname ?: $r->vehicle?->plate_number,
                'urgent' => false,
            ]);

        return $scans->concat($requests)->sortByDesc('at')->take(8)->values();
    }
}
