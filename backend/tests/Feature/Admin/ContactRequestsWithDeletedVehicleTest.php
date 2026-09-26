<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\ContactRequest;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Regression: a request whose vehicle the customer later deleted crashed the list with a 500. */
class ContactRequestsWithDeletedVehicleTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_and_detail_still_render_after_the_vehicle_is_deleted(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);

        $vehicle = Vehicle::factory()->for(User::factory()->create())->create(['plate_number' => 'DEL-777']);
        $tag = Tag::create([
            'public_token' => bin2hex(random_bytes(16)), 'serial_number' => 'ST-Q-DV-1',
            'type' => 'qr', 'status' => 'assigned', 'vehicle_id' => $vehicle->id,
        ]);
        $request = ContactRequest::create(['vehicle_id' => $vehicle->id, 'tag_id' => $tag->id, 'message' => 'x', 'reason' => 'damage']);
        $vehicle->delete();

        $this->actingAs($admin, 'admin')->get('/admin/contact-requests')->assertOk()->assertSee('DEL-777')->assertSee('محذوفة');
        $this->actingAs($admin, 'admin')->get("/admin/contact-requests/{$request->id}")->assertOk()->assertSee('مركبة محذوفة');
    }
}
