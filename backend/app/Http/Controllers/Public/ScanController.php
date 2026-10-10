<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\ContactOwnerRequest;
use App\Http\Requests\Public\EmergencyRequest;
use App\Http\Requests\Public\ImportantMessageRequest;
use App\Models\ContactRequest;
use App\Models\Scan;
use App\Models\Tag;
use App\Notifications\ContactRequestNotification;
use App\Notifications\TagActionNotification;
use App\Rules\PlainTextOnly;
use App\Services\MessageImageProcessor;
use App\Services\PublicContacts;
use App\Services\SecurityEventLogger;
use App\Services\TagTokenGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * The public, unauthenticated QR/NFC scan experience (section 13). No
 * account is required. Every lookup and action here is a potential target
 * for token enumeration/guessing, so failed lookups are indistinguishable
 * from each other (same view, same status code) regardless of the reason,
 * and are logged as QR_GUESSING candidates server-side only.
 */
class ScanController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $tag = $this->resolveActiveTag($token, $request);

        if (! $tag) {
            return view('public.invalid');
        }

        Scan::create([
            'tag_id' => $tag->id,
            'vehicle_id' => $tag->vehicle_id,
            'action_type' => 'view',
            'status' => 'success',
            'request_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $contacts = app(PublicContacts::class);

        return view('public.scan', [
            'tag' => $tag,
            'vehicle' => $tag->vehicle,
            'publicAttributes' => $tag->vehicle->publicAttributes(),
            'ownerContacts' => $contacts->visibleTo($tag->vehicle->user),
            'ownerName' => $contacts->nameFor($tag->vehicle->user),
            'emergency' => $contacts->emergencyFor($tag->vehicle->user),
            'ownerAvatarUrl' => $contacts->avatarUrlFor($tag->vehicle->user, $tag->public_token),
        ]);
    }

    /** The owner's avatar, only served if they switched it on for this scan page. */
    public function ownerAvatar(Request $request, string $token): HttpResponse
    {
        $tag = $this->resolveActiveTagOrFail($token, $request);
        $user = $tag->vehicle->user;

        abort_unless($user->show_avatar_publicly && $user->avatar_path && Storage::disk('local')->exists($user->avatar_path), 404);

        return Storage::disk('local')->response($user->avatar_path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    public function vehicleBlock(Request $request, string $token): RedirectResponse
    {
        return $this->recordSimpleAction($request, $token, 'vehicle_block', 'تم إرسال تنبيه إعاقة المرور لصاحب المركبة.');
    }

    public function lightsOn(Request $request, string $token): RedirectResponse
    {
        return $this->recordSimpleAction($request, $token, 'lights_on', 'تم إرسال تنبيه الأضواء لصاحب المركبة.');
    }

    public function importantMessage(ImportantMessageRequest $request, string $token): RedirectResponse
    {
        $tag = $this->resolveActiveTagOrFail($token, $request);

        $scan = $this->createScan($tag, 'important_message', ['message' => $request->validated('message')], $request);
        $tag->vehicle->user->notify(new TagActionNotification($scan));

        return back()->with('scan_status', 'تم إرسال رسالتك لصاحب المركبة.');
    }

    public function emergency(EmergencyRequest $request, string $token): RedirectResponse
    {
        $tag = $this->resolveActiveTagOrFail($token, $request);

        $scan = $this->createScan($tag, 'emergency', ['message' => $request->validated('message')], $request);
        $tag->vehicle->user->notify(new TagActionNotification($scan));

        SecurityEventLogger::log(
            SecurityEventLogger::EMERGENCY_TRIGGERED, 'warning', 'unknown', null,
            ['tag_id' => $tag->id, 'vehicle_id' => $tag->vehicle_id], $request
        );

        return back()->with('scan_status', 'تم إرسال تنبيه الطوارئ لصاحب المركبة فورًا.');
    }

    public function contactOwner(ContactOwnerRequest $request, string $token): RedirectResponse
    {
        $tag = $this->resolveActiveTagOrFail($token, $request);

        $message = $this->composeContactMessage($request);

        $contactRequest = ContactRequest::create([
            'vehicle_id' => $tag->vehicle_id,
            'tag_id' => $tag->id,
            'visitor_name' => $request->validated('visitor_name'),
            'visitor_phone' => $request->validated('visitor_phone'),
            'reason' => $request->validated('reason'),
            'message' => $message,
            'request_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $this->createScan($tag, 'contact_owner', ['message' => $message], $request);

        $tag->vehicle->user->notify(new ContactRequestNotification($contactRequest));

        // The visitor's private conversation link: the request uuid is unguessable and is the only key.
        return redirect()->route('public.scan.thread', [$tag->public_token, $contactRequest->uuid])
            ->with('scan_status', 'تم إرسال رسالتك.');
    }

    public function thread(Request $request, string $token, string $uuid): View
    {
        $tag = $this->resolveActiveTag($token, $request);
        $contactRequest = $tag ? $this->findThread($tag, $uuid) : null;

        if (! $contactRequest) {
            return view('public.invalid');
        }

        return view('public.thread', ['tag' => $tag, 'thread' => $contactRequest->load('messages')]);
    }

    /** Polled by the thread page so replies appear without a manual refresh. */
    public function threadMessages(Request $request, string $token, string $uuid): JsonResponse
    {
        $tag = $this->resolveActiveTag($token, $request);
        $contactRequest = $tag ? $this->findThread($tag, $uuid) : null;
        abort_if(! $contactRequest, 404);

        $items = collect([['sender' => 'visitor', 'body' => $contactRequest->message, 'id' => null, 'at' => $contactRequest->created_at]])
            ->concat($contactRequest->messages->map(fn ($m) => ['sender' => $m->sender, 'body' => $m->body, 'id' => $m->id, 'image' => $m->image_path, 'at' => $m->created_at]));

        return response()->json([
            'status' => $contactRequest->status,
            'messages' => $items->map(fn ($i) => [
                'sender' => $i['sender'], 'body' => $i['body'],
                'image_url' => ! empty($i['image'] ?? null) ? route('public.scan.thread.image', [$token, $uuid, $i['id']]) : null,
                'at' => $i['at']->format('Y-m-d H:i'),
            ])->values(),
        ])->header('Cache-Control', 'no-store');
    }

    public function threadReply(Request $request, string $token, string $uuid): RedirectResponse
    {
        $tag = $this->resolveActiveTagOrFail($token, $request);
        $contactRequest = $this->findThread($tag, $uuid);
        abort_if(! $contactRequest, 404);

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:500', new PlainTextOnly, 'required_without:image'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], [
            'body.required_without' => 'اكتب رسالتك أو أرفق صورة.',
        ]);

        if ($contactRequest->messages()->count() >= ContactRequest::MAX_MESSAGES) {
            return back()->withErrors(['body' => 'وصلت المحادثة إلى الحد الأقصى من الرسائل.']);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $jpeg = app(MessageImageProcessor::class)->normalize($request->file('image'));
            if ($jpeg === null) {
                return back()->withErrors(['image' => 'تعذّرت معالجة الصورة. جرّب صورة أخرى.']);
            }
            $imagePath = 'contact-message-images/'.Str::uuid().'.jpg';
            Storage::disk('local')->put($imagePath, $jpeg);
        }

        $body = trim((string) ($data['body'] ?? ''));
        $contactRequest->addMessage('visitor', $body, $imagePath);
        $contactRequest->update(['status' => 'new', 'read_at' => null]);
        $tag->vehicle->user->notify(new ContactRequestNotification($contactRequest, $body !== '' ? $body : '📷 صورة'));

        return back()->with('scan_status', 'تم إرسال رسالتك.');
    }

    /** Serves a message's photo — gated the same way the thread page itself
     *  is (the unguessable uuid), never a public storage URL. */
    public function threadImage(Request $request, string $token, string $uuid, int $message): HttpResponse
    {
        $tag = $this->resolveActiveTag($token, $request);
        $contactRequest = $tag ? $this->findThread($tag, $uuid) : null;
        abort_if(! $contactRequest, 404);

        $msg = $contactRequest->messages()->whereKey($message)->firstOrFail();
        abort_unless($msg->image_path && Storage::disk('local')->exists($msg->image_path), 404);

        return Storage::disk('local')->response($msg->image_path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function findThread(Tag $tag, string $uuid): ?ContactRequest
    {
        return ContactRequest::where('uuid', $uuid)->where('tag_id', $tag->id)->first();
    }

    /** The report text is the chosen reason's fixed label, except "other"
     *  which uses whatever the visitor typed; any further talk happens in the thread. */
    private function composeContactMessage(ContactOwnerRequest $request): string
    {
        $reason = $request->validated('reason');

        if ($reason === 'other') {
            return trim((string) $request->validated('other_details'));
        }

        return [
            'blocking' => 'سيارتك تعيق الطريق',
            'damage' => 'لاحظت ضررًا في المركبة',
            'malfunction' => 'يوجد عطل',
            'safety' => 'توجد مشكلة تتعلق بالسلامة',
            'assistance' => 'أحتاج إلى مساعدة',
        ][$reason];
    }

    private function recordSimpleAction(Request $request, string $token, string $actionType, string $successMessage): RedirectResponse
    {
        $tag = $this->resolveActiveTagOrFail($token, $request);

        $scan = $this->createScan($tag, $actionType, [], $request);
        $tag->vehicle->user->notify(new TagActionNotification($scan));

        return back()->with('scan_status', $successMessage);
    }

    private function createScan(Tag $tag, string $actionType, array $payload, Request $request): Scan
    {
        return Scan::create([
            'tag_id' => $tag->id,
            'vehicle_id' => $tag->vehicle_id,
            'action_type' => $actionType,
            'payload' => array_filter($payload),
            'status' => 'success',
            'request_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    private function resolveActiveTag(string $token, Request $request): ?Tag
    {
        $token = TagTokenGenerator::normalize($token);
        $tag = Tag::with('vehicle.user')->where('public_token', $token)->first();

        // An owner can pause a vehicle from the app: its code then behaves like an
        // unlinked one (generic page, no contact, no scan recorded) until reactivated.
        if ($tag && $tag->status === 'assigned' && $tag->vehicle && $tag->vehicle->status !== 'active') {
            return null;
        }

        if (! $tag || $tag->status !== 'assigned' || ! $tag->vehicle) {
            SecurityEventLogger::log(
                SecurityEventLogger::QR_GUESSING, 'info', 'unknown', null,
                ['token_prefix' => substr($token, 0, 6), 'found' => (bool) $tag], $request
            );

            return null;
        }

        return $tag;
    }

    private function resolveActiveTagOrFail(string $token, Request $request): Tag
    {
        $tag = $this->resolveActiveTag($token, $request);

        abort_if(! $tag, 404);

        return $tag;
    }
}
