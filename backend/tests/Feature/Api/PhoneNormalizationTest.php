<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The system is Iraq-only: no phone field anywhere should ever require a
 * country code, and "07..." (the leading trunk zero people actually dial
 * with) must be accepted everywhere "7..." is — login, emergency contact and
 * the phone/whatsapp contact platforms all normalize to the same "+964..."
 * value regardless of which form was typed.
 */
class PhoneNormalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);
    }

    private function bearer(User $u): array
    {
        return ['Authorization' => 'Bearer '.$u->createToken('t')->plainTextToken];
    }

    public function test_otp_request_treats_the_leading_zero_and_bare_forms_as_the_same_phone(): void
    {
        $this->postJson('/api/v1/auth/otp/request', ['phone' => '07701234567'])
            ->assertStatus(200);

        $this->assertDatabaseHas('otp_codes', ['phone' => '+9647701234567']);

        // Same subscriber, no zero this time — the resend cooldown fires
        // (429 OTP_COOLDOWN), which only happens if both requests were
        // normalized to the identical stored phone.
        $this->postJson('/api/v1/auth/otp/request', ['phone' => '7701234567'])
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'OTP_COOLDOWN');
    }

    public function test_emergency_contact_phone_is_normalized_regardless_of_the_leading_zero(): void
    {
        $user = User::factory()->create();

        $this->putJson('/api/v1/profile/emergency', [
            'name' => 'صديق', 'phone' => '07709999999', 'visible' => false,
        ], $this->bearer($user))->assertStatus(200);

        $this->assertDatabaseHas('emergency_contacts', ['user_id' => $user->id, 'phone' => '+9647709999999']);
    }

    public function test_the_phone_shown_to_visitors_is_normalized_regardless_of_the_leading_zero(): void
    {
        $user = User::factory()->create();

        $this->putJson('/api/v1/profile/contact', [
            'phone' => ['value' => '07701234567', 'visible' => false],
        ], $this->bearer($user))->assertStatus(200);

        $this->assertDatabaseHas('contact_methods', ['user_id' => $user->id, 'type' => 'phone', 'value' => '+9647701234567']);
    }
}
