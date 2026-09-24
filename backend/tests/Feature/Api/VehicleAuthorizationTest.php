<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * IDOR/BOLA regression coverage for vehicle ownership (sections 9, 45:
 * "IDOR", "BOLA", "Broken function-level authorization").
 */
class VehicleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_view_another_customers_vehicle(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $vehicle = Vehicle::factory()->for($owner)->create();

        $this->actingAs($attacker, 'sanctum')
            ->getJson("/api/v1/vehicles/{$vehicle->uuid}")
            ->assertStatus(403);
    }

    public function test_customer_cannot_update_another_customers_vehicle(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $vehicle = Vehicle::factory()->for($owner)->create(['nickname' => 'original']);

        $this->actingAs($attacker, 'sanctum')
            ->putJson("/api/v1/vehicles/{$vehicle->uuid}", ['nickname' => 'hijacked'])
            ->assertStatus(403);

        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'nickname' => 'original']);
    }

    public function test_customer_cannot_delete_another_customers_vehicle(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $vehicle = Vehicle::factory()->for($owner)->create();

        $this->actingAs($attacker, 'sanctum')
            ->deleteJson("/api/v1/vehicles/{$vehicle->uuid}")
            ->assertStatus(403);

        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'deleted_at' => null]);
    }

    public function test_vehicle_list_only_returns_the_authenticated_users_own_vehicles(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Vehicle::factory()->for($user)->count(2)->create();
        Vehicle::factory()->for($other)->count(3)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/vehicles');

        $response->assertStatus(200)->assertJsonCount(2, 'data');
    }

    public function test_customer_can_manage_their_own_vehicle(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user)->create();

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/vehicles/{$vehicle->uuid}", ['nickname' => 'my car'])
            ->assertStatus(200)
            ->assertJsonPath('data.nickname', 'my car');
    }
}
