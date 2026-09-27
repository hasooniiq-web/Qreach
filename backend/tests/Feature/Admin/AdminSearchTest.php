<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSearchTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role): Admin
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', $role)->value('id')]);

        return $admin;
    }

    public function test_global_search_finds_users_for_an_admin_who_may_view_them(): void
    {
        $admin = $this->admin('super_admin');
        User::factory()->create(['name' => 'حسين النجار', 'phone' => '+9647701239999']);

        $this->actingAs($admin, 'admin')->get('/admin/search?q=النجار')->assertOk()->assertSee('حسين النجار');
        $this->actingAs($admin, 'admin')->get('/admin/search?q=9999')->assertOk()->assertSee('9647701239999');
    }

    public function test_short_queries_return_nothing_and_guests_are_redirected(): void
    {
        $admin = $this->admin('super_admin');
        User::factory()->create(['name' => 'حسين']);

        $this->actingAs($admin, 'admin')->get('/admin/search?q=ح')->assertOk()->assertDontSee('حسين النجار');
        auth('admin')->logout();
        $this->get('/admin/search?q=test')->assertRedirect();
    }

    public function test_every_dashboard_page_gets_a_title_and_breadcrumbs(): void
    {
        $admin = $this->admin('super_admin');

        $this->actingAs($admin, 'admin')->get('/admin/users')->assertOk()->assertSee('data-page-header', false)->assertSee('مسار التنقل');
        $this->actingAs($admin, 'admin')->get('/admin/dashboard')->assertOk()->assertSee('data-page-header', false);
    }
}
