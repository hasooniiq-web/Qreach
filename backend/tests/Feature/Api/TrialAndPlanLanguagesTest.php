<?php

namespace Tests\Feature\Api;

use App\Models\Admin;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\PlanEntitlements;
use App\Services\SubscriptionManager;
use App\Services\SubscriptionReminders;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The one-time free trial of Plus, and plan names/descriptions in three languages.
 */
class TrialAndPlanLanguagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);
        Cache::flush();
    }

    private function plan(string $slug): SubscriptionPlan
    {
        return SubscriptionPlan::where('slug', $slug)->firstOrFail();
    }

    private function userPlan(User $user): string
    {
        return app(PlanEntitlements::class)->planFor($user->fresh())->slug;
    }

    // ---- Trial -----------------------------------------------------------------

    public function test_a_new_customer_can_start_the_week_of_plus_once(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/subscription')
            ->assertOk()
            ->assertJsonPath('trial.available', true)
            ->assertJsonPath('trial.days', 7)
            ->assertJsonPath('trial.used', false);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/subscription/trial')
            ->assertOk()
            ->assertJsonPath('plan.slug', 'plus')
            ->assertJsonPath('subscription.status', 'trialing')
            ->assertJsonPath('trial.active', true)
            ->assertJsonPath('trial.available', false)
            ->assertJsonPath('trial.used', true)
            ->assertJsonPath('subscription.can_cancel_renewal', false);

        $this->assertSame('plus', $this->userPlan($user));
        $subscription = $user->subscriptions()->firstOrFail();
        $this->assertSame('trialing', $subscription->status);
        $this->assertEqualsWithDelta(7, now()->diffInDays($subscription->current_period_ends_at), 0.1);
        $this->assertNotNull($user->fresh()->plus_trial_used_at);
    }

    public function test_the_trial_cannot_be_taken_twice_even_after_it_ends(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/subscription/trial')->assertOk();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/subscription/trial')
            ->assertStatus(409)->assertJsonPath('error.code', 'TRIAL_USED'); // taken already, even while it still runs

        $this->travel(8)->days();
        $this->assertSame('basic', $this->userPlan($user));

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/subscription/trial')
            ->assertStatus(409)->assertJsonPath('error.code', 'TRIAL_USED');
        $this->assertSame(1, $user->subscriptions()->count());
    }

    public function test_a_trial_has_no_grace_week(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/subscription/trial')->assertOk();

        $this->travel(6)->days();
        $this->assertSame('plus', $this->userPlan($user));

        // One day past the end: a paid plan would still be in its grace week; a trial is over.
        $this->travel(2)->days();
        $this->assertSame('basic', $this->userPlan($user));
    }

    public function test_a_paying_customer_does_not_get_the_trial(): void
    {
        $user = User::factory()->create();
        app(SubscriptionManager::class)->grant($user, $this->plan('plus'), Admin::factory()->create()->id, 365);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/subscription')->assertJsonPath('trial.available', false);
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/subscription/trial')
            ->assertStatus(409)->assertJsonPath('error.code', 'TRIAL_NOT_NEEDED');
    }

    public function test_the_trial_can_be_switched_off_from_the_settings(): void
    {
        SystemSetting::set('plus_trial_days', 0);
        Cache::flush();
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/subscription')->assertJsonPath('trial.available', false);
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/subscription/trial')
            ->assertStatus(422)->assertJsonPath('error.code', 'TRIAL_UNAVAILABLE');
        $this->assertSame(0, $user->subscriptions()->count());
    }

    public function test_the_trial_length_follows_the_setting(): void
    {
        SystemSetting::set('plus_trial_days', 14);
        Cache::flush();
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/subscription/trial')->assertOk();

        $this->assertEqualsWithDelta(14, now()->diffInDays($user->subscriptions()->first()->current_period_ends_at), 0.1);
    }

    public function test_redeeming_a_voucher_during_the_trial_starts_a_full_paid_period(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/subscription/trial')->assertOk();

        app(SubscriptionManager::class)->grant($user, $this->plan('plus'), Admin::factory()->create()->id, 365);

        $subscription = $user->subscriptions()->firstOrFail();
        $this->assertSame(1, $user->subscriptions()->count(), 'The trial is converted, not duplicated.');
        $this->assertSame('active', $subscription->status);
        $this->assertEqualsWithDelta(365, now()->diffInDays($subscription->current_period_ends_at), 0.1);
        $this->assertTrue($subscription->isInGrace() === false);
    }

    public function test_a_trial_gets_one_ending_tomorrow_reminder_and_no_renewal_reminders(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/subscription/trial')->assertOk();

        // Day 3 of 7: nothing yet.
        $this->travel(3)->days();
        $this->assertSame([], app(SubscriptionReminders::class)->run());

        // Less than a day left: exactly one "ends tomorrow" notice, then never again.
        $this->travelTo(now()->addDays(3)->addHours(2));
        $this->assertSame(['trial_ending' => 1], app(SubscriptionReminders::class)->run());
        $this->assertSame([], app(SubscriptionReminders::class)->run());

        $note = $user->notifications()->latest()->first();
        $this->assertSame('trial_ending', $note->data['action_type']);
        $this->assertStringContainsString('غدًا', $note->data['title']);
    }

    public function test_starting_the_trial_twice_in_a_row_is_refused(): void
    {
        $user = User::factory()->create();
        $manager = app(SubscriptionManager::class);

        $manager->startTrial($user);

        $this->expectException(\DomainException::class);
        $manager->startTrial($user);
    }

    // ---- Plan languages --------------------------------------------------------

    public function test_the_api_returns_plan_text_in_the_requested_language(): void
    {
        $plus = $this->plan('plus');
        $plus->update([
            'name_ar' => 'بلس', 'name_en' => 'Plus', 'name_ckb' => 'پلەس',
            'description_ar' => 'وصف عربي', 'description_en' => 'English text', 'description_ckb' => 'دەقی کوردی',
        ]);
        $user = User::factory()->create();

        $get = fn (string $lang) => $this->actingAs($user, 'sanctum')
            ->withHeaders(['Accept-Language' => $lang])->getJson('/api/v1/subscription')
            ->json('plans');
        $plusOf = fn (array $plans) => collect($plans)->firstWhere('slug', 'plus');

        $this->assertSame('بلس', $plusOf($get('ar'))['name']);
        $this->assertSame('Plus', $plusOf($get('en'))['name']);
        $this->assertSame('English text', $plusOf($get('en'))['description']);
        $this->assertSame('پلەس', $plusOf($get('ckb'))['name']);
        $this->assertSame('دەقی کوردی', $plusOf($get('ckb'))['description']);
        $this->assertSame('Plus', $plusOf($get('en-US,en;q=0.9'))['name']);

        // No header (older app builds): Arabic, as before.
        // (the test client adds its own en-us default, so the header is blanked explicitly)
        $none = $this->actingAs($user, 'sanctum')->withHeaders(['Accept-Language' => ''])->getJson('/api/v1/subscription')->json('plans');
        $this->assertSame('بلس', $plusOf($none)['name']);
    }

    public function test_an_empty_language_falls_back_to_arabic(): void
    {
        $plus = $this->plan('plus');
        $plus->update(['name_ar' => 'بلس', 'name_ckb' => null, 'description_ar' => 'وصف', 'description_ckb' => '']);

        $this->assertSame('بلس', $plus->fresh()->nameIn('ckb'));
        $this->assertSame('وصف', $plus->fresh()->descriptionIn('ckb'));
        $this->assertSame('بلس', $plus->fresh()->nameIn(null));
    }

    public function test_the_dashboard_saves_the_text_in_all_three_languages(): void
    {
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);
        $plus = $this->plan('plus');

        $this->actingAs($admin, 'admin')->put('/admin/plans/'.$plus->id, [
            'name_ar' => 'بلس', 'name_en' => 'Plus', 'name_ckb' => 'پلەس',
            'description_ar' => 'ع', 'description_en' => 'E', 'description_ckb' => 'ک',
            'price_cents' => 25000, 'billing_period_days' => 365, 'is_active' => '1',
            'public_contacts' => ['phone', 'instagram'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $fresh = $plus->fresh();
        $this->assertSame('Plus', $fresh->name_en);
        $this->assertSame('پلەس', $fresh->name_ckb);
        $this->assertSame('E', $fresh->description_en);
        $this->assertSame('ک', $fresh->description_ckb);

        $this->actingAs($admin, 'admin')->get('/admin/plans')->assertOk()->assertSee('کوردی')->assertSee('English');
    }
}
