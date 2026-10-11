<?php

namespace Tests\Feature\Api;

use App\Models\ContactMethod;
use App\Models\PlanUpgradeRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Basic = messaging only; Pro = messaging + showing the owner's chosen
 * phone/Instagram/extra info. Entitlements are enforced server-side: the
 * API refuses Basic owners, and the public scan page re-checks the plan on
 * every render so a lapsed subscription (after its grace week) hides details.
 */
class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);
    }

    private function grantPro(User $user, ?\DateTimeInterface $endsAt = null, string $status = 'active'): Subscription
    {
        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => SubscriptionPlan::where('slug', 'plus')->value('id'),
            'status' => $status,
            'current_period_starts_at' => now()->subDay(),
            'current_period_ends_at' => $endsAt ?? now()->addYear(),
        ]);
    }

    private function bearer(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('t')->plainTextToken];
    }

    private function assignedTagFor(User $owner): Tag
    {
        $vehicle = Vehicle::factory()->for($owner)->create();

        return Tag::create([
            'public_token' => bin2hex(random_bytes(16)),
            'serial_number' => 'ST-Q-TEST-'.random_int(100000, 999999),
            'type' => 'qr', 'status' => 'assigned', 'vehicle_id' => $vehicle->id,
        ]);
    }

    public function test_a_user_with_no_subscription_is_on_basic_with_messaging_only(): void
    {
        $user = User::factory()->create();

        $this->getJson('/api/v1/subscription', $this->bearer($user))
            ->assertOk()
            ->assertJsonPath('plan.slug', 'basic')
            ->assertJsonPath('plan.is_free', true)
            ->assertJsonPath('plan.features.messaging', true)
            ->assertJsonPath('plan.features.public_contacts', ['phone'])
            ->assertJsonPath('plan.features.extra_contact_methods', false)
            ->assertJsonPath('source', 'default')
            ->assertJsonPath('pending_upgrade', false)
            ->assertJsonCount(2, 'plans');
    }

    public function test_an_active_pro_subscription_grants_pro_and_reports_price_and_end_date(): void
    {
        $user = User::factory()->create();
        $this->grantPro($user);

        $this->getJson('/api/v1/subscription', $this->bearer($user))
            ->assertOk()
            ->assertJsonPath('plan.slug', 'plus')
            ->assertJsonPath('plan.price', 25000)
            ->assertJsonPath('plan.currency', 'IQD')
            ->assertJsonPath('source', 'subscription')
            ->assertJsonPath('subscription.status', 'active');
    }

    public function test_an_expired_pro_subscription_falls_back_to_basic(): void
    {
        $user = User::factory()->create();
        $this->grantPro($user, now()->subDays(10));

        $this->getJson('/api/v1/subscription', $this->bearer($user))
            ->assertJsonPath('plan.slug', 'basic')
            ->assertJsonPath('source', 'default')
            ->assertJsonPath('subscription.status', 'expired');
    }

    public function test_upgrade_request_is_created_once_and_duplicates_are_rejected(): void
    {
        $user = User::factory()->create();
        $headers = $this->bearer($user);

        $this->postJson('/api/v1/subscription/upgrade-requests', ['plan' => 'plus'], $headers)->assertCreated();
        $this->assertDatabaseHas('plan_upgrade_requests', ['user_id' => $user->id, 'status' => 'pending']);

        $this->postJson('/api/v1/subscription/upgrade-requests', ['plan' => 'plus'], $headers)
            ->assertStatus(409)->assertJsonPath('error.code', 'UPGRADE_PENDING');
        $this->assertSame(1, PlanUpgradeRequest::count());
    }

    public function test_a_pro_user_cannot_request_an_upgrade_to_pro_and_basic_is_not_an_upgrade(): void
    {
        $user = User::factory()->create();
        $this->grantPro($user);
        $headers = $this->bearer($user);

        $this->postJson('/api/v1/subscription/upgrade-requests', ['plan' => 'plus'], $headers)
            ->assertStatus(409)->assertJsonPath('error.code', 'ALREADY_ON_PLAN');
        $this->postJson('/api/v1/subscription/upgrade-requests', ['plan' => 'basic'], $headers)
            ->assertStatus(422)->assertJsonPath('error.code', 'PLAN_NOT_AVAILABLE');
    }

    public function test_scan_page_shows_direct_contact_for_a_pro_owner_who_chose_to_share(): void
    {
        $owner = User::factory()->create();
        $this->grantPro($owner);
        ContactMethod::create(['user_id' => $owner->id, 'type' => 'phone', 'value' => '+9647701234567', 'privacy_state' => 'public']);
        ContactMethod::create(['user_id' => $owner->id, 'type' => 'instagram', 'value' => 'my.car', 'privacy_state' => 'public']);
        ContactMethod::create(['user_id' => $owner->id, 'type' => 'email', 'value' => 'private@example.com', 'privacy_state' => 'private']);
        $tag = $this->assignedTagFor($owner);

        $this->get("/t/{$tag->public_token}")
            ->assertOk()
            ->assertSee('href="tel:+9647701234567"', false)
            ->assertSee('https://instagram.com/my.car', false)
            ->assertDontSee('private@example.com');
    }

    public function test_basic_owner_shows_only_the_phone_and_extra_platforms_stay_hidden_even_if_stored(): void
    {
        $owner = User::factory()->create();
        ContactMethod::create(['user_id' => $owner->id, 'type' => 'phone', 'value' => '+9647701234567', 'privacy_state' => 'public']);
        ContactMethod::create(['user_id' => $owner->id, 'type' => 'instagram', 'value' => 'my.car', 'privacy_state' => 'public']);
        $tag = $this->assignedTagFor($owner);

        $this->get("/t/{$tag->public_token}")
            ->assertOk()
            ->assertSee('9647701234567')
            ->assertDontSee('my.car');
    }

    public function test_extra_platforms_disappear_once_the_plus_subscription_lapses(): void
    {
        $owner = User::factory()->create();
        $this->grantPro($owner, now()->subDays(10));
        ContactMethod::create(['user_id' => $owner->id, 'type' => 'instagram', 'value' => 'my.car', 'privacy_state' => 'public']);
        $tag = $this->assignedTagFor($owner);

        $this->get("/t/{$tag->public_token}")->assertOk()->assertDontSee('my.car');
    }
}
