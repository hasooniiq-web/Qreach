<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Requests\Api\Auth\RequestOtpRequest;
use App\Http\Requests\Api\Auth\SetPasswordRequest;
use App\Http\Requests\Api\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\Device;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OtpService;
use App\Services\SecurityEventLogger;
use App\Services\SessionRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Customer authentication: create an account (name + phone + password,
 * phone verified once via OTP) and log in afterwards with phone + password.
 * An account created before this existed, or one whose owner never finished
 * setting a password, has no usable password yet (`password` is null) — it
 * reaches one the same way a forgotten password is reset: prove phone
 * ownership via OTP, then set a new password, in requestPasswordOtp/
 * setPassword below.
 */
class OtpAuthController extends Controller
{
    /** A precomputed hash of an arbitrary string — never a real password —
     *  checked when the phone isn't found, so a nonexistent account doesn't
     *  respond measurably faster than a wrong password would. Precomputed
     *  because hashing (argon2id) is deliberately slow; computing a fresh
     *  one per request would make that cost worse, not better. */
    private const DUMMY_HASH = '$argon2id$v=19$m=65536,t=4,p=1$anU4S0t5YU4xSmxEUGFhVA$pxaag4uKVWA09/DIAHzUrbjrBKG083vAhI3jq9LpxPM';

    public function __construct(private readonly OtpService $otp) {}

    /** Step 1 of creating a new account: collect name/phone/password, send the verification OTP. */
    public function register(RegisterRequest $request): JsonResponse
    {
        $phone = $request->string('phone')->toString();
        $existing = User::where('phone', $phone)->first();

        if ($existing && $existing->phone_verified_at) {
            return response()->json([
                'error' => ['code' => 'ALREADY_REGISTERED', 'message' => 'هذا الرقم مسجَّل بالفعل. سجّل الدخول بدلًا من ذلك.'],
            ], 422);
        }

        // Not persisted as a real account until verify() below succeeds —
        // an unverified row here just reserves the phone number against a
        // name/password someone typed, same as any pending signup would.
        User::updateOrCreate(
            ['phone' => $phone],
            ['name' => trim($request->string('name')), 'password' => $request->string('password')]
        );

        $this->otp->request($phone, 'register');

        return response()->json(['message' => 'تم إرسال رمز التحقق.']);
    }

    /** Step 2: verify the OTP sent in register() and activate the account. */
    public function verifyRegister(VerifyOtpRequest $request): JsonResponse
    {
        $phone = $request->string('phone')->toString();
        $this->otp->verify($phone, $request->string('code'), 'register');

        $user = User::where('phone', $phone)->first();
        abort_if(! $user, 422, 'لم يتم العثور على بيانات التسجيل. ابدأ من جديد.');

        if (! $user->phone_verified_at) {
            $user->update(['phone_verified_at' => now()]);
        }

        return $this->issueSession($user, $request, 'customer.register');
    }

    /** Phone + password. No OTP — that only happens once, during registration or a password (re)set. */
    public function login(LoginRequest $request): JsonResponse
    {
        $phone = $request->string('phone')->toString();
        $user = User::where('phone', $phone)->first();

        // Hash::check still runs against *some* hash even when the phone is
        // unknown, so a nonexistent account doesn't respond measurably
        // faster than a wrong password would.
        $hash = $user?->password ?? self::DUMMY_HASH;
        $validPassword = Hash::check($request->string('password'), $hash);

        if (! $user || ! $user->password || ! $validPassword || ! $user->phone_verified_at) {
            SecurityEventLogger::log(SecurityEventLogger::AUTH_FAILURE, 'info', 'customer', $user?->id, [], $request);
            AuditLogger::log('customer.login_failed', 'customer', $user?->id, 'User', $user?->id, 'failure', [], $request);

            return response()->json([
                'error' => ['code' => 'INVALID_CREDENTIALS', 'message' => 'رقم الهاتف أو كلمة المرور غير صحيحة.'],
            ], 422);
        }

        return $this->issueSession($user, $request, 'customer.login');
    }

    /** Sends the OTP used to set a first-ever password, or to reset a forgotten one — same request shape as the old phone-only login. */
    public function requestPasswordOtp(RequestOtpRequest $request): JsonResponse
    {
        $this->otp->request($request->string('phone'), 'password_reset');

        return response()->json(['message' => 'تم إرسال رمز التحقق.']);
    }

    /** Verifies that OTP and sets the new password in one step, then logs the customer in. */
    public function setPassword(SetPasswordRequest $request): JsonResponse
    {
        $phone = $request->string('phone')->toString();
        $this->otp->verify($phone, $request->string('code'), 'password_reset');

        $user = User::firstOrCreate(['phone' => $phone], ['status' => 'active']);
        $user->update([
            'password' => $request->string('password'),
            'phone_verified_at' => $user->phone_verified_at ?? now(),
        ]);

        return $this->issueSession($user->fresh(), $request, 'customer.password_set');
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

    /** Common tail of every flow that ends in a logged-in session: token, device row, audit/security logging. */
    private function issueSession(User $user, Request $request, string $auditAction): JsonResponse
    {
        // One active session per account. Revoking the previous tokens and creating
        // the new one happen in a single transaction that holds a row lock on the
        // user, so two logins racing for the same account serialize: the later one
        // wins and the earlier one's token is revoked — never two live sessions.
        $token = DB::transaction(function () use ($user, $request) {
            User::whereKey($user->id)->lockForUpdate()->first();

            $user->update(['last_login_at' => now()]);
            SessionRegistry::revokeAll($user);

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

            return $token;
        });

        SecurityEventLogger::log(SecurityEventLogger::AUTH_SUCCESS, 'info', 'customer', $user->id, [], $request);
        AuditLogger::log($auditAction, 'customer', $user->id, 'User', $user->id, 'success', [], $request);

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token->plainTextToken,
        ]);
    }
}
