<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EmergencyContactRequest;
use App\Http\Requests\Api\StoreContactMethodRequest;
use App\Http\Requests\Api\UpdateContactMethodRequest;
use App\Models\ContactMethod;
use App\Rules\PlainTextOnly;
use App\Services\AuditLogger;
use App\Services\AvatarProcessor;
use App\Services\ContactPlatforms;
use App\Services\MessageImageProcessor;
use App\Services\PlanEntitlements;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * The customer's own profile and what visitors may see of it.
 *
 * Default: visitors see nothing (not the name, not the phone, no methods) and
 * can only send a message. The owner switches items on one by one.
 *   - Basic: the name, the phone number and one emergency contact can be shown.
 *   - Plus:  also any number of extra contact platforms (Instagram, WhatsApp...).
 * Every rule is enforced here on write and again on every scan-page render
 * (see PublicContacts) — the app only mirrors it.
 */
class ProfileController extends Controller
{
    private const MAX_METHODS = 15;

    public function __construct(
        private readonly PlanEntitlements $plans,
        private readonly AvatarProcessor $avatars,
        private readonly MessageImageProcessor $images,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $plan = $this->plans->planFor($user);
        $methods = $user->contactMethods()->orderBy('id')->get();
        $phone = $methods->firstWhere('type', 'phone');
        $emergency = $user->emergencyContacts()->where('status', 'active')->orderBy('priority')->first();

        return response()->json([
            'name' => $user->name,
            'phone' => $user->phone,
            'plan' => $plan->slug,
            'plan_name' => $plan->name_ar,
            'has_avatar' => (bool) $user->avatar_path,
            'avatar_version' => $user->updated_at?->timestamp,
            'has_cover' => (bool) $user->cover_path,
            'cover_version' => $user->updated_at?->timestamp,
            'show_name' => (bool) $user->show_name_publicly,
            'show_avatar' => (bool) $user->show_avatar_publicly,
            'show_cover' => (bool) $user->show_cover_publicly,
            'can_add_methods' => $plan->allowsExtraMethods(),
            'contact' => [
                'phone' => [
                    'value' => $phone?->value,
                    'visible' => $phone?->privacy_state === 'public',
                    'can_show' => $plan->allowsPublicContact('phone'),
                ],
            ],
            'methods' => $methods->where('type', '!=', 'phone')->values()->map(fn (ContactMethod $m) => [
                'id' => $m->id,
                'type' => $m->type,
                'label' => ContactPlatforms::label($m->type, $m->label),
                'value' => $m->value,
                'visible' => $m->privacy_state === 'public',
                'can_show' => $plan->allowsPublicContact($m->type),
            ]),
            'platforms' => collect(ContactPlatforms::all())
                ->except('phone')
                ->map(fn ($p, $type) => ['type' => $type, 'label' => $p['label'], 'hint' => $p['hint']])
                ->values(),
            'emergency' => $emergency ? [
                'name' => $emergency->name,
                'relationship' => $emergency->relationship,
                'phone' => $emergency->phone,
                'visible' => (bool) $emergency->is_public,
                'can_show' => $plan->allowsEmergencyContact(),
            ] : null,
        ]);
    }

