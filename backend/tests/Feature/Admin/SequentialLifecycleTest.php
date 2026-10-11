<?php

namespace Tests\Feature\Admin;

use App\Exceptions\TagActivationException;
use App\Models\Admin;
use App\Models\PlanUpgradeRequest;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\QrBatchService;
use App\Services\SubscriptionManager;
use App\Services\TagLifecycleService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Client round 4: a code moves strictly generated -> printing -> unassigned ->
 * assigned; it can only be assigned after it was sent to print, and only a
 * code that was assigned can be cancelled. Plus the list/history fixes.
 */
class SequentialLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);

        return $admin;
    }

    private function paidPlan(): SubscriptionPlan
    {
        return SubscriptionPlan::query()->where('price_cents', '>', 0)->first()
            ?? SubscriptionPlan::create(['slug' => 'plus', 'name_ar' => 'بلس', 'price_cents' => 25000, 'currency' => 'IQD', 'billing_period_days' => 365, 'is_active' => true]);
    }

    public function test_a_code_that_was_not_sent_to_print_cannot_be_assigned(): void
    {
        $tag = app(QrBatchService::class)->createBatch(1, null)->tags()->first();
        $this->assertSame('generated', $tag->status);

        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user)->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/tags/activate', [
            'token' => $tag->public_token, 'vehicle_id' => $vehicle->uuid,
        ])->assertStatus(422)->assertJsonPath('error.code', 'TAG_NOT_READY');

        $this->assertSame('generated', $tag->fresh()->status);
    }

    public function test_a_code_can_be_assigned_once_it_was_sent_to_print(): void
    {
        $admin = $this->admin();
        $batch = app(QrBatchService::class)->createBatch(1, $admin->id);
        app(TagLifecycleService::class)->sendBatchToPrinting($batch, 'admin', $admin->id);
        $tag = $batch->tags()->first();
        $this->assertSame('printing', $tag->status);

        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user)->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/tags/activate', [
            'token' => $tag->public_token, 'vehicle_id' => $vehicle->uuid,
        ])->assertStatus(201);

        $this->assertSame('assigned', $tag->fresh()->status);
    }

    public function test_only_an_assigned_code_can_be_cancelled(): void
    {
        $tag = app(QrBatchService::class)->createBatch(1, null)->tags()->first();

        foreach (['generated', 'printing', 'unassigned'] as $status) {
            $tag->update(['status' => $status]);
            try {
                app(TagLifecycleService::class)->revoke($tag, 'x', 'admin', null);
                $this->fail("A {$status} code must not be cancellable.");
            } catch (TagActivationException) {
                // expected
            }
            $this->assertSame($status, $tag->fresh()->status);
        }

        $tag->update(['status' => 'assigned']);
        app(TagLifecycleService::class)->revoke($tag, 'x', 'admin', null);
        $this->assertSame('revoked', $tag->fresh()->status);
    }

    public function test_the_cancel_action_is_only_offered_for_assigned_codes(): void
    {
        $admin = $this->admin();
        $batch = app(QrBatchService::class)->createBatch(2, $admin->id);
        [$a, $b] = $batch->tags()->orderBy('id')->get()->all();
        $a->update(['status' => 'assigned', 'vehicle_id' => Vehicle::factory()->create()->id]);

        $html = $this->actingAs($admin, 'admin')->get('/admin/qr')->assertOk()->getContent();

        $this->assertStringContainsString(route('admin.tags.revoke', $a), $html);
        $this->assertStringNotContainsString(route('admin.tags.revoke', $b), $html);

        // The server refuses it anyway, with a message instead of an error page.
        $this->actingAs($admin, 'admin')->post(route('admin.tags.revoke', $b), ['reason' => 'x'])
            ->assertSessionHasErrors('reason');
        $this->assertSame('generated', $b->fresh()->status);
    }

    public function test_the_vehicle_list_counts_active_codes_and_names_the_make_in_brackets(): void
    {
        $admin = $this->admin();
        $vehicle = Vehicle::factory()->create(['nickname' => 'محمد فائز', 'make' => 'KIA', 'model' => 'Rio']);
        $tags = app(QrBatchService::class)->createBatch(2, $admin->id)->tags()->get();
        $tags[0]->update(['status' => 'assigned', 'vehicle_id' => $vehicle->id]);
        $tags[1]->update(['status' => 'revoked', 'vehicle_id' => $vehicle->id]);

        $this->actingAs($admin, 'admin')->get('/admin/vehicles')->assertOk()
            ->assertSee('محمد فائز')->assertSee('(KIA Rio)')->assertSee('(+1 ملغى/مستبدل)', false);
    }

    public function test_batch_notes_are_visible_in_the_list_and_on_the_batch(): void
    {
        $admin = $this->admin();
        $batch = app(QrBatchService::class)->createBatch(1, $admin->id);
        $batch->update(['notes' => 'مطبعة النور — أمر 4411']);

        $this->actingAs($admin, 'admin')->get('/admin/batches')->assertOk()->assertSee('مطبعة النور — أمر 4411');
        $this->actingAs($admin, 'admin')->get("/admin/batches/{$batch->id}")->assertOk()->assertSee('مطبعة النور — أمر 4411');
    }

    public function test_a_manual_grant_appears_in_the_approved_upgrade_history(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $plan = $this->paidPlan();

        app(SubscriptionManager::class)->grant($user, $plan, $admin->id);

        $this->assertDatabaseHas('plan_upgrade_requests', ['user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'approved']);
        $this->actingAs($admin, 'admin')->get('/admin/subscriptions/requests?status=approved')->assertOk()
            ->assertSee($user->display_name);
    }

    public function test_a_waiting_request_is_closed_when_the_plan_is_granted(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $plan = $this->paidPlan();
        $request = PlanUpgradeRequest::create(['user_id' => $user->id, 'plan_id' => $plan->id]);

        app(SubscriptionManager::class)->grant($user, $plan, $admin->id);

        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame(1, PlanUpgradeRequest::where('user_id', $user->id)->count());
    }

    public function test_the_display_name_falls_back_to_the_phone_number(): void
    {
        $user = User::factory()->create(['name' => null, 'phone' => '+9647700000000']);

        $this->assertSame('+9647700000000', $user->display_name);
    }
}
