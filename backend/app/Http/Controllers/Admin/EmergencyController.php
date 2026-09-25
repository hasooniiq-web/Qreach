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
    public function index(Request $request): View
    {
        $base = Scan::where('action_type', 'emergency');

        $alerts = (clone $base)->with(['vehicle.user', 'tag'])
            ->when($request->filled('from') && strtotime($request->string('from')->toString()), fn ($q) => $q->where('created_at', '>=', Carbon::parse($request->string('from')->toString())->startOfDay()))
            ->when($request->filled('to') && strtotime($request->string('to')->toString()), fn ($q) => $q->where('created_at', '<=', Carbon::parse($request->string('to')->toString())->endOfDay()))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

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