    public function updateName(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'min:2', 'max:100', new PlainTextOnly]]);

        $request->user()->update(['name' => trim($data['name'])]);
        AuditLogger::log('profile.name_updated', 'customer', $request->user()->id, 'User', $request->user()->id, 'success', [], $request);

        return $this->show($request);
    }

    /** Whether visitors may see the owner's name, avatar and/or cover photo. All off by default. */
    public function updateVisibility(Request $request): JsonResponse
    {
        $data = $request->validate([
            'show_name' => ['sometimes', 'boolean'],
            'show_avatar' => ['sometimes', 'boolean'],
            'show_cover' => ['sometimes', 'boolean'],
        ]);

        if ($data === []) {
            return response()->json(['message' => 'لم يتم إرسال أي تغيير.'], 422);
        }

        $map = ['show_name' => 'show_name_publicly', 'show_avatar' => 'show_avatar_publicly', 'show_cover' => 'show_cover_publicly'];
        $update = [];
        foreach ($map as $input => $column) {
            if (array_key_exists($input, $data)) {
                $update[$column] = (bool) $data[$input];
            }
        }

        $request->user()->update($update);
        AuditLogger::log('profile.visibility_updated', 'customer', $request->user()->id, 'User', $request->user()->id, 'success', $data, $request);

        return $this->show($request);
    }

    /** The phone number shown to visitors (a Basic feature). Empty value removes it. */
    public function updateContact(Request $request): JsonResponse
    {
        $user = $request->user();
        $plan = $this->plans->planFor($user);

        $data = $request->validate([
            'phone' => ['required', 'array'],
            'phone.value' => ['nullable', 'string'],
            'phone.visible' => ['sometimes', 'boolean'],
        ]);

        $value = trim((string) ($data['phone']['value'] ?? ''));
        $visible = (bool) ($data['phone']['visible'] ?? false);
        $existing = ContactMethod::where('user_id', $user->id)->where('type', 'phone')->first();

        if ($value === '') {
            $existing?->delete();

            return $this->show($request);
        }

        $clean = ContactPlatforms::normalize('phone', $value);
        if ($clean === null) {
            return response()->json(['message' => 'رقم الهاتف غير صالح.', 'errors' => ['phone.value' => ['رقم الهاتف غير صالح.']]], 422);
        }
        if ($visible && ! $plan->allowsPublicContact('phone')) {
            return $this->planRequired();
        }

        ContactMethod::updateOrCreate(
            ['user_id' => $user->id, 'type' => 'phone'],
            ['value' => $clean, 'privacy_state' => $visible ? 'public' : 'private']
        );
        AuditLogger::log('profile.contact_updated', 'customer', $user->id, 'User', $user->id, 'success', [], $request);

        return $this->show($request);
    }

    /** Adds a contact platform. Plus only — checked here, not just hidden in the app. */
    public function storeMethod(StoreContactMethodRequest $request): JsonResponse
    {
        $user = $request->user();
        $plan = $this->plans->planFor($user);

        if (! $plan->allowsExtraMethods()) {
            return $this->planRequired();
        }
        if ($user->contactMethods()->count() >= self::MAX_METHODS) {
            return response()->json(['error' => ['code' => 'LIMIT_REACHED', 'message' => 'وصلت إلى الحد الأقصى من طرق التواصل.']], 422);
        }

        $type = $request->validated('type');
        $visible = (bool) ($request->validated('visible') ?? false);

        // One entry per platform, except "other" which may repeat with its own label.
        if ($type !== 'other' && $user->contactMethods()->where('type', $type)->exists()) {
            return response()->json(['error' => ['code' => 'ALREADY_EXISTS', 'message' => 'أضفت هذه المنصة مسبقًا. عدّلها بدل إضافتها من جديد.']], 422);
        }
        if ($visible && ! $plan->allowsPublicContact($type)) {
            return $this->planRequired();
        }

        $user->contactMethods()->create([
            'type' => $type,
            'value' => $request->cleanValue(),
            'label' => $type === 'other' ? $request->validated('label') : null,
            'privacy_state' => $visible ? 'public' : 'private',
        ]);
        AuditLogger::log('profile.method_added', 'customer', $user->id, 'User', $user->id, 'success', ['type' => $type], $request);

        return $this->show($request);
    }

    public function updateMethod(UpdateContactMethodRequest $request, int $method): JsonResponse
    {
        $contact = $this->ownedMethod($request, $method);
        $this->authorize('update', $contact);
        $plan = $this->plans->planFor($request->user());

        $visible = $request->validated('visible');
        if ($visible === true && ! $plan->allowsPublicContact($contact->type)) {
            return $this->planRequired();
        }
        // Editing a value counts as changing extra methods, which needs Plus.
        if ($request->filled('value') && $contact->type !== 'phone' && ! $plan->allowsExtraMethods()) {
            return $this->planRequired();
        }

        $update = [];
        if ($request->filled('value')) {
            $update['value'] = $request->cleanValue($contact->type);
        }
        if ($visible !== null) {
            $update['privacy_state'] = $visible ? 'public' : 'private';
        }
        $contact->update($update);

        return $this->show($request);
    }

    /** Removing is always allowed, even after a downgrade. */
    public function destroyMethod(Request $request, int $method): JsonResponse
    {
        $contact = $this->ownedMethod($request, $method);
        $this->authorize('delete', $contact);
        $contact->delete();

        return $this->show($request);
    }

    public function updateEmergency(EmergencyContactRequest $request): JsonResponse
    {
        $user = $request->user();
        $plan = $this->plans->planFor($user);
        $visible = (bool) ($request->validated('visible') ?? false);

        if ($visible && ! $plan->allowsEmergencyContact()) {
            return $this->planRequired();
        }

        $user->emergencyContacts()->updateOrCreate(['priority' => 1], [
            'name' => trim($request->validated('name')),
            'relationship' => $request->validated('relationship'),
            'phone' => $request->validated('phone'),
            'status' => 'active',
            'is_public' => $visible,
        ]);
        AuditLogger::log('profile.emergency_updated', 'customer', $user->id, 'User', $user->id, 'success', [], $request);

        return $this->show($request);
    }

    public function destroyEmergency(Request $request): JsonResponse
    {
        $request->user()->emergencyContacts()->delete();

        return $this->show($request);
    }

    public function avatar(Request $request): Response
    {
        $path = $request->user()->avatar_path;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function storeAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=64,min_height=64'],
        ]);

        $user = $request->user();
        $jpeg = $this->avatars->normalize($request->file('avatar'));

        if ($jpeg === null) {
            return response()->json(['error' => ['code' => 'INVALID_IMAGE', 'message' => 'تعذّرت معالجة الصورة. جرّب صورة أخرى.']], 422);
        }

        $path = "user-avatars/{$user->uuid}.jpg";
        Storage::disk('local')->put($path, $jpeg);
        $user->update(['avatar_path' => $path]);

        AuditLogger::log('profile.avatar_updated', 'customer', $user->id, 'User', $user->id, 'success', [], $request);

        return $this->show($request);
    }

    public function destroyAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('local')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        return $this->show($request);
    }

    public function cover(Request $request): Response
    {
        $path = $request->user()->cover_path;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    /** Unlike the avatar (cropped to a square), the cover keeps its own
     *  aspect ratio — MessageImageProcessor just downscales and re-encodes. */
    public function storeCover(Request $request): JsonResponse
    {
        $request->validate([
            'cover' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144', 'dimensions:min_width=200,min_height=80'],
        ]);

        $user = $request->user();
        $jpeg = $this->images->normalize($request->file('cover'));

        if ($jpeg === null) {
            return response()->json(['error' => ['code' => 'INVALID_IMAGE', 'message' => 'تعذّرت معالجة الصورة. جرّب صورة أخرى.']], 422);
        }

        $path = "user-covers/{$user->uuid}.jpg";
        Storage::disk('local')->put($path, $jpeg);
        $user->update(['cover_path' => $path]);

        AuditLogger::log('profile.cover_updated', 'customer', $user->id, 'User', $user->id, 'success', [], $request);

        return $this->show($request);
    }

    public function destroyCover(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->cover_path) {
            Storage::disk('local')->delete($user->cover_path);
            $user->update(['cover_path' => null]);
        }

        return $this->show($request);
    }

    private function ownedMethod(Request $request, int $id): ContactMethod
    {
        return $request->user()->contactMethods()->findOrFail($id);
    }

    private function planRequired(): JsonResponse
    {
        return response()->json(['error' => [
            'code' => 'PLAN_REQUIRED',
            'message' => 'هذه الميزة متاحة في باقة Plus. رقّ اشتراكك لتفعيلها.',
        ]], 403);
    }
}
