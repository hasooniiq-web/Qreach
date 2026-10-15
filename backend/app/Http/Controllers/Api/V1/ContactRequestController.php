<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContactRequest;
use App\Rules\PlainTextOnly;
use App\Services\MessageImageProcessor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $page = $this->involved($request)->with('vehicle:id,user_id,nickname,make,model,plate_number')
            ->orderByDesc('last_activity_at')->orderByDesc('id')->paginate(20);

        return response()->json([
            'data' => $page->getCollection()->map(fn (ContactRequest $r) => $this->present($r, $request))->values(),
            'unread_count' => $this->owned($request)->where('status', 'new')->count(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $contact = $this->find($request, $uuid);

        // Only the owner's opening of the conversation counts as "read".
        if ($contact->status === 'new' && $this->isOwner($request, $contact)) {
            $contact->update(['status' => 'read', 'read_at' => now()]);
        }

        return response()->json($this->present($contact->fresh(['vehicle', 'messages']), $request, withMessages: true));
    }

    public function reply(Request $request, string $uuid): JsonResponse
    {
        $contact = $this->find($request, $uuid);
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:500', new PlainTextOnly, 'required_without:image'],
            // Same limits as the visitor side: real image, 8 MB, re-encoded server-side.
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], [
            'body.required_without' => 'اكتب رسالتك أو أرفق صورة.',
        ]);

        $owner = $this->isOwner($request, $contact);

        // A conversation a customer started from the app has no message limit; the web one does.
        if ($contact->reporter_user_id === null && $contact->messages()->count() >= ContactRequest::MAX_MESSAGES) {
            return response()->json(['error' => ['code' => 'THREAD_FULL', 'message' => 'وصلت المحادثة إلى الحد الأقصى من الرسائل.']], 422);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $jpeg = app(MessageImageProcessor::class)->normalize($request->file('image'));
            if ($jpeg === null) {
                return response()->json(['error' => ['code' => 'INVALID_IMAGE', 'message' => 'تعذّرت معالجة الصورة. جرّب صورة أخرى.']], 422);
            }
            $imagePath = 'contact-message-images/'.Str::uuid().'.jpg';
            Storage::disk('local')->put($imagePath, $jpeg);
        }

        $body = trim((string) ($data['body'] ?? ''));
        $contact->addMessage($owner ? 'owner' : 'visitor', $body, $imagePath);

        if ($owner) {
            $contact->update([
                'status' => 'responded',
                'responded_at' => now(),
                'read_at' => $contact->read_at ?? now(),
            ]);

            // The customer who scanned from the app hears back as a notification.
            if ($contact->reporter_user_id !== null && ($reporter = $contact->reporter)) {
                $reporter->notify(new \App\Notifications\ContactReplyNotification($contact, $body !== '' ? $body : '📷 صورة'));
            }
        } else {
            $contact->update(['status' => 'new', 'read_at' => null]);
            $contact->vehicle->user->notify(new \App\Notifications\ContactRequestNotification($contact, $body !== '' ? $body : '📷 صورة'));
        }

        return response()->json($this->present($contact->fresh(['vehicle', 'messages']), $request, withMessages: true), 201);
    }

    public function markRead(Request $request, string $uuid): JsonResponse
    {
        $contact = $this->findOwned($request, $uuid);

        if ($contact->status === 'new') {
            $contact->update(['status' => 'read', 'read_at' => now()]);
        }

        return response()->json($this->present($contact->fresh('vehicle'), $request));
    }

    public function markResponded(Request $request, string $uuid): JsonResponse
    {
        $contact = $this->findOwned($request, $uuid);

        if (! in_array($contact->status, ['responded', 'closed'], true)) {
            $contact->update([
                'status' => 'responded',
                'responded_at' => now(),
                'read_at' => $contact->read_at ?? now(),
            ]);
        }

        return response()->json($this->present($contact->fresh('vehicle'), $request));
    }

    /** Conversations about the caller's own vehicles. @return Builder<ContactRequest> */
    private function owned(Request $request): Builder
    {
        return ContactRequest::query()->whereHas('vehicle', fn ($q) => $q->where('user_id', $request->user()->id));
    }

    /** Both sides: the owner's inbox plus conversations the caller started by scanning another code. @return Builder<ContactRequest> */
    private function involved(Request $request): Builder
    {
        $id = $request->user()->id;

        return ContactRequest::query()->where(function (Builder $q) use ($id) {
            $q->whereHas('vehicle', fn ($v) => $v->where('user_id', $id))->orWhere('reporter_user_id', $id);
        });
    }

    private function find(Request $request, string $uuid): ContactRequest
    {
        return $this->involved($request)->with('vehicle:id,user_id,nickname,make,model,plate_number')->where('uuid', $uuid)->firstOrFail();
    }

    private function findOwned(Request $request, string $uuid): ContactRequest
    {
        return $this->owned($request)->with('vehicle:id,user_id,nickname,make,model,plate_number')->where('uuid', $uuid)->firstOrFail();
    }

    private function isOwner(Request $request, ContactRequest $r): bool
    {
        return (int) $r->vehicle?->user_id === (int) $request->user()->id;
    }

    /** @return array<string, mixed> */
    private function present(ContactRequest $r, Request $request, bool $withMessages = false): array
    {
        $owner = $this->isOwner($request, $r);

        $out = [
            'id' => $r->uuid,
            // "owner": about my vehicle · "reporter": I started it by scanning someone else's code.
            'role' => $owner ? 'owner' : 'reporter',
            'reason' => $r->reason,
            'visitor_name' => $owner ? $r->visitor_name : null,
            'visitor_phone' => $owner ? $r->visitor_phone : null,
            'message' => $r->message,
            'status' => $r->status,
            // The owner sees their own label; the person who scanned sees what the page showed.
            'vehicle' => $owner
                ? ($r->vehicle?->nickname ?: $r->vehicle?->plate_number)
                : ($r->vehicle?->nickname ?: trim(($r->vehicle?->make ?? '').' '.($r->vehicle?->model ?? '')) ?: null),
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
