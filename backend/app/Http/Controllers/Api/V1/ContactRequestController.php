<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContactRequest;
use App\Rules\PlainTextOnly;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

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

    public function show(Request $request, string $uuid): JsonResponse
    {
        $contact = $this->find($request, $uuid);

        if ($contact->status === 'new') {
            $contact->update(['status' => 'read', 'read_at' => now()]);
        }

        return response()->json($this->present($contact->fresh(['vehicle', 'messages']), withMessages: true));
    }

    public function reply(Request $request, string $uuid): JsonResponse
    {
        $contact = $this->find($request, $uuid);
        $data = $request->validate(['body' => ['required', 'string', 'max:500', new PlainTextOnly]]);

        if ($contact->messages()->count() >= ContactRequest::MAX_MESSAGES) {
            return response()->json(['error' => ['code' => 'THREAD_FULL', 'message' => 'وصلت المحادثة إلى الحد الأقصى من الرسائل.']], 422);
        }

        $contact->addMessage('owner', trim($data['body']));
        $contact->update([
            'status' => 'responded',
            'responded_at' => now(),
            'read_at' => $contact->read_at ?? now(),
        ]);

        return response()->json($this->present($contact->fresh(['vehicle', 'messages']), withMessages: true), 201);
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
    private function present(ContactRequest $r, bool $withMessages = false): array
    {
        $out = [
            'id' => $r->uuid,
            'visitor_name' => $r->visitor_name,
            'visitor_phone' => $r->visitor_phone,
            'message' => $r->message,
            'status' => $r->status,
            'vehicle' => $r->vehicle?->nickname ?: $r->vehicle?->plate_number,
            'created_at' => $r->created_at->toIso8601String(),
            'expires_at' => $r->expiresAt()->toIso8601String(),
        ];

        if ($withMessages) {
            $out['messages'] = $r->messages->map(fn ($m) => [
                'sender' => $m->sender,
                'body' => $m->body,
                // A relative path, not route()'s absolute form: the app's
                // own configured API host (which can differ from APP_URL in
                // local dev) is what it should be fetched through.
                'image_url' => $m->image_path ? "/contact-requests/{$r->uuid}/messages/{$m->id}/image" : null,
                'created_at' => $m->created_at->toIso8601String(),
            ])->values();
        }

        return $out;
    }

    /** A photo a visitor attached in the thread. Same ownership scoping as every other route here. */
    public function messageImage(Request $request, string $uuid, int $message): Response
    {
        $contact = $this->find($request, $uuid);
        $msg = $contact->messages()->whereKey($message)->firstOrFail();

        abort_unless($msg->image_path && Storage::disk('local')->exists($msg->image_path), 404);

        return Storage::disk('local')->response($msg->image_path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
