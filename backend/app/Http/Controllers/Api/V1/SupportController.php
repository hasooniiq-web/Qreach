<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Rules\PlainTextOnly;
use App\Services\MessageImageProcessor;
use App\Services\SupportContacts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Technical support from the app: the support WhatsApp numbers, and tickets with text
 * and photos. Every query is scoped to the caller's own tickets, so someone else's
 * ticket id is simply a 404.
 */
class SupportController extends Controller
{
    public function info(SupportContacts $contacts): JsonResponse
    {
        return response()->json(['whatsapp' => $contacts->all()]);
    }

    public function index(Request $request): JsonResponse
    {
        $tickets = SupportTicket::where('user_id', $request->user()->id)
            ->with('messages')
            ->orderByDesc('last_activity_at')->limit(50)->get();

        return response()->json([
            'data' => $tickets->map(fn (SupportTicket $t) => $this->present($t))->values(),
            'answered_count' => $tickets->where('status', 'answered')->count(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => ['required', 'in:'.implode(',', SupportTicket::CATEGORIES)],
            'subject' => ['required', 'string', 'max:120', new PlainTextOnly],
            'body' => ['required', 'string', 'max:2000', new PlainTextOnly],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], [
            'subject.required' => 'اكتب عنوان المشكلة.',
            'body.required' => 'اكتب تفاصيل المشكلة.',
        ]);

        $open = SupportTicket::where('user_id', $request->user()->id)->whereIn('status', ['open', 'answered'])->count();
        if ($open >= SupportTicket::MAX_OPEN) {
            return response()->json(['error' => [
                'code' => 'TOO_MANY_TICKETS',
                'message' => 'لديك '.SupportTicket::MAX_OPEN.' تذاكر مفتوحة. أغلق إحداها أو انتظر الرد قبل فتح تذكرة جديدة.',
            ]], 422);
        }

        $imagePath = $this->storeImage($request);
        if ($imagePath === false) {
            return $this->invalidImage();
        }

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'category' => $data['category'],
            'subject' => trim($data['subject']),
        ]);
        $ticket->addMessage('user', trim($data['body']), $imagePath);

        return response()->json($this->present($ticket->fresh('messages'), true), 201);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        return response()->json($this->present($this->find($request, $uuid)->load('messages'), true));
    }

    public function reply(Request $request, string $uuid): JsonResponse
    {
        $ticket = $this->find($request, $uuid);

        if ($ticket->status === 'closed') {
            return response()->json(['error' => ['code' => 'TICKET_CLOSED', 'message' => 'أُغلقت هذه التذكرة. افتح تذكرة جديدة إن احتجت مساعدة.']], 422);
        }

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:2000', new PlainTextOnly, 'required_without:image'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], [
            'body.required_without' => 'اكتب رسالتك أو أرفق صورة.',
        ]);

        $imagePath = $this->storeImage($request);
        if ($imagePath === false) {
            return $this->invalidImage();
        }

        $ticket->addMessage('user', trim((string) ($data['body'] ?? '')), $imagePath);
        $ticket->update(['status' => 'open']);

        return response()->json($this->present($ticket->fresh('messages'), true), 201);
    }

    public function image(Request $request, string $uuid, int $message): Response
    {
        $ticket = $this->find($request, $uuid);
        $msg = $ticket->messages()->whereKey($message)->firstOrFail();

        abort_unless($msg->image_path && Storage::disk('local')->exists($msg->image_path), 404);

        return Storage::disk('local')->response($msg->image_path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function find(Request $request, string $uuid): SupportTicket
    {
        return SupportTicket::where('user_id', $request->user()->id)->where('uuid', $uuid)->firstOrFail();
    }

    /** @return string|false|null the stored path, null when no image was sent, false when it could not be processed */
    private function storeImage(Request $request): string|false|null
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $jpeg = app(MessageImageProcessor::class)->normalize($request->file('image'));
        if ($jpeg === null) {
            return false;
        }

        $path = 'support-images/'.Str::uuid().'.jpg';
        Storage::disk('local')->put($path, $jpeg);

        return $path;
    }

    private function invalidImage(): JsonResponse
    {
        return response()->json(['error' => ['code' => 'INVALID_IMAGE', 'message' => 'تعذّرت معالجة الصورة. جرّب صورة أخرى.']], 422);
    }

    /** @return array<string, mixed> */
    private function present(SupportTicket $t, bool $withMessages = false): array
    {
        $out = [
            'id' => $t->uuid,
            'category' => $t->category,
            'subject' => $t->subject,
            'status' => $t->status,
            'created_at' => $t->created_at->toIso8601String(),
            'last_activity_at' => $t->last_activity_at?->toIso8601String(),
        ];

        if ($withMessages) {
            $out['messages'] = $t->messages->map(fn (SupportMessage $m) => [
                'sender' => $m->sender,
                'body' => $m->body,
                'image_url' => $m->image_path ? "/support/tickets/{$t->uuid}/messages/{$m->id}/image" : null,
                'created_at' => $m->created_at->toIso8601String(),
            ])->values();
        } else {
            $last = $t->relationLoaded('messages') ? $t->messages->last() : null;
            $out['last_message'] = $last ? ($last->body !== '' ? $last->body : '📷') : null;
        }

        return $out;
    }
}
