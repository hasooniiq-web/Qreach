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
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Visitors see nothing by default. The owner switches items on one by one.
 * Basic: name, phone and one emergency contact. Plus: also extra platforms.
 * The Plus rule is enforced by the API (403 PLAN_REQUIRED) and again on every
 * scan-page render — never only by hiding buttons in the app.
 */
class ProfileVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);
    }

    private function plus(User $user, bool $active = true): void
    {
        Subscription::create([
            'user_id' => $user->id, 'plan_id' => SubscriptionPlan::where('slug', 'plus')->value('id'), 'status' => 'active',
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

    public function test_a_new_profile_shows_nothing_by_default_not_even_the_name(): void
    {
        $owner = User::factory()->create(['name' => 'حسين السري', 'phone' => '+9647709998877']);

        $this->getJson('/api/v1/profile', $this->bearer($owner))->assertOk()
            ->assertJsonPath('show_name', false)
            ->assertJsonPath('contact.phone.visible', false)
            ->assertJsonPath('can_add_methods', false)
            ->assertJsonPath('emergency', null);

        $this->scanPageFor($owner)->assertOk()
            ->assertDontSee('حسين السري')->assertDontSee('9709998877')->assertDontSee('تواصل مباشر');
    }

    public function test_basic_owner_can_show_name_phone_and_emergency_contact(): void
    {
        $owner = User::factory()->create(['name' => 'حسين علي']);
        $headers = $this->bearer($owner);

        $this->putJson('/api/v1/profile/visibility', ['show_name' => true], $headers)->assertOk()->assertJsonPath('show_name', true);
        $this->putJson('/api/v1/profile/contact', ['phone' => ['value' => '+9647701234567', 'visible' => true]], $headers)
            ->assertOk()->assertJsonPath('contact.phone.visible', true);
        $this->putJson('/api/v1/profile/emergency', [
            'name' => 'الوالد', 'relationship' => 'أب', 'phone' => '+9647800000001', 'visible' => true,
        ], $headers)->assertOk()->assertJsonPath('emergency.visible', true);

        $this->scanPageFor($owner)->assertOk()
            ->assertSee('حسين علي')->assertSee('9647701234567')->assertSee('الوالد')->assertSee('جهة اتصال الطوارئ');
    }

    public function test_an_emergency_contact_is_hidden_until_switched_on(): void
    {
        $owner = User::factory()->create();
        $this->putJson('/api/v1/profile/emergency', ['name' => 'الأم', 'phone' => '+9647800000002', 'visible' => false], $this->bearer($owner))->assertOk();

        $this->scanPageFor($owner)->assertOk()->assertDontSee('الأم')->assertDontSee('9647800000002');
    }

    public function test_basic_owner_cannot_add_extra_methods_the_server_refuses(): void
    {
        $owner = User::factory()->create();

        $this->postJson('/api/v1/profile/methods', ['type' => 'instagram', 'value' => 'my.car', 'visible' => false], $this->bearer($owner))
            ->assertStatus(403)->assertJsonPath('error.code', 'PLAN_REQUIRED');

        $this->assertSame(0, ContactMethod::count());
    }

    public function test_plus_owner_adds_many_platforms_and_only_switched_on_ones_are_shown(): void
    {
        $owner = User::factory()->create();
        $this->plus($owner);
        $headers = $this->bearer($owner);

        $this->postJson('/api/v1/profile/methods', ['type' => 'instagram', 'value' => '@shown.one', 'visible' => true], $headers)->assertOk();
        $this->postJson('/api/v1/profile/methods', ['type' => 'snapchat', 'value' => 'hidden_one', 'visible' => false], $headers)->assertOk();
        $this->postJson('/api/v1/profile/methods', ['type' => 'whatsapp', 'value' => '+9647701112233', 'visible' => true], $headers)->assertOk();

        $this->scanPageFor($owner)->assertOk()
            ->assertSee('https://instagram.com/shown.one', false)
            ->assertSee('https://wa.me/9647701112233', false)
            ->assertDontSee('hidden_one');
    }

    public function test_duplicate_platform_and_invalid_values_are_rejected(): void
    {
        $owner = User::factory()->create();
        $this->plus($owner);
        $headers = $this->bearer($owner);

        $this->postJson('/api/v1/profile/methods', ['type' => 'tiktok', 'value' => 'my.tok'], $headers)->assertOk();
        $this->postJson('/api/v1/profile/methods', ['type' => 'tiktok', 'value' => 'other.tok'], $headers)->assertStatus(422);
        $this->postJson('/api/v1/profile/methods', ['type' => 'instagram', 'value' => 'not a handle!'], $headers)->assertStatus(422);
        $this->postJson('/api/v1/profile/methods', ['type' => 'website', 'value' => 'http://insecure.example'], $headers)->assertStatus(422);
        $this->postJson('/api/v1/profile/methods', ['type' => 'nonsense', 'value' => 'x'], $headers)->assertStatus(422);
        $this->postJson('/api/v1/profile/methods', ['type' => 'other', 'value' => '<script>x</script>', 'label' => 'x'], $headers)->assertStatus(422);
    }

    public function test_extra_platforms_are_hidden_again_when_plus_lapses_but_can_still_be_removed(): void
    {
        $owner = User::factory()->create();
        $this->plus($owner, active: false);
        $method = ContactMethod::create(['user_id' => $owner->id, 'type' => 'instagram', 'value' => 'my.car', 'privacy_state' => 'public']);

        $this->scanPageFor($owner)->assertOk()->assertDontSee('my.car');

        $headers = $this->bearer($owner);
        $this->putJson("/api/v1/profile/methods/{$method->id}", ['visible' => true], $headers)->assertStatus(403);
        $this->deleteJson("/api/v1/profile/methods/{$method->id}", [], $headers)->assertOk();
        $this->assertSame(0, ContactMethod::count());
    }

    public function test_a_customer_cannot_touch_someone_elses_method(): void
    {
        $owner = User::factory()->create();
        $this->plus($owner);
        $method = ContactMethod::create(['user_id' => $owner->id, 'type' => 'instagram', 'value' => 'my.car', 'privacy_state' => 'private']);

        $stranger = User::factory()->create();
        $this->plus($stranger);

        $this->putJson("/api/v1/profile/methods/{$method->id}", ['visible' => true], $this->bearer($stranger))->assertNotFound();
        $this->deleteJson("/api/v1/profile/methods/{$method->id}", [], $this->bearer($stranger))->assertNotFound();
        $this->assertSame('private', $method->fresh()->privacy_state);
    }

    public function test_clearing_the_phone_removes_it_and_bad_numbers_are_rejected(): void
    {
        $user = User::factory()->create();
        $headers = $this->bearer($user);

        $this->putJson('/api/v1/profile/contact', ['phone' => ['value' => '+9647701234567']], $headers)->assertOk();
        $this->putJson('/api/v1/profile/contact', ['phone' => ['value' => '']], $headers)->assertOk()->assertJsonPath('contact.phone.value', null);
        $this->assertSame(0, ContactMethod::count());

        $this->putJson('/api/v1/profile/contact', ['phone' => ['value' => '12']], $headers)->assertStatus(422);
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

    public function test_avatar_and_cover_are_hidden_by_default_and_shown_only_when_switched_on(): void
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->for($owner)->create();
        $tag = Tag::create([
            'public_token' => bin2hex(random_bytes(16)), 'serial_number' => 'ST-Q-AV-'.random_int(1000, 9999),
            'type' => 'qr', 'status' => 'assigned', 'vehicle_id' => $vehicle->id,
        ]);
        $headers = $this->bearer($owner);

        $this->postJson('/api/v1/profile/avatar', ['avatar' => UploadedFile::fake()->image('avatar.jpg', 300, 300)], $headers)
            ->assertOk()->assertJsonPath('has_avatar', true);
        $this->postJson('/api/v1/profile/cover', ['cover' => UploadedFile::fake()->image('cover.jpg', 1200, 400)], $headers)
            ->assertOk()->assertJsonPath('has_cover', true);

        // Uploaded, but not switched on yet: the public scan page serves neither.
        $this->get("/t/{$tag->public_token}/avatar")->assertNotFound();
        $this->get("/t/{$tag->public_token}/cover")->assertNotFound();

        $this->putJson('/api/v1/profile/visibility', ['show_avatar' => true, 'show_cover' => true], $headers)
            ->assertOk()->assertJsonPath('show_avatar', true)->assertJsonPath('show_cover', true);

        $this->get("/t/{$tag->public_token}/avatar")->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->get("/t/{$tag->public_token}/cover")->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_updating_visibility_with_no_fields_is_rejected(): void
    {
        $owner = User::factory()->create();
        $this->putJson('/api/v1/profile/visibility', [], $this->bearer($owner))->assertStatus(422);
    }
}
