<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContactRequest;
use App\Models\Scan;
use App\Models\Tag;
use App\Notifications\ContactRequestNotification;
use App\Services\PlanEntitlements;
use App\Services\PublicContacts;
use App\Services\SecurityEventLogger;
use App\Services\TagTokenGenerator;
use App\Support\ContactReasons;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A customer scans another vehicle's QR code with the app's own scanner. They see what the
 * owner chose to show on the public page, pick one of the six reasons and carry on the
 * conversation from the app — with the same rules as the web page: nothing is typed to open
 * it, the owner never learns who the sender is, and the conversation is deleted after 7 days.
 */
class ScanContactController extends Controller
{
    /** What the scanned code is, and what may be done with it. */
    public function lookup(Request $request): JsonResponse
    {
        $tag = $this->resolve($request);
        $vehicle = $tag->vehicle;
        $contacts = app(PublicContacts::class);

        $existing = ContactRequest::where('vehicle_id', $vehicle->id)
            ->where('reporter_user_id', $request->user()->id)
            ->where('created_at', '>', now()->subDays(ContactRequest::RETENTION_DAYS))
            ->latest('id')->first();

        Scan::create([
            'tag_id' => $tag->id, 'vehicle_id' => $vehicle->id, 'action_type' => 'view', 'status' => 'success',
            'request_ip' => $request->ip(), 'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'token' => $tag->public_token,
            'vehicle' => $vehicle->publicAttributes(),
            'owner_name' => $contacts->nameFor($vehicle->user),
            'emergency' => $contacts->emergencyFor($vehicle->user),
            'contacts' => collect($contacts->visibleTo($vehicle->user))->map(fn ($c) => [
                'type' => $c['type'], 'label' => $c['label'], 'value' => $c['value'], 'href' => $c['href'] ?? null,
            ])->values(),
            'reasons' => collect(ContactReasons::keys())->map(fn ($k) => ['key' => $k])->values(),
            'conversation_id' => $existing?->uuid,
        ]);
    }

    /** One of the six reasons: opens the conversation (or adds to the one already open). */
    public function contact(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:300'],
            'reason' => ['required', 'in:'.implode(',', ContactReasons::keys())],
        ], [
            'reason.required' => 'اختر سبب البلاغ.',
            'reason.in' => 'سبب البلاغ غير صالح.',
        ]);

        $tag = $this->resolve($request);
        $user = $request->user();
        $message = ContactReasons::message($data['reason']);

        $thread = ContactRequest::where('vehicle_id', $tag->vehicle_id)
            ->where('reporter_user_id', $user->id)
            ->where('created_at', '>', now()->subDays(ContactRequest::RETENTION_DAYS))
            ->latest('id')->first();

        if ($thread) {
            // Same person, same vehicle: it stays one conversation, not a new alert thread each time.
            $thread->addMessage('visitor', $message);
            $thread->update(['status' => 'new', 'read_at' => null, 'reason' => $data['reason']]);
            $tag->vehicle->user->notify(new ContactRequestNotification($thread, $message));
        } else {
            $thread = ContactRequest::create([
                'vehicle_id' => $tag->vehicle_id,
                'tag_id' => $tag->id,
                'reporter_user_id' => $user->id,
                'reason' => $data['reason'],
                'message' => $message,
                'request_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            $tag->vehicle->user->notify(new ContactRequestNotification($thread));
        }

        Scan::create([
            'tag_id' => $tag->id, 'vehicle_id' => $tag->vehicle_id, 'action_type' => 'contact_owner',
            'payload' => ['message' => $message, 'source' => 'app'], 'status' => 'success',
            'request_ip' => $request->ip(), 'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['id' => $thread->uuid], 201);
    }

    /**
     * The tag behind a scanned address, only when the public page would answer for it (the same
     * rules: linked, vehicle active and covered by its plan, QR side switched on). Every refusal
     * is the same answer, so this cannot be used to probe which codes exist.
     */
    private function resolve(Request $request): Tag
    {
        $request->validate(['token' => ['required', 'string', 'max:300']]);

        $raw = trim((string) $request->input('token'));
        if (preg_match('~/t/([^/?#\s]+)~', $raw, $m)) {
            $raw = $m[1];
        }
        $token = TagTokenGenerator::normalize($raw);

        $tag = Tag::with('vehicle.user')->where('public_token', $token)->first();

        $usable = $tag
            && $tag->status === 'assigned'
            && $tag->vehicle
            && $tag->vehicle->status === 'active'
            && ! app(PlanEntitlements::class)->isVehicleLocked($tag->vehicle)
            && $tag->channelEnabled(Tag::CHANNEL_QR);

        if (! $usable) {
            SecurityEventLogger::log(
                SecurityEventLogger::QR_GUESSING, 'info', 'customer', $request->user()->id,
                ['token_prefix' => substr($token, 0, 6), 'found' => (bool) $tag, 'source' => 'app'], $request
            );

            abort(response()->json(['error' => [
                'code' => 'TAG_NOT_FOUND',
                'message' => 'هذا الرمز غير صالح أو غير فعّال حاليًا.',
            ]], 404));
        }

        if ($tag->vehicle->user_id === $request->user()->id) {
            abort(response()->json(['error' => [
                'code' => 'OWN_VEHICLE',
                'message' => 'هذه مركبتك. لا حاجة لمراسلة نفسك.',
            ]], 422));
        }

        return $tag;
    }
}
