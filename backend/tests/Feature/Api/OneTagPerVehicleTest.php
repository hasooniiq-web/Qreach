<?php

namespace Tests\Feature\Api;

use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A vehicle has exactly one code. To change it the owner removes the
 * current code, then links a new one.
 */
class OneTagPerVehicleTest extends TestCase
{
    use RefreshDatabase;

    private function tag(): Tag
    {
        return Tag::create([
            'public_token' => bin2hex(random_bytes(16)), 'serial_number' => 'ST-Q-1T-'.random_int(100000, 999999),
            'type' => 'qr', 'status' => 'available',
        ]);
    }

    private function activate(User $user, Vehicle $vehicle, Tag $tag)
    {
        return $this->actingAs($user, 'sanctum')->postJson('/api/v1/tags/activate', [
            'token' => $tag->public_token, 'vehicle_id' => $vehicle->uuid,
        ]);
    }

    public function test_a_second_code_cannot_be_linked_to_a_vehicle_that_already_has_one(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user)->create();
        $first = $this->tag();
        $second = $this->tag();

        $this->activate($user, $vehicle, $first)->assertCreated();
        $this->activate($user, $vehicle, $second)
            ->assertStatus(422)->assertJsonPath('error.code', 'VEHICLE_ALREADY_HAS_TAG');

        // The rejected code stays untouched and can still go on another vehicle.
        $this->assertSame('available', $second->fresh()->status);
        $this->assertSame(1, Tag::where('vehicle_id', $vehicle->id)->count());
    }

    public function test_removing_the_code_frees_the_vehicle_for_a_new_one_and_the_old_code_stays_dead(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user)->create();
        $old = $this->tag();
        $new = $this->tag();
        $this->activate($user, $vehicle, $old)->assertCreated();

        $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/vehicles/{$vehicle->uuid}/tag")->assertOk();

        $this->assertSame('revoked', $old->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'qr.revoked', 'resource_id' => $old->id]);

        $this->activate($user, $vehicle, $new)->assertCreated();
        $this->assertSame('assigned', $new->fresh()->status);

        // A removed code can never be linked again, by anyone.
        $this->activate($user, Vehicle::factory()->for($user)->create(), $old)
            ->assertStatus(422)->assertJsonPath('error.code', 'TAG_REVOKED');
    }

    public function test_removing_when_there_is_no_code_is_a_clear_404(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user)->create();

        $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/vehicles/{$vehicle->uuid}/tag")
            ->assertNotFound()->assertJsonPath('error.code', 'NO_TAG');
    }

    public function test_a_user_cannot_remove_another_users_code(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $vehicle = Vehicle::factory()->for($owner)->create();
        $tag = $this->tag();
        $this->activate($owner, $vehicle, $tag)->assertCreated();

        $this->app['auth']->forgetGuards();
        $this->actingAs($intruder, 'sanctum')->deleteJson("/api/v1/vehicles/{$vehicle->uuid}/tag")->assertForbidden();

        $this->assertSame('assigned', $tag->fresh()->status);
    }
}
