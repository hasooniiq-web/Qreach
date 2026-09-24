<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactRequest;
use App\Models\SecurityEvent;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
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

        return view('admin.dashboard.index', compact('stats'));
    }
}
