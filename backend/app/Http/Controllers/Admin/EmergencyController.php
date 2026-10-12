<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Scan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Emergency alerts raised from the public scan page (a visitor confirmed a
 * real emergency). Operations staff need these on their own screen rather
 * than buried among ordinary scans. Shows the vehicle, time and the
 * visitor's short description — no visitor IP or user agent.
 */
class EmergencyController extends Controller
{
    use \App\Http\Controllers\Concerns\ExportsTables;

    public function index(Request $request): View|\Illuminate\Http\Response
    {
        $base = Scan::where('action_type', 'emergency');

        $alerts = (clone $base)->with(['vehicle.user', 'tag'])
            ->when($request->filled('from') && strtotime($request->string('from')->toString()), fn ($q) => $q->where('created_at', '>=', Carbon::parse($request->string('from')->toString())->startOfDay()))
            ->when($request->filled('to') && strtotime($request->string('to')->toString()), fn ($q) => $q->where('created_at', '<=', Carbon::parse($request->string('to')->toString())->endOfDay()))
            ->latest('id');

        if ($export = $this->exportRows($request, 'emergencies', 'التنبيهات الطارئة', ['الوقت', 'المركبة', 'هاتف المالك', 'وصف الزائر', 'الرمز'], $alerts,
            fn ($a) => [
                \App\Support\Export\Labels::date($a->created_at, true),
                (string) ($a->vehicle ? ($a->vehicle->nickname ?: $a->vehicle->plate_number) : '—'),
                (string) ($a->vehicle?->user?->phone ?? '—'),
                (string) ($a->payload['message'] ?? 'بدون وصف'),
                (string) ($a->tag?->serial_number ?? '—'),
            ],
            filters: $this->filterLines($request, ['from' => 'من', 'to' => 'إلى']))) {
            return $export;
        }

        $alerts = $alerts->paginate(20)->withQueryString();

        return view('admin.emergencies.index', [
            'alerts' => $alerts,
            'stats' => [
                'last_24h' => (clone $base)->where('created_at', '>=', now()->subDay())->count(),
                'last_7d' => (clone $base)->where('created_at', '>=', now()->subDays(7))->count(),
                'total' => (clone $base)->count(),
            ],
        ]);
    }
}
