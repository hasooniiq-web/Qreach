<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContactMethod;
use App\Rules\PlainTextOnly;
use App\Services\AuditLogger;
use App\Services\PlanEntitlements;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The customer's own profile: display name and the contact details they can
 * store. Storing a detail is open to everyone; *showing* it to people who
 * scan their tag is what needs the Pro plan, and nothing is shown unless the
 * owner switches it on — the default is hidden, so a visitor only ever gets
 * to send a message. Visibility is enforced here on write and again on every
 * scan-page render (see PublicContacts).
 */
class ProfileController extends Controller
{
    /** request key => contact_methods.type */
    private const FIELDS = ['phone' => 'phone', 'instagram' => 'instagram', 'extra' => 'other'];

    public function __construct(private readonly PlanEntitlements $plans) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $plan = $this->plans->planFor($user);
        $stored = $user->contactMethods()->whereIn('type', array_values(self::FIELDS))->get()->keyBy('type');

        $contact = [];
        foreach (self::FIELDS as $key => $type) {
            $method = $stored->get($type);
            $contact[$key] = [
                'value' => $method?->value,
                'visible' => $method?->privacy_state === 'public',
                // false on Basic: the owner may store the detail but cannot show it.
                'can_show' => $plan->allowsPublicContact($type),
            ];
        }

        return response()->json([
            'name' => $user->name,
            'phone' => $user->phone,
            'plan' => $plan->slug,
            'contact' => $contact,
        ]);
    }

    public function updateName(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'min:2', 'max:100', new PlainTextOnly]]);

        $request->user()->update(['name' => trim($data['name'])]);
        AuditLogger::log('profile.name_updated', 'customer', $request->user()->id, 'User', $request->user()->id, 'success', [], $request);

        return $this->show($request);
    }

    public function updateContact(Request $request): JsonResponse
    {
        $user = $request->user();
        $plan = $this->plans->planFor($user);

        $data = $request->validate([
            'phone' => ['sometimes', 'array'],
            'phone.value' => ['nullable', 'string', 'regex:/^\+?[1-9]\d{7,14}$/'],
            'phone.visible' => ['sometimes', 'boolean'],
            'instagram' => ['sometimes', 'array'],
            'instagram.value' => ['nullable', 'string', 'regex:/^@?[A-Za-z0-9._]{1,30}$/'],
            'instagram.visible' => ['sometimes', 'boolean'],
            'extra' => ['sometimes', 'array'],
            'extra.value' => ['nullable', 'string', 'max:200', new PlainTextOnly],
            'extra.visible' => ['sometimes', 'boolean'],
        ]);

        // Check every requested change before saving any, so a refusal never leaves a half-applied update.
        foreach (self::FIELDS as $key => $type) {
            if (($data[$key]['visible'] ?? false) && ! $plan->allowsPublicContact($type)) {
                return response()->json(['error' => [
                    'code' => 'PLAN_REQUIRED',
                    'message' => 'إظهار بيانات التواصل للزوار متاح في الباقة الاحترافية فقط.',
                ]], 403);
            }
        }

        foreach (self::FIELDS as $key => $type) {
            if (! isset($data[$key])) {
                continue;
            }

            $existing = ContactMethod::where('user_id', $user->id)->where('type', $type)->first();
            $value = array_key_exists('value', $data[$key]) ? trim((string) $data[$key]['value']) : ($existing?->value ?? '');
            $value = $key === 'instagram' ? ltrim($value, '@') : $value;

            if ($value === '') {
                $existing?->delete();

                continue;
            }

            $visible = array_key_exists('visible', $data[$key]) ? (bool) $data[$key]['visible'] : ($existing?->privacy_state === 'public');

            ContactMethod::updateOrCreate(
                ['user_id' => $user->id, 'type' => $type],
                ['value' => $value, 'label' => $type === 'other' ? 'معلومات إضافية' : null, 'privacy_state' => $visible ? 'public' : 'private']
            );
        }

        AuditLogger::log('profile.contact_updated', 'customer', $user->id, 'User', $user->id, 'success', [], $request);

        return $this->show($request);
    }
}
