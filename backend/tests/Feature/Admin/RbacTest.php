<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Section 20/26 regression coverage: every admin route must be permission-
 * gated, not just authentication-gated. Verified manually during
 * development (a support_admin account was correctly blocked from
 * /admin/admins and /admin/batches/create) — this locks that in.
 */
class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_role_can_access_admin_management(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);

        $this->actingAs($admin, 'admin')->get('/admin/admins')->assertStatus(200);
    }

    public function test_support_admin_role_cannot_access_admin_management(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'support_admin')->value('id')]);

        $this->actingAs($admin, 'admin')->get('/admin/admins')->assertStatus(403);
    }

    public function test_support_admin_role_cannot_create_qr_batches(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'support_admin')->value('id')]);

        $this->actingAs($admin, 'admin')->get('/admin/batches/create')->assertStatus(403);
    }

    public function test_support_admin_role_can_view_contact_requests(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'support_admin')->value('id')]);

        $this->actingAs($admin, 'admin')->get('/admin/contact-requests')->assertStatus(200);
    }

    public function test_an_admin_with_no_roles_is_denied_everywhere(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')->get('/admin/users')->assertStatus(403);
    }
}
