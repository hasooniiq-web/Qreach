<?php

namespace Tests\Feature\Api;

use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Security regression tests for the QR/NFC activation engine (sections 11,
 * 44, 45). These lock in behavior discovered and manually verified during
 * development — duplicate activation, cross-user ownership, and revoked-tag
 * rejection must never silently regress.
 */
class TagActivationTest extends TestCase
{
    use RefreshDatabase;

    private function makeAvailableTag(): Tag
    {
        return Tag::create([
            'public_token' => bin2hex(random_bytes(16)),
            'serial_number' => 'ST-Q-TEST-'.random_int(100000, 999999),
            'type' => 'qr',
            'status' => 'available',
        ]);
    }

    public function test_customer_can_activate_an_available_tag_onto_their_own_vehicle(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user)->create();
        $tag = $this->makeAvailableTag();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tags/activate', [
            'token' => $tag->public_token,
            'vehicle_id' => $vehicle->uuid,
        ]);

        $response->assertStatus(201)->assertJsonPath('data.status', 'assigned');

        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'status' => 'assigned', 'vehicle_id' => $vehicle->id]);
        $this->assertDatabaseCount('tag_assignments', 1);
    }

    public function test_activating_an_already_assigned_tag_fails_with_a_structured_error(): void
    {
        $user = User::factory()->create();
        $vehicleA = Vehicle::factory()->for($user)->create();
        $vehicleB = Vehicle::factory()->for($user)->create();
        $tag = $this->makeAvailableTag();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/tags/activate', [
            'token' => $tag->public_token,
            'vehicle_id' => $vehicleA->uuid,
        ])->assertStatus(201);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tags/activate', [
            'token' => $tag->public_token,
            'vehicle_id' => $vehicleB->uuid,
        ]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'TAG_ALREADY_ACTIVATED');
        $this->assertDatabaseCount('tag_assignments', 1);
    }

    public function test_customer_cannot_activate_a_tag_onto_another_customers_vehicle(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $vehicle = Vehicle::factory()->for($owner)->create();
        $tag = $this->makeAvailableTag();

        $response = $this->actingAs($attacker, 'sanctum')->postJson('/api/v1/tags/activate', [
            'token' => $tag->public_token,
            'vehicle_id' => $vehicle->uuid,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'status' => 'available']);
    }

    public function test_a_revoked_tag_cannot_be_activated(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user)->create();
        $tag = $this->makeAvailableTag();
        $tag->update(['status' => 'revoked', 'revoked_at' => now(), 'revocation_reason' => 'test']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tags/activate', [
            'token' => $tag->public_token,
            'vehicle_id' => $vehicle->uuid,
        ]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'TAG_REVOKED');
    }

    public function test_activating_an_unknown_token_returns_not_found_without_leaking_detail(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user)->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tags/activate', [
            'token' => 'this-token-does-not-exist',
            'vehicle_id' => $vehicle->uuid,
        ]);

        $response->assertStatus(422)->assertJsonPath('error.code', 'TAG_NOT_FOUND');
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->postJson('/api/v1/tags/activate', ['token' => 'x', 'vehicle_id' => 'y'])
            ->assertStatus(401);
    }

    public function test_revoking_a_tag_persists_the_reason_and_timestamp(): void
    {
        // Regression: Tag was missing revocation_reason/revoked_at from
        // $fillable, so TagLifecycleService::revoke()'s update() silently
        // dropped both fields even though the status change succeeded.
        $tag = $this->makeAvailableTag();
        $tag->update(['status' => 'assigned']);

        app(\App\Services\TagLifecycleService::class)->revoke($tag, 'Lost by customer', 'admin', null);

        $tag->refresh();
        $this->assertSame('revoked', $tag->status);
        $this->assertSame('Lost by customer', $tag->revocation_reason);
        $this->assertNotNull($tag->revoked_at);
    }
}
