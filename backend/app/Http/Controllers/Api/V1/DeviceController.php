<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdatePushTokenRequest;
use App\Models\Device;
use Illuminate\Http\JsonResponse;

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
}
