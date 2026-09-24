<?php

namespace Tests\Feature\Api;

use App\Models\OtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Regression coverage for a real bug found during manual testing: OtpCode
 * was missing `consumed_at` from $fillable, so OtpService::verify()'s
 * `$otp->update(['consumed_at' => now()])` was silently dropped by
 * Eloquent's mass-assignment guard — meaning a correct OTP code could be
 * replayed indefinitely within its validity window (section 45: OTP replay).
 */
class OtpSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_otp_code_cannot_be_verified_twice(): void
    {
        $phone = '+966500000099';
        $code = '123456';

        OtpCode::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'purpose' => 'login',
            'max_attempts' => 5,
            'expires_at' => now()->addMinutes(5),
        ]);

        $first = $this->postJson('/api/v1/auth/otp/verify', ['phone' => $phone, 'code' => $code]);
        $first->assertStatus(200);

        // Replaying the exact same code must fail now that it's consumed.
        $second = $this->postJson('/api/v1/auth/otp/verify', ['phone' => $phone, 'code' => $code]);
        $second->assertStatus(422)->assertJsonPath('error.code', 'OTP_INVALID');
    }

    public function test_consumed_at_is_actually_persisted_on_verification(): void
    {
        $phone = '+966500000098';
        $code = '654321';

        $otp = OtpCode::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'purpose' => 'login',
            'max_attempts' => 5,
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->postJson('/api/v1/auth/otp/verify', ['phone' => $phone, 'code' => $code])->assertStatus(200);

        $this->assertNotNull($otp->fresh()->consumed_at);
    }
}
