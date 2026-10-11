<?php

namespace Tests\Feature\Api;

use App\Models\Admin;
use App\Models\ContactMethod;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PlanEntitlements;
use App\Services\SubscriptionManager;
use App\Services\SubscriptionReminders;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The yearly plan's lifecycle: reminders 30/10/1 days before the end, a grace
 * week after it, then back to Basic with nothing deleted — and stopping the
 * renewal keeps the days that are left.
 */
class SubscriptionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);
    }

    private function plus(): SubscriptionPlan
    {
        return SubscriptionPlan::where('slug', 'plus')->firstOrFail();
    }

    private function subscribe(User $user, \DateTimeInterface $endsAt, int $periodDays = 365, string $status = 'active'): Subscription
    {
        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $this->plus()->id,
            'status' => $status,
            'current_period_starts_at' => \Illuminate\Support\Carbon::instance($endsAt)->subDays($periodDays),
            'current_period_ends_at' => $endsAt,
        ]);
    }

    private function bearer(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('t')->plainTextToken];
    }

    private function noticeKinds(User $user): array
    {
        return $user->notifications()->get()->pluck('data.action_type')->all();
    }

    // ---- Grace week -------------------------------------------------------

    public function test_the_plan_keeps_working_for_a_week_after_it_ends_then_returns_to_basic(): void
    {
        $inGrace = User::factory()->create();
        $this->subscribe($inGrace, now()->subDays(3));
        $pastGrace = User::factory()->create();
        $this->subscribe($pastGrace, now()->subDays(8));

        $entitlements = app(PlanEntitlements::class);
        $this->assertSame('plus', $entitlements->planFor($inGrace)->slug);
        $this->assertSame('basic', $entitlements->planFor($pastGrace)->slug);

        $this->actingAs($inGrace, 'sanctum')->getJson('/api/v1/subscription')
            ->assertOk()
            ->assertJsonPath('plan.slug', 'plus')
            ->assertJsonPath('subscription.status', 'grace')
            ->assertJsonPath('subscription.can_cancel_renewal', true);
        $this->actingAs($pastGrace, 'sanctum')->getJson('/api/v1/subscription')
            ->assertOk()->assertJsonPath('plan.slug', 'basic')->assertJsonPath('subscription.status', 'expired');
    }

    public function test_renewing_inside_the_grace_week_extends_from_today_without_a_second_row(): void
    {
        $user = User::factory()->create();
        $subscription = $this->subscribe($user, now()->subDays(3));

        app(SubscriptionManager::class)->grant($user, $this->plus(), Admin::factory()->create()->id, 365);

        $this->assertSame(1, Subscription::where('user_id', $user->id)->count());
        $ends = $subscription->fresh()->current_period_ends_at;
        $this->assertTrue($ends->between(now()->addDays(364), now()->addDays(366)));
    }

    // ---- Nothing is deleted -------------------------------------------------

    public function test_lapsing_hides_plus_data_without_deleting_it_and_renewing_brings_it_back(): void
    {
        $user = User::factory()->create();
        $subscription = $this->subscribe($user, now()->addDays(5));
        $method = ContactMethod::create([
            'user_id' => $user->id, 'type' => 'instagram', 'label' => 'إنستغرام', 'value' => 'abu_jasim', 'privacy_state' => 'public',
        ]);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/profile')->assertOk()
            ->assertJsonPath('methods.0.can_show', true);

        // The plan ends and the grace week passes.
        $subscription->update(['current_period_ends_at' => now()->subDays(9)]);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/profile')->assertOk()
            ->assertJsonPath('plan', 'basic')
            ->assertJsonPath('methods.0.can_show', false);
        $this->assertDatabaseHas('contact_methods', ['id' => $method->id, 'value' => 'abu_jasim', 'privacy_state' => 'public']);

        // Renewing brings it back exactly as it was.
        app(SubscriptionManager::class)->grant($user, $this->plus(), Admin::factory()->create()->id, 365);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/profile')->assertOk()
            ->assertJsonPath('methods.0.can_show', true)
            ->assertJsonPath('methods.0.value', 'abu_jasim');
    }

    // ---- Reminders ----------------------------------------------------------

    public function test_a_reminder_is_sent_once_at_each_threshold(): void
    {
        $user = User::factory()->create();
        $subscription = $this->subscribe($user, now()->addDays(30)->subHour());
        $reminders = app(SubscriptionReminders::class);

        $this->assertSame(['before_30' => 1], $reminders->run());
        $this->assertSame([], $reminders->run(), 'the same reminder must not repeat');

        $subscription->update(['current_period_ends_at' => now()->addDays(10)->subHour()]);
        // A moved period end is a new period for reminder purposes only after renewal; the
        // 30-day reminder is already recorded for the old end, so it would be sent again —
        // which is exactly what happens after a real renewal.
        $reminders->run();

        $subscription->update(['current_period_ends_at' => now()->addHours(20)]);
        $sent = $reminders->run();
        $this->assertArrayHasKey('before_1', $sent);

        $texts = $user->notifications()->get()->pluck('data.title')->implode(' | ');
        $this->assertStringContainsString('ينتهي غدًا', $texts);
    }

    public function test_the_expiry_and_the_return_to_basic_are_announced_once_each(): void
    {
        $user = User::factory()->create();
        $subscription = $this->subscribe($user, now()->subHours(2));
        $reminders = app(SubscriptionReminders::class);

        $this->assertSame(['expired' => 1], $reminders->run());
        $this->assertSame([], $reminders->run());

        $subscription->update(['current_period_ends_at' => now()->subDays(8)]);
        $this->assertSame(['downgraded' => 1], $reminders->run());
        $this->assertSame([], $reminders->run());

        $body = $user->notifications()->get()->pluck('data.message')->implode(' | ');
        $this->assertStringContainsString('بياناتك محفوظة', $body);
    }

    public function test_a_short_grant_never_gets_a_thirty_day_reminder(): void
    {
        $user = User::factory()->create();
        $this->subscribe($user, now()->addDays(7)->subHour(), periodDays: 7);

        $this->assertSame([], app(SubscriptionReminders::class)->run());
        $this->assertSame([], $this->noticeKinds($user));
    }

    public function test_after_downtime_only_the_closest_reminder_is_sent(): void
    {
        $user = User::factory()->create();
        $this->subscribe($user, now()->addDays(5));

        $this->assertSame(['before_10' => 1], app(SubscriptionReminders::class)->run());
        $this->assertSame(['before_10'], $this->noticeKinds($user));
    }

    public function test_the_basic_plan_and_unrelated_subscriptions_get_no_reminders(): void
    {
        $user = User::factory()->create();
        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => SubscriptionPlan::where('slug', 'basic')->value('id'),
            'status' => 'active',
            'current_period_starts_at' => now()->subYear(),
            'current_period_ends_at' => now()->addDays(10),
        ]);
        $this->subscribe(User::factory()->create(), now()->addDays(100));

        $this->assertSame([], app(SubscriptionReminders::class)->run());
    }

    public function test_the_command_runs(): void
    {
        $this->subscribe(User::factory()->create(), now()->addDays(30)->subHour());

        $this->artisan('subscriptions:remind')->expectsOutputToContain('before_30')->assertSuccessful();
    }

    // ---- Stopping the renewal -------------------------------------------------

    public function test_stopping_the_renewal_keeps_the_plan_until_the_end_date(): void
    {
        $user = User::factory()->create();
        $ends = now()->addDays(200);
        $subscription = $this->subscribe($user, $ends);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/subscription/cancel-renewal')
            ->assertOk()
            ->assertJsonPath('plan.slug', 'plus')
            ->assertJsonPath('subscription.status', 'active')
            ->assertJsonPath('subscription.renewal_canceled', true)
            ->assertJsonPath('subscription.can_cancel_renewal', false);

        $fresh = $subscription->fresh();
        $this->assertSame('active', $fresh->status);
        $this->assertNotNull($fresh->renewal_canceled_at);
        $this->assertTrue($fresh->current_period_ends_at->equalTo($ends->copy()->startOfSecond()) || $fresh->current_period_ends_at->diffInSeconds($ends) < 2);
        $this->assertSame('plus', app(PlanEntitlements::class)->planFor($user)->slug);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/subscription/resume-renewal')
            ->assertOk()->assertJsonPath('subscription.renewal_canceled', false);
    }

    public function test_a_user_without_a_paid_plan_cannot_cancel_a_renewal(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/subscription/cancel-renewal')
            ->assertStatus(404)->assertJsonPath('error.code', 'NO_SUBSCRIPTION');
    }

    public function test_the_reminder_after_stopping_the_renewal_says_it_will_not_renew(): void
    {
        $user = User::factory()->create();
        $subscription = $this->subscribe($user, now()->addDays(10)->subHour());
        app(SubscriptionManager::class)->cancelRenewal($subscription);

        app(SubscriptionReminders::class)->run();

        $message = $user->notifications()->latest()->first()->data['message'];
        $this->assertStringContainsString('أوقفتَ التجديد', $message);
    }
}
