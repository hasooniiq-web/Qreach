<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** One active login per customer account, enforced on the server. */
class SingleSessionTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(string $platform): string
    {
        // Sanctum caches the authenticated user within one test process.
        $this->app['auth']->forgetGuards();

        return $this->postJson('/api/v1/auth/login', [
            'phone' => '07701234567', 'password' => 'correct-horse',
            'platform' => $platform, 'device_name' => $platform,
        ])->assertOk()->json('token');
    }

    private function listVehicles(string $token): \Illuminate\Testing\TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->getJson('/api/v1/vehicles', ['Authorization' => 'Bearer '.$token]);
    }

    public function test_signing_in_on_a_new_device_logs_the_previous_one_out(): void
    {
        $user = User::factory()->create(['phone' => '+9647701234567', 'password' => 'correct-horse']);

        $phone = $this->signIn('ios');
        $this->listVehicles($phone)->assertOk();

        $tablet = $this->signIn('android');

        $this->listVehicles($tablet)->assertOk();
        $this->listVehicles($phone)->assertUnauthorized()->assertJsonPath('error.code', 'SESSION_REPLACED');

        $this->assertSame(1, $user->tokens()->count());
        $this->assertSame(1, Device::where('user_id', $user->id)->where('is_active', true)->count());
    }

    public function test_the_replaced_device_keeps_getting_rejected_not_just_once(): void
    {
        User::factory()->create(['phone' => '+9647701234567', 'password' => 'correct-horse']);
        $old = $this->signIn('ios');
        $this->signIn('android');

        foreach (range(1, 3) as $_) {
            $this->listVehicles($old)->assertUnauthorized();
        }
    }

    public function test_other_accounts_are_not_affected(): void
    {
        User::factory()->create(['phone' => '+9647701234567', 'password' => 'correct-horse']);
        $other = User::factory()->create(['phone' => '+9647709999999', 'password' => 'another-pass-1']);
        $otherToken = $other->createToken('x')->plainTextToken;

        $this->signIn('ios');

        $this->listVehicles($otherToken)->assertOk();
    }

    public function test_an_expired_token_cannot_access_protected_routes(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('x');
        $token->accessToken->forceFill(['created_at' => now()->subDays(200)])->save();

        $this->listVehicles($token->plainTextToken)->assertUnauthorized();
    }

    public function test_an_unknown_token_gets_the_generic_unauthenticated_code(): void
    {
        $this->listVehicles('999|doesnotexist')->assertUnauthorized()->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_logout_still_works_for_the_current_session(): void
    {
        User::factory()->create(['phone' => '+9647701234567', 'password' => 'correct-horse']);
        $token = $this->signIn('ios');

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/v1/auth/logout', [], ['Authorization' => 'Bearer '.$token])->assertOk();
        $this->listVehicles($token)->assertUnauthorized()->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }
}
