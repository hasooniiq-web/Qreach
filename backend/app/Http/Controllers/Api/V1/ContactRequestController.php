<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContactRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The owner's inbox: messages visitors sent after scanning one of their
 * vehicles' tags. Every query is scoped to vehicles the caller owns, so
 * another user's request uuid is simply a 404. Requests never expose the
 * visitor's IP or user agent — only what the visitor chose to type.
 */
class ContactRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $page = $this->owned($request)->with('vehicle:id,nickname,plate_number')->latest('id')->paginate(20);

        return response()->json([
            'data' => $page->getCollection()->map(fn (ContactRequest $r) => $this->present($r))->values(),
            'unread_count' => $this->owned($request)->where('status', 'new')->count(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function markRead(Request $request, string $uuid): JsonResponse
    {
        $contact = $this->find($request, $uuid);

        if ($contact->status === 'new') {
            $contact->update(['status' => 'read', 'read_at' => now()]);
        }

        return response()->json($this->present($contact->fresh('vehicle')));
    }

    public function markResponded(Request $request, string $uuid): JsonResponse
    {
        $contact = $this->find($request, $uuid);

        if (! in_array($contact->status, ['responded', 'closed'], true)) {
            $contact->update([
                'status' => 'responded',
                'responded_at' => now(),
                'read_at' => $contact->read_at ?? now(),
            ]);
        }

        return response()->json($this->present($contact->fresh('vehicle')));
    }

    /** @return Builder<ContactRequest> */
    private function owned(Request $request): Builder
    {
        return ContactRequest::query()->whereHas('vehicle', fn ($q) => $q->where('user_id', $request->user()->id));
    }

    private function find(Request $request, string $uuid): ContactRequest
    {
        return $this->owned($request)->with('vehicle:id,nickname,plate_number')->where('uuid', $uuid)->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function present(ContactRequest $r): array
    {
        return [
            'id' => $r->uuid,
            'visitor_name' => $r->visitor_name,
            'visitor_phone' => $r->visitor_phone,
            'message' => $r->message,
            'status' => $r->status,
            'vehicle' => $r->vehicle?->nickname ?: $r->vehicle?->plate_number,
            'created_at' => $r->created_at->toIso8601String(),
        ];
    }
}
