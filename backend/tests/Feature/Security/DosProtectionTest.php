<?php

namespace Tests\Feature\Security;

use App\Models\Admin;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Flooding protection inside the application: a ceiling over all browser traffic, the
 * API ceiling, and limits on the expensive operations (exports).
 */
class DosProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->withoutVite();
    }

    private function admin(): Admin
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);

        return $admin;
    }

    public function test_one_address_cannot_flood_the_public_pages(): void
    {
        $this->seed(SubscriptionPlanSeeder::class);
        config(['security.web_per_minute' => 5]);

        // The landing page also has its own (30/min) limit; the global ceiling is the lower one here.
        foreach (range(1, 5) as $i) {
            $this->get('/admin/login')->assertOk();
        }

        $this->get('/admin/login')->assertStatus(429);
    }

    public function test_the_ceiling_is_counted_per_address_not_for_everyone(): void
    {
        config(['security.web_per_minute' => 3]);

        foreach (range(1, 3) as $i) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.1.1.1'])->get('/admin/login')->assertOk();
        }
        $this->withServerVariables(['REMOTE_ADDR' => '10.1.1.1'])->get('/admin/login')->assertStatus(429);

        // Another visitor is unaffected.
        $this->withServerVariables(['REMOTE_ADDR' => '10.2.2.2'])->get('/admin/login')->assertOk();
    }

    public function test_the_api_has_a_per_address_ceiling_that_holds_before_sign_in(): void
    {
        config(['security.api_per_minute' => 5]);

        // Unauthenticated requests are refused with 401 — and still counted, so a script
        // hammering the API without a token is stopped by the ceiling, not just answered.
        foreach (range(1, 5) as $i) {
            $this->getJson('/api/v1/subscription')->assertStatus(401);
        }

        $this->getJson('/api/v1/subscription')
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'RATE_LIMITED')
            ->assertHeader('Retry-After');
    }

    public function test_the_api_and_the_web_ceilings_are_counted_separately(): void
    {
        config(['security.api_per_minute' => 2, 'security.web_per_minute' => 100]);

        $this->getJson('/api/v1/subscription');
        $this->getJson('/api/v1/subscription');
        $this->getJson('/api/v1/subscription')->assertStatus(429);

        $this->get('/admin/login')->assertOk();
    }

    public function test_exports_are_limited_per_admin_per_minute(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        foreach (range(1, 6) as $i) {
            $this->get('/admin/users?export=xlsx')->assertOk();
        }

        $this->get('/admin/users?export=xlsx')->assertStatus(429);
    }

    public function test_only_two_exports_run_at_the_same_time(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        // Two exports are "running" (their slots are held) ...
        $a = Cache::lock('export-slot-1', 120);
        $b = Cache::lock('export-slot-2', 120);
        $this->assertTrue($a->get());
        $this->assertTrue($b->get());

        // ... so a third is told to wait, instead of piling more memory use on the server.
        $this->get('/admin/users?export=xlsx')->assertStatus(429);

        $a->release();
        $this->get('/admin/users?export=xlsx')->assertOk();
        $b->release();
    }

    public function test_an_export_always_gives_its_slot_back(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');
        User::factory()->count(3)->create();

        foreach (range(1, 4) as $i) {
            $this->get('/admin/users?export=pdf')->assertOk();
        }

        $this->assertTrue(Cache::lock('export-slot-1', 5)->get(), 'A finished export must not keep its slot.');
    }
}
