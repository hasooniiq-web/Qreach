<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The topbar search must find records however the admin types them. */
class AdminSearchTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);

        return $admin;
    }

    public function test_users_are_found_by_name_in_any_case_and_by_phone_in_any_local_format(): void
    {
        $this->actingAs($this->admin(), 'admin');
        User::factory()->create(['name' => 'Ali Hassan', 'phone' => '+9647701234567']);

        foreach (['ali hassan', 'ALI', '07701234567', '7701234567', '+964 770 123 4567', '0770 123', '٠٧٧٠١٢٣٤٥٦٧'] as $query) {
            $this->get('/admin/search?q='.urlencode($query))->assertOk()->assertSee('Ali Hassan');
        }
    }

    public function test_tags_are_found_by_serial_in_any_case_and_by_token(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $tag = Tag::create(['public_token' => '7K3QZ0M4X9TBN2R8D5WHVYC1AJ', 'serial_number' => 'ST-Q-2026-000048', 'type' => 'qr', 'status' => 'generated']);

        $this->get('/admin/search?q=st-q-2026-000048')->assertOk()->assertSee('ST-Q-2026-000048');
        $this->get('/admin/search?q=7k3qz0m4x9tbn2r8d5whvyc1aj')->assertOk()->assertSee('ST-Q-2026-000048');
        $this->get('/admin/search?q=ST-Q-9999')->assertOk()->assertDontSee('ST-Q-2026-000048');
    }

    public function test_vehicles_are_found_by_plate_and_by_the_owners_phone(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $owner = User::factory()->create(['phone' => '+9647709998877']);
        Vehicle::factory()->for($owner)->create(['plate_number' => '48291', 'nickname' => 'Family Car']);

        $this->get('/admin/search?q=48291')->assertOk()->assertSee('48291');
        $this->get('/admin/search?q=family')->assertOk()->assertSee('48291');
        $this->get('/admin/search?q=0770999')->assertOk()->assertSee('48291');
    }
}
