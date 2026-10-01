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
        $phone = '+9647500000099';
        $code = '123456';

        OtpCode::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'purpose' => 'password_reset',
            'max_attempts' => 5,
            'expires_at' => now()->addMinutes(5),
        ]);

        $payload = ['phone' => $phone, 'code' => $code, 'password' => 'correct-horse'];

        $first = $this->postJson('/api/v1/auth/password/set', $payload);
        $first->assertStatus(200);

        // Replaying the exact same code must fail now that it's consumed.
        $second = $this->postJson('/api/v1/auth/password/set', $payload);
        $second->assertStatus(422)->assertJsonPath('error.code', 'OTP_INVALID');
    }

    public function test_consumed_at_is_actually_persisted_on_verification(): void
    {
        $phone = '+9647500000098';
        $code = '654321';

        $otp = OtpCode::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'purpose' => 'password_reset',
            'max_attempts' => 5,
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->postJson('/api/v1/auth/password/set', ['phone' => $phone, 'code' => $code, 'password' => 'correct-horse'])
            ->assertStatus(200);

        $this->assertNotNull($otp->fresh()->consumed_at);
    }
}
