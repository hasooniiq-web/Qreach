<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ContactRequestController extends Controller
{
    use \App\Http\Controllers\Concerns\ExportsTables;

    public function index(Request $request): View|\Illuminate\Http\Response
    {
        $requests = ContactRequest::query()
            // A customer may have deleted the vehicle since; the request must still list.
            ->with(['vehicle' => fn ($q) => $q->withTrashed()->with('user')])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest();

        if ($export = $this->exportRows($request, 'contact-requests', 'طلبات التواصل', ['الزائر', 'المركبة', 'الرسالة', 'الحالة', 'التاريخ'], $requests,
            fn ($r) => [
                (string) ($r->visitor_name ?: 'زائر'),
                ($r->vehicle?->plate_number ?? '—').($r->vehicle?->trashed() ? ' (محذوفة)' : ''),
                (string) $r->message,
                \App\Support\Export\Labels::status($r->status),
                \App\Support\Export\Labels::date($r->created_at, true),
            ],
            filters: $this->filterLines($request, ['status' => 'الحالة'], ['status' => ['new' => 'جديد', 'read' => 'مقروء', 'responded' => 'تم الرد', 'closed' => 'مغلق']]))) {
            return $export;
        }

        $requests = $requests->paginate(20)->withQueryString();

        return view('admin.contact-requests.index', compact('requests'));
    }

    public function show(ContactRequest $contactRequest): View
    {
        if ($contactRequest->status === 'new') {
            $contactRequest->update(['status' => 'read', 'read_at' => now()]);
        }

        $contactRequest->load(['vehicle' => fn ($q) => $q->withTrashed()->with('user'), 'messages']);

        return view('admin.contact-requests.show', ['contactRequest' => $contactRequest]);
    }

    public function close(ContactRequest $contactRequest): RedirectResponse
    {
        $contactRequest->update(['status' => 'closed']);

        return back()->with('status', 'تم إغلاق طلب التواصل.');
    }

    /** Read-only oversight view of a photo a visitor attached in the thread. */
    public function messageImage(ContactRequest $contactRequest, int $message): Response
    {
        $msg = $contactRequest->messages()->whereKey($message)->firstOrFail();
        abort_unless($msg->image_path && Storage::disk('local')->exists($msg->image_path), 404);

        return Storage::disk('local')->response($msg->image_path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
