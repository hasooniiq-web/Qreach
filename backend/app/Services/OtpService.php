<?php

namespace App\Services;

use App\Contracts\SmsGatewayContract;
use App\Models\OtpCode;
use App\Services\SecurityEventLogger;
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

    private const TTL_MINUTES = 5;

    private const RESEND_COOLDOWN_SECONDS = 60;

    public function __construct(private readonly SmsGatewayContract $sms) {}

    public function request(string $phone, string $purpose = 'login'): OtpCode
    {
        $recent = OtpCode::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if ($recent && $recent->created_at->diffInSeconds(now()) < self::RESEND_COOLDOWN_SECONDS) {
            throw new \App\Exceptions\OtpCooldownException(
                self::RESEND_COOLDOWN_SECONDS - $recent->created_at->diffInSeconds(now())
            );
        }

        $code = (string) random_int(100000, 999999);

        $otp = OtpCode::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'purpose' => $purpose,
            'max_attempts' => 5,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'request_ip' => request()->ip(),
        ]);

        $this->sms->send($phone, "رمز التحقق الخاص بك في Qreach: {$code}. صالح لمدة 5 دقائق.");

        return $otp;
    }

    /**
     * @throws \App\Exceptions\OtpInvalidException
     */
    public function verify(string $phone, string $code, string $purpose = 'login'): void
    {
        $otp = OtpCode::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $otp || $otp->isExpired()) {
            SecurityEventLogger::log(SecurityEventLogger::OTP_ABUSE, 'info', 'customer', null, [
                'phone' => $this->maskPhone($phone), 'reason' => 'expired_or_missing',
            ]);
            throw new \App\Exceptions\OtpInvalidException('رمز التحقق غير صالح أو منتهي الصلاحية.');
        }

        if ($otp->attemptsExhausted()) {
            SecurityEventLogger::log(SecurityEventLogger::OTP_ABUSE, 'warning', 'customer', null, [
                'phone' => $this->maskPhone($phone), 'reason' => 'attempts_exhausted',
            ]);
            throw new \App\Exceptions\OtpInvalidException('تم تجاوز عدد المحاولات المسموح به. اطلب رمزًا جديدًا.');
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            if ($otp->attempts >= 3) {
                SecurityEventLogger::log(SecurityEventLogger::OTP_ABUSE, 'warning', 'customer', null, [
                    'phone' => $this->maskPhone($phone), 'attempts' => $otp->attempts,
                ]);
            }

            throw new \App\Exceptions\OtpInvalidException('رمز التحقق غير صحيح.');
        }

        $otp->update(['consumed_at' => now()]);
    }

    private function maskPhone(string $phone): string
    {
        return Str::mask($phone, '*', 3, -2);
    }
}
