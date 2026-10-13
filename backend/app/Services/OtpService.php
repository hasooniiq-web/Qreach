<?php

namespace App\Services;

use App\Contracts\SmsGatewayContract;
use App\Exceptions\OtpCooldownException;
use App\Exceptions\OtpLimitException;
use App\Exceptions\OtpInvalidException;
use App\Models\OtpCode;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * OTP generation/verification (section 23). The plaintext code is never
 * persisted — only an HMAC-style hash via Hash::make (bcrypt/argon2id,
 * same as passwords) so it can't be reversed even from a DB dump.
 */
class OtpService
{
    private const LENGTH = 6;

    public function __construct(private readonly SmsGatewayContract $sms) {}

    /** Admin-configurable via the settings page — see SystemSettingController. */
    public static function ttlMinutes(): int
    {
        return (int) SystemSetting::get('otp_code_ttl_minutes', 5);
    }

    public static function resendCooldownSeconds(): int
    {
        return (int) SystemSetting::get('otp_resend_cooldown_seconds', 60);
    }

    public function request(string $phone, string $purpose = 'login'): OtpCode
    {
        $ip = request()->ip();

        $this->enforceCaps($phone, $ip);

        // The wait between two sends to the same number doubles with every send in the
        // last hour (60 s, 2 min, 4 min, ...), so a number cannot be hammered at a steady rate.
        $sentLastHour = OtpCode::where('phone', $phone)->where('created_at', '>', now()->subHour())->count();
        $last = OtpCode::where('phone', $phone)->latest('id')->first();

        $cooldown = min(
            (int) config('security.otp.max_cooldown', 900),
            self::resendCooldownSeconds() * (2 ** max(0, $sentLastHour - 1))
        );

        if ($last && $last->created_at->diffInSeconds(now()) < $cooldown) {
            throw new OtpCooldownException($cooldown - (int) $last->created_at->diffInSeconds(now()));
        }

        $code = (string) random_int(100000, 999999);
        $ttl = self::ttlMinutes();

        $otp = OtpCode::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'purpose' => $purpose,
            'max_attempts' => 5,
            'expires_at' => now()->addMinutes($ttl),
            'request_ip' => $ip,
        ]);

        $this->sms->send($phone, "رمز التحقق الخاص بك في Qreach: {$code}. صالح لمدة {$ttl} دقائق.");

        return $otp;
    }

    /**
     * Refuses a new code when this number, this address, or the whole system has already
     * asked for its share. `retryAfter` is when the oldest counted code leaves its window.
     *
     * @throws OtpLimitException
     */
    private function enforceCaps(string $phone, ?string $ip): void
    {
        $caps = (array) config('security.otp');

        $checks = [
            [OtpCode::where('phone', $phone), 3600, (int) ($caps['per_phone_hour'] ?? 5)],
            [OtpCode::where('phone', $phone), 86400, (int) ($caps['per_phone_day'] ?? 10)],
            [OtpCode::query(), 86400, (int) ($caps['global_day'] ?? 3000)],
        ];

        if ($ip) {
            $checks[] = [OtpCode::where('request_ip', $ip), 3600, (int) ($caps['per_ip_hour'] ?? 15)];
            $checks[] = [OtpCode::where('request_ip', $ip), 86400, (int) ($caps['per_ip_day'] ?? 40)];
        }

        foreach ($checks as [$query, $window, $limit]) {
            $since = now()->subSeconds($window);
            $windowQuery = (clone $query)->where('created_at', '>', $since);

            $count = (clone $windowQuery)->count();

            if ($count >= $limit) {
                // Free again when enough of the oldest codes have aged out of the window.
                $pivot = (clone $windowQuery)->orderBy('created_at')->offset($count - $limit)->limit(1)->first();
                $retry = $pivot
                    ? max(1, (int) now()->diffInSeconds($pivot->created_at->copy()->addSeconds($window), false))
                    : $window;

                SecurityEventLogger::log(SecurityEventLogger::OTP_ABUSE, 'warning', 'customer', null, [
                    'phone' => $this->maskPhone($phone), 'reason' => 'send_cap', 'window' => $window,
                ]);

                throw new OtpLimitException($retry);
            }
        }
    }

    /**
     * @throws OtpInvalidException
     * @throws \App\Exceptions\AccountLockedException
     */
    public function verify(string $phone, string $code, string $purpose = 'login'): void
    {
        $lockout = app(LoginLockout::class);
        // A number whose code was guessed wrong three times in a row waits (1 h, then 10 h, ...).
        $lockout->assertNotLocked(LoginLockout::CUSTOMER_OTP, $phone);

        $otp = OtpCode::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $otp || $otp->isExpired()) {
            SecurityEventLogger::log(SecurityEventLogger::OTP_ABUSE, 'info', 'customer', null, [
                'phone' => $this->maskPhone($phone), 'reason' => 'expired_or_missing',
            ]);
            throw new OtpInvalidException('رمز التحقق غير صالح أو منتهي الصلاحية.');
        }

        if ($otp->attemptsExhausted()) {
            SecurityEventLogger::log(SecurityEventLogger::OTP_ABUSE, 'warning', 'customer', null, [
                'phone' => $this->maskPhone($phone), 'reason' => 'attempts_exhausted',
            ]);
            throw new OtpInvalidException('تم تجاوز عدد المحاولات المسموح به. اطلب رمزًا جديدًا.');
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            $state = $lockout->recordFailure(LoginLockout::CUSTOMER_OTP, $phone);
            if ($state['locked']) {
                SecurityEventLogger::log(SecurityEventLogger::ACCOUNT_LOCKED, 'warning', 'customer', null, [
                    'phone' => $this->maskPhone($phone), 'stage' => 'otp', 'retry_after' => $state['retry_after'],
                ]);

                throw new \App\Exceptions\AccountLockedException($state['locked_until'], $state['retry_after']);
            }

            if ($otp->attempts >= 3) {
                SecurityEventLogger::log(SecurityEventLogger::OTP_ABUSE, 'warning', 'customer', null, [
                    'phone' => $this->maskPhone($phone), 'attempts' => $otp->attempts,
                ]);
            }

            throw new OtpInvalidException('رمز التحقق غير صحيح.');
        }

        $otp->update(['consumed_at' => now()]);
        $lockout->clear(LoginLockout::CUSTOMER_OTP, $phone);
    }

    private function maskPhone(string $phone): string
    {
        return Str::mask($phone, '*', 3, -2);
    }
}
