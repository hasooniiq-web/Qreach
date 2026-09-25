<?php

namespace Tests\Feature\Api;

use App\Models\ContactMethod;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The owner stores details in their profile and chooses, per detail, whether
 * visitors see it. Default: nothing is shown — visitors can only message.
 * Showing anything needs Pro, enforced on write and on every scan-page render.
 */
class ProfileVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);
    }

    private function pro(User $user, bool $active = true): void
    {
        Subscription::create([
            'user_id' => $user->id, 'plan_id' => SubscriptionPlan::where('slug', 'pro')->value('id'), 'status' => 'active',
            'current_period_starts_at' => now()->subDay(), 'current_period_ends_at' => $active ? now()->addYear() : now()->subMinute(),
        ]);
    }

    private function bearer(User $u): array
    {
        return ['Authorization' => 'Bearer '.$u->createToken('t')->plainTextToken];
    }

    private function scanPageFor(User $owner)
    {
        $vehicle = Vehicle::factory()->for($owner)->create();
        $tag = Tag::create([
            'public_token' => bin2hex(random_bytes(16)), 'serial_number' => 'ST-Q-PV-'.random_int(1000, 9999),
            'type' => 'qr', 'status' => 'assigned', 'vehicle_id' => $vehicle->id,
        ]);

        return $this->get("/t/{$tag->public_token}");
    }

    public function test_a_new_profile_shows_nothing_by_default(): void
    {
        $user = User::factory()->create(['name' => 'حسين']);

        $this->getJson('/api/v1/profile', $this->bearer($user))->assertOk()
            ->assertJsonPath('name', 'حسين')
            ->assertJsonPath('contact.phone.visible', false)
            ->assertJsonPath('contact.phone.can_show', false)
            ->assertJsonPath('contact.instagram.value', null);
    }

    public function test_basic_owner_can_store_details_privately_but_not_show_them(): void
    {
        $user = User::factory()->create();
        $headers = $this->bearer($user);

        $this->putJson('/api/v1/profile/contact', ['instagram' => ['value' => '@my.car', 'visible' => false]], $headers)
            ->assertOk()->assertJsonPath('contact.instagram.value', 'my.car')->assertJsonPath('contact.instagram.visible', false);
        $this->assertDatabaseHas('contact_methods', ['user_id' => $user->id, 'type' => 'instagram', 'privacy_state' => 'private']);

        $this->putJson('/api/v1/profile/contact', ['instagram' => ['visible' => true]], $headers)
            ->assertStatus(403)->assertJsonPath('error.code', 'PLAN_REQUIRED');
        $this->assertDatabaseHas('contact_methods', ['user_id' => $user->id, 'type' => 'instagram', 'privacy_state' => 'private']);
    }

    public function test_a_refused_change_applies_nothing(): void
    {
        $user = User::factory()->create();

        $this->putJson('/api/v1/profile/contact', [
            'extra' => ['value' => 'مخزَّن', 'visible' => false],
            'phone' => ['value' => '+9647701234567', 'visible' => true],
        ], $this->bearer($user))->assertStatus(403);

        $this->assertSame(0, ContactMethod::count());
    }

    public function test_pro_owner_details_stay_hidden_until_switched_on(): void
    {
        $owner = User::factory()->create();
        $this->pro($owner);
        $headers = $this->bearer($owner);

        $this->putJson('/api/v1/profile/contact', ['phone' => ['value' => '+9647701234567', 'visible' => false]], $headers)->assertOk();
        $this->scanPageFor($owner)->assertOk()->assertDontSee('9647701234567')->assertDontSee('تواصل مباشر مع المالك');

        $this->putJson('/api/v1/profile/contact', ['phone' => ['visible' => true]], $headers)
            ->assertOk()->assertJsonPath('contact.phone.visible', true)->assertJsonPath('contact.phone.value', '+9647701234567');
        $this->scanPageFor($owner)->assertOk()->assertSee('href="tel:+9647701234567"', false);
    }

    public function test_only_the_switched_on_fields_are_shown(): void
    {
        $owner = User::factory()->create();
        $this->pro($owner);

        $this->putJson('/api/v1/profile/contact', [
            'phone' => ['value' => '+9647701234567', 'visible' => true],
            'instagram' => ['value' => 'secret.handle', 'visible' => false],
        ], $this->bearer($owner))->assertOk();

        $this->scanPageFor($owner)->assertOk()->assertSee('9647701234567')->assertDontSee('secret.handle');
    }

    public function test_switched_on_details_disappear_when_the_subscription_lapses(): void
    {
        $owner = User::factory()->create();
        $this->pro($owner, active: false);
        ContactMethod::create(['user_id' => $owner->id, 'type' => 'phone', 'value' => '+9647701234567', 'privacy_state' => 'public']);

        $this->scanPageFor($owner)->assertOk()->assertDontSee('9647701234567');
    }

    public function test_clearing_a_value_removes_it_and_bad_input_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->pro($user);
        $headers = $this->bearer($user);

        $this->putJson('/api/v1/profile/contact', ['phone' => ['value' => '+9647701234567']], $headers)->assertOk();
        $this->putJson('/api/v1/profile/contact', ['phone' => ['value' => '']], $headers)->assertOk()->assertJsonPath('contact.phone.value', null);
        $this->assertSame(0, ContactMethod::count());

        $this->putJson('/api/v1/profile/contact', ['instagram' => ['value' => 'not a handle!']], $headers)->assertStatus(422);
        $this->putJson('/api/v1/profile/contact', ['phone' => ['value' => '12']], $headers)->assertStatus(422);
        $this->putJson('/api/v1/profile/contact', ['extra' => ['value' => '<script>x</script>']], $headers)->assertStatus(422);
    }

    public function test_name_can_be_updated_and_is_validated(): void
    {
        $user = User::factory()->create(['name' => 'قديم']);
        $headers = $this->bearer($user);

        $this->putJson('/api/v1/profile', ['name' => '  حسين علي  '], $headers)->assertOk()->assertJsonPath('name', 'حسين علي');
        $this->assertSame('حسين علي', $user->fresh()->name);

        $this->putJson('/api/v1/profile', ['name' => 'x'], $headers)->assertStatus(422);
        $this->putJson('/api/v1/profile', ['name' => '<b>x</b>'], $headers)->assertStatus(422);
    }
}
