<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactRequest;
use App\Models\Scan;
use App\Models\SecurityEvent;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * All figures here are live counts from the database — never mocked
 * (section 17: "Do not generate fake statistics").
 */
class DashboardController extends Controller
{
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

        return view('admin.dashboard.index', ['stats' => $stats, 'activity' => $this->recentActivity()]);
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
