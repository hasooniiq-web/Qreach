<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Section 15 push-notification registration. A device row is keyed to the
 * current access token (not just the user), so re-registering from the same
 * logged-in device updates one row instead of accumulating duplicates.
 */
class DevicePushTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_register_a_push_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('device-a')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/devices/push-token', [
                'platform' => 'android',
                'push_provider' => 'fcm',
                'push_token' => 'fcm-token-abc123',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('devices', [
            'user_id' => $user->id,
            'platform' => 'android',
            'push_provider' => 'fcm',
            'push_token' => 'fcm-token-abc123',
            'is_active' => true,
        ]);
    }

    public function test_re_registering_the_same_device_updates_rather_than_duplicates(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('device-a')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/devices/push-token', [
                'platform' => 'android',
                'push_provider' => 'fcm',
                'push_token' => 'first-token',
            ])
            ->assertStatus(200);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/devices/push-token', [
                'platform' => 'android',
                'push_provider' => 'fcm',
                'push_token' => 'rotated-token',
            ])
            ->assertStatus(200);

        $this->assertSame(1, Device::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('devices', ['user_id' => $user->id, 'push_token' => 'rotated-token']);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->putJson('/api/v1/devices/push-token', [
            'platform' => 'android',
            'push_provider' => 'fcm',
            'push_token' => 'x',
        ])->assertStatus(401);
    }
}
