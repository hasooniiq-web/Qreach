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
 * The contact details an owner chooses to show visitors who scan their tag.
 * Editing is a Pro entitlement, enforced here on the server — the app only
 * reflects `editable`. Values are kept if a plan lapses (they just stop
 * being shown; see PublicContacts).
 */
class PublicContactController extends Controller
{
    /** request field => contact_methods.type */
    private const FIELDS = ['phone' => 'phone', 'instagram' => 'instagram', 'extra' => 'other'];

    public function __construct(private readonly PlanEntitlements $plans) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $plan = $this->plans->planFor($user);
        $stored = $user->contactMethods()->where('privacy_state', 'public')->pluck('value', 'type');

        return response()->json([
            'editable' => $plan->publicContactTypes() !== [],
            'phone' => $stored['phone'] ?? null,
            'instagram' => $stored['instagram'] ?? null,
            'extra' => $stored['other'] ?? null,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $plan = $this->plans->planFor($user);

        $data = $request->validate([
            'phone' => ['nullable', 'string', 'regex:/^\+?[1-9]\d{7,14}$/'],
            'instagram' => ['nullable', 'string', 'regex:/^@?[A-Za-z0-9._]{1,30}$/'],
            'extra' => ['nullable', 'string', 'max:200', new PlainTextOnly],
        ]);

        foreach (self::FIELDS as $field => $type) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field] === null ? '' : trim($data[$field]);

            if ($value !== '' && ! $plan->allowsPublicContact($type)) {
                return response()->json(['error' => [
                    'code' => 'PLAN_REQUIRED',
                    'message' => 'عرض بيانات التواصل للزوار متاح في الباقة الاحترافية فقط.',
                ]], 403);
            }

            if ($field === 'instagram') {
                $value = ltrim($value, '@');
            }

            if ($value === '') {
                ContactMethod::where('user_id', $user->id)->where('type', $type)->delete();

                continue;
            }

            ContactMethod::updateOrCreate(
                ['user_id' => $user->id, 'type' => $type],
                ['value' => $value, 'label' => $type === 'other' ? 'معلومات إضافية' : null, 'privacy_state' => 'public']
            );
        }

        AuditLogger::log('profile.public_contact_updated', 'customer', $user->id, 'User', $user->id, 'success', [], $request);

        return $this->show($request);
    }
}
