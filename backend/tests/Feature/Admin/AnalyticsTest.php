<?php

namespace Tests\Feature\Admin;

use App\Console\Commands\AggregateAnalytics;
use App\Models\Admin;
use App\Models\Role;
use App\Models\Scan;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Section 43: dashboard charts must read only from analytics_daily_stats,
 * populated by a real aggregation job — never fabricated numbers.
 */
class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_aggregation_command_computes_real_counts_from_scans(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user)->create();
        $tag = Tag::create([
            'public_token' => bin2hex(random_bytes(16)),
            'serial_number' => 'ST-Q-TEST-1',
            'type' => 'qr', 'status' => 'assigned', 'vehicle_id' => $vehicle->id,
        ]);

        Scan::create(['tag_id' => $tag->id, 'vehicle_id' => $vehicle->id, 'action_type' => 'view', 'status' => 'success']);
        Scan::create(['tag_id' => $tag->id, 'vehicle_id' => $vehicle->id, 'action_type' => 'view', 'status' => 'success']);
        Scan::create(['tag_id' => $tag->id, 'vehicle_id' => $vehicle->id, 'action_type' => 'emergency', 'status' => 'success']);

        $this->artisan(AggregateAnalytics::class, ['date' => Carbon::today()->toDateString()])
            ->assertSuccessful();

        $this->assertDatabaseHas('analytics_daily_stats', [
            'metric_key' => 'scans', 'dimension' => 'all', 'value' => 3,
        ]);
        $this->assertDatabaseHas('analytics_daily_stats', [
            'metric_key' => 'scans', 'dimension' => 'view', 'value' => 2,
        ]);
        $this->assertDatabaseHas('analytics_daily_stats', [
            'metric_key' => 'scans', 'dimension' => 'emergency', 'value' => 1,
        ]);
    }

    public function test_re_running_aggregation_for_the_same_day_does_not_duplicate_rows(): void
    {
        $this->artisan(AggregateAnalytics::class, ['date' => Carbon::today()->toDateString()])->assertSuccessful();

        // 1 total + 6 per-action-type scan rows + registrations + qr_activations
        // + contact_requests + notifications = 11 distinct metric/dimension rows.
        $this->assertDatabaseCount('analytics_daily_stats', 11);

        $this->artisan(AggregateAnalytics::class, ['date' => Carbon::today()->toDateString()])->assertSuccessful();

        // Re-running for the same day must update in place, not duplicate.
        $this->assertDatabaseCount('analytics_daily_stats', 11);
    }

    public function test_analytics_page_requires_permission(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')->get('/admin/analytics')->assertStatus(403);
    }

    public function test_analytics_page_renders_with_no_fabricated_data(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/analytics?period=7d');

        $response->assertStatus(200);
        $response->assertViewHas('charts', function ($charts) {
            return $charts['scans']['total'] === 0;
        });
    }
}
