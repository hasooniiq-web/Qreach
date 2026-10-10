<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PushDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_phone_can_report_why_it_has_no_push_token_and_it_is_only_logged(): void
    {
        Log::spy();
        $user = User::factory()->create();
        $token = $user->createToken('iphone')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/devices/push-diagnostic', [
                'platform' => 'ios', 'reason' => 'apns_token_null', 'permission' => 'authorized', 'has_apns_token' => false,
            ])->assertOk()->assertJson(['ok' => true]);

        Log::shouldHaveReceived('warning')->withArgs(fn ($message, $context) => $message === 'push diagnostic'
            && $context['reason'] === 'apns_token_null' && $context['user_id'] === $user->id);
        $this->assertSame(0, Device::count());
    }

    public function test_it_needs_a_login_and_a_known_platform(): void
    {
        $this->postJson('/api/v1/devices/push-diagnostic', ['platform' => 'ios', 'reason' => 'x'])->assertUnauthorized();

        $this->app['auth']->forgetGuards();
        $token = User::factory()->create()->createToken('t')->plainTextToken;
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/devices/push-diagnostic', ['platform' => 'toaster', 'reason' => 'x'])->assertStatus(422);
    }
}
