<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityEventController extends Controller
{
    use \App\Http\Controllers\Concerns\ExportsTables;

    public function index(Request $request): View|\Illuminate\Http\Response
    {
        $events = SecurityEvent::query()
            ->when($request->filled('severity'), fn ($q) => $q->where('severity', $request->string('severity')))
            ->when($request->filled('event_type'), fn ($q) => $q->where('event_type', $request->string('event_type')))
            ->when($request->boolean('unresolved_only'), fn ($q) => $q->where('resolved', false))
            ->latest();

        if ($export = $this->exportRows($request, 'security-events', 'الأحداث الأمنية', ['الحدث', 'المستوى', 'الجهة الفاعلة', 'عنوان IP', 'التاريخ', 'الحالة'], $events,
            fn ($e) => [
                (string) $e->event_type,
                \App\Support\Export\Labels::status($e->severity),
                (string) $e->actor_type,
                (string) $e->request_ip,
                \App\Support\Export\Labels::date($e->created_at, true),
                $e->resolved ? 'تمت المراجعة' : 'قيد المراجعة',
            ],
            filters: $this->filterLines($request, ['severity' => 'المستوى', 'event_type' => 'نوع الحدث', 'unresolved_only' => 'غير المراجَع فقط']))) {
            return $export;
        }

        $events = $events->paginate(30)->withQueryString();

        $eventTypes = SecurityEvent::select('event_type')->distinct()->pluck('event_type');

        // Real counts only — an at-a-glance strip so an admin sees whether anything
        // needs attention before reading the table (redesign brief §29).
        $summary = [
            'critical_open' => SecurityEvent::where('severity', 'critical')->where('resolved', false)->count(),
            'warning_open' => SecurityEvent::where('severity', 'warning')->where('resolved', false)->count(),
            'last_24h' => SecurityEvent::where('created_at', '>=', now()->subDay())->count(),
        ];

        return view('admin.security.index', compact('events', 'eventTypes', 'summary'));
    }

    public function resolve(Request $request, SecurityEvent $securityEvent): RedirectResponse
    {
        $securityEvent->update([
            'resolved' => true,
            'resolved_by' => $request->user('admin')->id,
            'resolved_at' => now(),
        ]);

        AuditLogger::log(
            'admin.security_event_resolved', 'admin', $request->user('admin')->id,
            'SecurityEvent', $securityEvent->id, 'success', [], $request
        );

        return back()->with('status', 'تم تحديد الحدث كمحلول.');
    }
}
