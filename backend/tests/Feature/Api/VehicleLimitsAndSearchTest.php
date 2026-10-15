<?php

namespace Tests\Feature\Api;

use App\Models\Admin;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\PlanEntitlements;
use App\Services\QrBatchService;
use App\Services\SubscriptionManager;
use App\Services\TagLifecycleService;
use App\Support\PhoneSearch;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Free plan = one vehicle, Plus = three; the newest vehicles go inactive when Plus ends and
 * come back with it. Plate styles. Admin phone search and the role's programmatic name.
 */
class VehicleLimitsAndSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();
        $this->withoutVite();
    }

    private function plus(): SubscriptionPlan
    {
        return SubscriptionPlan::where('slug', 'plus')->firstOrFail();
    }

    private function addVehicle(User $user, string $plate)
    {
        return $this->actingAs($user, 'sanctum')->postJson('/api/v1/vehicles', ['plate_number' => $plate, 'vehicle_type' => 'sedan']);
    }

    private function admin(): Admin
    {
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);

        return $admin;
    }

    // ---- limits ------------------------------------------------------------------------------

    public function test_the_free_plan_holds_one_vehicle_and_is_told_to_activate_the_plan(): void
    {
        $user = User::factory()->create();

        $this->addVehicle($user, '11111')->assertCreated();
        $this->addVehicle($user, '22222')->assertStatus(403)
            ->assertJsonPath('error.code', 'VEHICLE_LIMIT')
            ->assertJsonPath('error.max_vehicles', 1)
            ->assertJsonPath('error.upgrade', true);

        $this->assertSame(1, $user->vehicles()->count());
    }

    public function test_plus_holds_three_vehicles_in_total(): void
    {
        $user = User::factory()->create();
        app(SubscriptionManager::class)->grant($user, $this->plus(), Admin::factory()->create()->id, 365);

        foreach (['11111', '22222', '33333'] as $plate) {
            $this->addVehicle($user, $plate)->assertCreated();
        }
        $this->addVehicle($user, '44444')->assertStatus(403)
            ->assertJsonPath('error.code', 'VEHICLE_LIMIT')->assertJsonPath('error.upgrade', false);
    }

    public function test_when_plus_ends_the_last_two_vehicles_go_inactive_and_return_with_it(): void
    {
        $user = User::factory()->create();
        $subscription = app(SubscriptionManager::class)->grant($user, $this->plus(), Admin::factory()->create()->id, 365);

        $first = Vehicle::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDays(3)]);
        $second = Vehicle::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDays(2)]);
        $third = Vehicle::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDay()]);

        $plans = app(PlanEntitlements::class);
        $this->assertSame([], $plans->lockedVehicleIds($user->fresh()));

        // The plan ends.
        $subscription->update(['status' => 'expired', 'current_period_ends_at' => now()->subMonths(3)]);
        Cache::flush();

        $this->assertSame([$second->id, $third->id], $plans->lockedVehicleIds($user->fresh()));

        $list = $this->actingAs($user->fresh(), 'sanctum')->getJson('/api/v1/vehicles')->assertOk()->json('data');
        $byId = collect($list)->keyBy('id');
        $this->assertFalse($byId[$first->uuid]['locked_by_plan'] ?? $byId[$first->id]['locked_by_plan']);

        // Plus comes back: nothing was deleted, everything answers again.
        app(SubscriptionManager::class)->grant($user->fresh(), $this->plus(), Admin::factory()->create()->id, 30);
        Cache::flush();
        $this->assertSame([], $plans->lockedVehicleIds($user->fresh()));
        $this->assertSame(3, $user->vehicles()->count());
    }

    public function test_a_locked_vehicle_cannot_get_a_code(): void
    {
        $user = User::factory()->create();
        Vehicle::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDay()]);
        $locked = Vehicle::factory()->create(['user_id' => $user->id, 'created_at' => now()]);

        $batch = app(QrBatchService::class)->createBatch(1, null);
        app(TagLifecycleService::class)->sendBatchToPrinting($batch, 'system', null);
        app(TagLifecycleService::class)->markBatchUnassigned($batch, 'system', null);
        $token = $batch->tags()->first()->public_token;

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tags/activate', ['token' => $token, 'vehicle_id' => $locked->uuid])
            ->assertStatus(403)->assertJsonPath('error.code', 'VEHICLE_LOCKED');
    }

    // ---- plate style -----------------------------------------------------------------------------

    public function test_the_plate_style_is_stored_and_defaults_to_other(): void
    {
        $user = User::factory()->create();

        $this->addVehicle($user, '11111')->assertCreated()->assertJsonPath('data.plate_style', 'other');

        $other = User::factory()->create();
        $this->actingAs($other, 'sanctum')->postJson('/api/v1/vehicles', [
            'plate_number' => '34567', 'plate_letter' => 'ب', 'plate_governorate' => '14', 'plate_style' => 'english', 'vehicle_type' => 'sedan',
        ])->assertCreated()->assertJsonPath('data.plate_style', 'english');

        $this->actingAs($other, 'sanctum')->postJson('/api/v1/vehicles', ['plate_number' => '1', 'plate_style' => 'klingon'])
            ->assertStatus(422);
    }

    // ---- phone search --------------------------------------------------------------------------------

    public function test_phone_search_patterns(): void
    {
        $this->assertSame('%7727009040%', PhoneSearch::pattern('07727009040'));
        $this->assertSame('%7727009040%', PhoneSearch::pattern('7727009040'));
        $this->assertSame('%7727009040%', PhoneSearch::pattern('+964 772 700 9040'));
        $this->assertSame('%7727009040%', PhoneSearch::pattern('9647727009040'));
        $this->assertSame('%7727009040%', PhoneSearch::pattern('٠٧٧٢٧٠٠٩٠٤٠'));
        $this->assertNull(PhoneSearch::pattern('أحمد'));
        $this->assertNull(PhoneSearch::pattern('12'));
    }

    public function test_the_dashboard_finds_a_customer_by_the_number_in_any_form(): void
    {
        $admin = $this->admin();
        User::factory()->create(['name' => 'صاحب الرقم', 'phone' => '+9647727009040']);
        User::factory()->create(['name' => 'شخص آخر', 'phone' => '+9647801112233']);

        foreach (['07727009040', '7727009040', '+9647727009040', '9647727009040'] as $q) {
            $this->actingAs($admin, 'admin')->get('/admin/users?q='.urlencode($q))
                ->assertOk()->assertSee('صاحب الرقم')->assertDontSee('شخص آخر');
        }
    }

    // ---- role name -------------------------------------------------------------------------------------

    public function test_a_role_name_with_spaces_is_normalised_by_the_system(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->post('/admin/roles', [
            'name_ar' => 'مدير إقليمي', 'name_en' => 'Regional Manager', 'permissions' => [],
        ])->assertRedirect();

        $role = Role::where('name_ar', 'مدير إقليمي')->firstOrFail();
        $this->assertSame('Regional_Manager', $role->name_en);
        $this->assertSame('regional_manager', $role->slug);
    }
}
