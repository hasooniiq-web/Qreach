<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdatePushTokenRequest;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Push-notification device registration (section 15). A device row is
 * scoped to the current access token, not just the user, because a single
 * customer can be logged in on several phones at once — each needs its own
 * push token, and logging out one device must not affect the others'
 * notifications.
 */
class DeviceController extends Controller
{
    public function updatePushToken(UpdatePushTokenRequest $request): JsonResponse
    {
        $user = $request->user();
        $tokenId = $user->currentAccessToken()->id;

        Device::updateOrCreate(
            ['user_id' => $user->id, 'personal_access_token_id' => $tokenId],
            [
                'platform' => $request->validated('platform'),
                'push_provider' => $request->validated('push_provider'),
                'push_token' => $request->validated('push_token'),
                'device_name' => $request->validated('device_name'),
                'last_active_at' => now(),
                'is_active' => true,
            ]
        );

        return response()->json(['message' => 'تم تسجيل الجهاز للإشعارات.']);
    }

    /**
     * Why a phone could not obtain a push token (e.g. no APNs token from Apple).
     * The app reports it; it is only logged — nothing is stored or acted on — so
     * a failing iPhone can be diagnosed from the server without a debugger.
     */
    public function pushDiagnostic(Request $request): JsonResponse
    {
        $data = $request->validate([
            'platform' => ['required', 'in:ios,android'],
            'reason' => ['required', 'string', 'max:80'],
            'permission' => ['nullable', 'string', 'max:40'],
            'has_apns_token' => ['nullable', 'boolean'],
            'detail' => ['nullable', 'string', 'max:500'],
        ]);

        Log::warning('push diagnostic', ['user_id' => $request->user()->id] + $data);

        return response()->json(['ok' => true]);
    }
}
