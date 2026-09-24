<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RequestOtpRequest;
use App\Http\Requests\Api\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\Device;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OtpService;
use App\Services\SecurityEventLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Customer authentication: phone + OTP only, no password (section 23).
 * Registration and login share the same endpoint pair — an unrecognized
 * phone number is silently registered on successful OTP verification,
 * which avoids leaking whether a phone number already has an account
 * (account enumeration, section 23).
 */
class OtpAuthController extends Controller
{
    public function __construct(private readonly OtpService $otp) {}

    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        $this->otp->request($request->string('phone'));

        return response()->json(['message' => 'تم إرسال رمز التحقق.']);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $phone = $request->string('phone')->toString();

        $this->otp->verify($phone, $request->string('code'));

        $user = DB::transaction(function () use ($phone) {
            $user = User::firstOrCreate(
                ['phone' => $phone],
                ['status' => 'active', 'phone_verified_at' => now()]
            );

            // firstOrCreate()'s in-memory instance doesn't reflect column
            // defaults (locale/timezone) applied at the DB level on insert.
            return $user->wasRecentlyCreated ? $user->fresh() : $user;
        });

        if (! $user->phone_verified_at) {
            $user->update(['phone_verified_at' => now()]);
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken($request->input('device_name', 'mobile'));

        if ($request->filled('platform')) {
            Device::create([
                'user_id' => $user->id,
                'personal_access_token_id' => $token->accessToken->id,
                'platform' => $request->input('platform'),
                'push_provider' => $request->input('push_provider'),
                'push_token' => $request->input('push_token'),
                'device_name' => $request->input('device_name'),
                'last_active_at' => now(),
            ]);
        }

        SecurityEventLogger::log(SecurityEventLogger::AUTH_SUCCESS, 'info', 'customer', $user->id, [], $request);
        AuditLogger::log('customer.login', 'customer', $user->id, 'User', $user->id, 'success', [], $request);

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token->plainTextToken,
        ]);
    }

    public function logout(): JsonResponse
    {
        $user = request()->user();
        $tokenId = $user->currentAccessToken()->id;

        Device::where('personal_access_token_id', $tokenId)->update(['is_active' => false, 'revoked_at' => now()]);
        $user->currentAccessToken()->delete();

        AuditLogger::log('customer.logout', 'customer', $user->id, 'User', $user->id);

        return response()->json(['message' => 'تم تسجيل الخروج.']);
    }

    public function logoutAllDevices(): JsonResponse
    {
        $user = request()->user();
        Device::where('user_id', $user->id)->update(['is_active' => false, 'revoked_at' => now()]);
        $user->tokens()->delete();

        AuditLogger::log('customer.logout_all_devices', 'customer', $user->id, 'User', $user->id);

        return response()->json(['message' => 'تم تسجيل الخروج من جميع الأجهزة.']);
    }
}
