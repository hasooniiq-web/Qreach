<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\PlanUpgradeRequest;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PlanEntitlements;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Subscription management is permission-gated: viewing needs
 * subscriptions.view, changing anything needs subscriptions.manage (super
 * admin only by default). Every change must also leave an audit trail.
 */
class SubscriptionAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesAndPermissionsSeeder::class, SubscriptionPlanSeeder::class]);
    }

    private function adminWithRole(string $slug): Admin
    {
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', $slug)->value('id')]);

        return $admin;
    }

    private function pro(): SubscriptionPlan
    {
        return SubscriptionPlan::where('slug', 'plus')->firstOrFail();
    }

    public function test_super_admin_sees_subscribers_with_plan_price_and_status(): void
    {
        $super = $this->adminWithRole('super_admin');
        $user = User::factory()->create(['name' => 'المشترك التجريبي']);
        Subscription::create([
            'user_id' => $user->id, 'plan_id' => $this->pro()->id, 'status' => 'active',
            'current_period_starts_at' => now(), 'current_period_ends_at' => now()->addYear(),
        ]);

        $this->actingAs($super, 'admin')->get('/admin/subscriptions')
            ->assertOk()
            ->assertSee('المشترك التجريبي')
            ->assertSee('بلس')
            ->assertSee('25,000')
            ->assertSee('فعّال');
    }

    public function test_an_active_row_past_its_end_date_is_shown_as_expired(): void
    {
        $super = $this->adminWithRole('super_admin');
        $user = User::factory()->create(['name' => 'منتهي الاشتراك']);
        Subscription::create([
            'user_id' => $user->id, 'plan_id' => $this->pro()->id, 'status' => 'active',
            'current_period_starts_at' => now()->subYear(), 'current_period_ends_at' => now()->subDay(),
        ]);

        $this->actingAs($super, 'admin')->get('/admin/subscriptions?status=expired')
            ->assertOk()->assertSee('منتهي الاشتراك')->assertSee('منتهي');
    }

    public function test_support_admin_can_view_but_not_change_subscriptions(): void
    {
        $support = $this->adminWithRole('support_admin');
        $user = User::factory()->create();

        $this->actingAs($support, 'admin')->get('/admin/subscriptions')->assertOk();
        $this->actingAs($support, 'admin')->get('/admin/plans')->assertOk();

        $this->actingAs($support, 'admin')->post("/admin/users/{$user->uuid}/subscription", ['plan' => 'plus'])->assertForbidden();
        $this->assertSame(0, Subscription::count());

        $this->actingAs($support, 'admin')->put("/admin/plans/{$this->pro()->id}", [
            'name_ar' => 'x', 'price_cents' => 1, 'billing_period_days' => 1,
        ])->assertForbidden();
    }

    public function test_an_admin_without_subscription_permissions_is_denied(): void
    {
        $ops = $this->adminWithRole('operations_admin');

        $this->actingAs($ops, 'admin')->get('/admin/subscriptions')->assertForbidden();
        $this->actingAs($ops, 'admin')->get('/admin/subscriptions/requests')->assertForbidden();
        $this->actingAs($ops, 'admin')->get('/admin/plans')->assertForbidden();
    }

    public function test_granting_pro_creates_an_active_subscription_and_an_audit_entry(): void
    {
        $super = $this->adminWithRole('super_admin');
        $user = User::factory()->create();

        $this->actingAs($super, 'admin')
            ->post("/admin/users/{$user->uuid}/subscription", ['plan' => 'plus'])
            ->assertRedirect();

        $sub = Subscription::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('active', $sub->status);
        $this->assertTrue($sub->current_period_ends_at->isAfter(now()->addDays(360)));
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.subscription_granted', 'resource_id' => $sub->id]);
    }

    public function test_granting_again_extends_the_current_period_instead_of_duplicating(): void
    {
        $super = $this->adminWithRole('super_admin');
        $user = User::factory()->create();

        $this->actingAs($super, 'admin')->post("/admin/users/{$user->uuid}/subscription", ['plan' => 'plus', 'days' => 30]);
        $end = Subscription::firstOrFail()->current_period_ends_at->copy();
        $this->actingAs($super, 'admin')->post("/admin/users/{$user->uuid}/subscription", ['plan' => 'plus', 'days' => 30]);

        $this->assertSame(1, Subscription::count());
        $this->assertEquals($end->copy()->addDays(30)->toDateString(), Subscription::firstOrFail()->current_period_ends_at->toDateString());
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.subscription_extended']);
    }

    public function test_the_free_basic_plan_cannot_be_granted(): void
    {
        $super = $this->adminWithRole('super_admin');
        $user = User::factory()->create();

        $this->actingAs($super, 'admin')
            ->post("/admin/users/{$user->uuid}/subscription", ['plan' => 'basic'])
            ->assertSessionHasErrors('plan');
        $this->assertSame(0, Subscription::count());
    }

    public function test_canceling_returns_the_user_to_basic(): void
    {
        $super = $this->adminWithRole('super_admin');
        $user = User::factory()->create();
        $sub = Subscription::create([
            'user_id' => $user->id, 'plan_id' => $this->pro()->id, 'status' => 'active',
            'current_period_starts_at' => now(), 'current_period_ends_at' => now()->addYear(),
        ]);

        $this->actingAs($super, 'admin')->post("/admin/subscriptions/{$sub->id}/cancel")->assertRedirect();

        $this->assertSame('canceled', $sub->fresh()->status);
        $this->assertSame('basic', app(PlanEntitlements::class)->planFor($user)->slug);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.subscription_canceled']);
    }

    public function test_approving_an_upgrade_request_grants_pro_and_marks_it_approved(): void
    {
        $super = $this->adminWithRole('super_admin');
        $user = User::factory()->create();
        $request = PlanUpgradeRequest::create(['user_id' => $user->id, 'plan_id' => $this->pro()->id]);

        $this->actingAs($super, 'admin')->post("/admin/subscriptions/requests/{$request->id}/approve")->assertRedirect();

        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame($super->id, $request->fresh()->handled_by);
        $this->assertSame('plus', app(PlanEntitlements::class)->planFor($user)->slug);

        // A handled request can't be processed twice.
        $this->actingAs($super, 'admin')->post("/admin/subscriptions/requests/{$request->id}/approve")->assertStatus(409);
        $this->assertSame(1, Subscription::count());
    }

    public function test_rejecting_an_upgrade_request_leaves_the_user_on_basic(): void
    {
        $super = $this->adminWithRole('super_admin');
        $user = User::factory()->create();
        $request = PlanUpgradeRequest::create(['user_id' => $user->id, 'plan_id' => $this->pro()->id]);

        $this->actingAs($super, 'admin')->post("/admin/subscriptions/requests/{$request->id}/reject")->assertRedirect();

        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertSame(0, Subscription::count());
    }

    public function test_plan_edit_changes_price_and_permissions_but_basic_stays_free(): void
    {
        $super = $this->adminWithRole('super_admin');
        $pro = $this->pro();
        $basic = SubscriptionPlan::where('slug', 'basic')->firstOrFail();

        $this->actingAs($super, 'admin')->put("/admin/plans/{$pro->id}", [
            'name_ar' => 'بلس', 'price_cents' => 40000, 'billing_period_days' => 365, 'is_active' => 1,
            'public_contacts' => ['phone', 'instagram'],
        ])->assertRedirect();

        $this->assertSame(40000, $pro->fresh()->price_cents);
        $this->assertSame(['phone', 'instagram'], $pro->fresh()->publicContactTypes());
        $this->assertFalse($pro->fresh()->allowsPublicContact('website'));

        // Trying to charge for Basic or deactivate it is ignored server-side.
        $this->actingAs($super, 'admin')->put("/admin/plans/{$basic->id}", [
            'name_ar' => 'الأساسية', 'price_cents' => 9999, 'billing_period_days' => 365,
        ])->assertRedirect();

        $this->assertSame(0, $basic->fresh()->price_cents);
        $this->assertTrue($basic->fresh()->is_active);
    }

    public function test_user_detail_and_list_show_the_plan(): void
    {
        $super = $this->adminWithRole('super_admin');
        $user = User::factory()->create();
        Subscription::create([
            'user_id' => $user->id, 'plan_id' => $this->pro()->id, 'status' => 'active',
            'current_period_starts_at' => now(), 'current_period_ends_at' => now()->addYear(),
        ]);

        $this->actingAs($super, 'admin')->get("/admin/users/{$user->uuid}")->assertOk()->assertSee('الاشتراك')->assertSee('بلس');
        $this->actingAs($super, 'admin')->get('/admin/users')->assertOk()->assertSee('بلس');
    }
}
