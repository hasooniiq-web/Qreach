<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Voucher;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The made-up data of the test environment: it builds, and it refuses to run anywhere else. */
class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fills_every_part_of_the_dashboard_with_invented_data(): void
    {
        config(['app.test_banner' => true]);
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SubscriptionPlanSeeder::class);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);

        $this->seed(DemoDataSeeder::class);

        $this->assertSame(24, User::count());
        $this->assertSame(20, Vehicle::count());
        $this->assertSame(60, Tag::count());
        $this->assertSame(18, Tag::where('status', 'assigned')->count());
        $this->assertSame(25, Voucher::count());
        $this->assertGreaterThanOrEqual(5, Subscription::where('status', 'active')->count());
        $this->assertSame(0, User::where('phone', 'not like', '+96477000%')->count(), 'Only invented numbers.');
    }

    public function test_it_will_not_run_without_the_test_banner_flag(): void
    {
        config(['app.test_banner' => false]);
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SubscriptionPlanSeeder::class);

        $this->seed(DemoDataSeeder::class);

        $this->assertSame(0, User::count());
    }
}
