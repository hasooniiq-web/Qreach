<?php

namespace Tests\Feature\Public;

use App\Models\Scan;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\TagActionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Section 13/44/45 regression coverage: the public scan page must never
 * require auth, never leak owner-private data, and must reject malformed
 * or malicious input server-side regardless of what the client sends.
 */
class ScanTest extends TestCase
{
    use RefreshDatabase;

    private function makeAssignedTag(array $vehicleAttrs = []): Tag
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user)->create($vehicleAttrs);

        return Tag::create([
            'public_token' => bin2hex(random_bytes(16)),
            'serial_number' => 'ST-Q-TEST-'.random_int(100000, 999999),
            'type' => 'qr',
            'status' => 'assigned',
            'vehicle_id' => $vehicle->id,
            'activated_at' => now(),
            'assigned_at' => now(),
        ]);
    }

    public function test_scanning_a_valid_tag_shows_the_public_page_without_auth(): void
    {
        $tag = $this->makeAssignedTag(['nickname' => 'سيارتي']);

        $response = $this->get("/t/{$tag->public_token}");

        $response->assertStatus(200)->assertSee('سيارتي');
        $this->assertDatabaseHas('scans', ['tag_id' => $tag->id, 'action_type' => 'view']);
    }

    public function test_scanning_an_unknown_token_shows_a_generic_invalid_page_not_a_500(): void
    {
        $this->get('/t/this-token-does-not-exist')->assertStatus(200)->assertViewIs('public.invalid');
    }

    public function test_scanning_a_revoked_tag_shows_the_same_generic_invalid_page(): void
    {
        $tag = $this->makeAssignedTag();
        $tag->update(['status' => 'revoked']);

        // Same view as an unknown token — no oracle for guessing which
        // tokens exist (section 45: QR token enumeration).
        $this->get("/t/{$tag->public_token}")->assertStatus(200)->assertViewIs('public.invalid');
    }

    public function test_vehicle_block_action_creates_a_scan_and_notifies_the_owner(): void
    {
        Notification::fake();
        $tag = $this->makeAssignedTag();

        $this->post("/t/{$tag->public_token}/vehicle-block")->assertRedirect();

        $this->assertDatabaseHas('scans', ['tag_id' => $tag->id, 'action_type' => 'vehicle_block']);
        Notification::assertSentTo($tag->vehicle->user, TagActionNotification::class);
    }

    public function test_important_message_rejects_html_content(): void
    {
        $tag = $this->makeAssignedTag();

        $response = $this->post("/t/{$tag->public_token}/message", [
            'message' => '<script>alert(1)</script>',
        ]);

        $response->assertSessionHasErrors('message');
        $this->assertDatabaseMissing('scans', ['tag_id' => $tag->id, 'action_type' => 'important_message']);
    }

    public function test_important_message_accepts_plain_arabic_text(): void
    {
        Notification::fake();
        $tag = $this->makeAssignedTag();

        $response = $this->post("/t/{$tag->public_token}/message", [
            'message' => 'سيارتك تحجب المدخل، الرجاء نقلها.',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('scans', ['tag_id' => $tag->id, 'action_type' => 'important_message']);
    }

    public function test_emergency_requires_explicit_confirmation(): void
    {
        $tag = $this->makeAssignedTag();

        $this->post("/t/{$tag->public_token}/emergency", ['message' => 'test'])
            ->assertSessionHasErrors('confirmed');

        $this->assertDatabaseMissing('scans', ['tag_id' => $tag->id, 'action_type' => 'emergency']);
    }

    public function test_confirmed_emergency_creates_a_scan_and_a_security_event(): void
    {
        Notification::fake();
        $tag = $this->makeAssignedTag();

        $this->post("/t/{$tag->public_token}/emergency", ['confirmed' => '1', 'message' => 'حريق'])
            ->assertRedirect();

        $this->assertDatabaseHas('scans', ['tag_id' => $tag->id, 'action_type' => 'emergency']);
        $this->assertDatabaseHas('security_events', ['event_type' => 'EMERGENCY_TRIGGERED']);
    }

    public function test_contact_owner_creates_a_contact_request_without_exposing_owner_phone(): void
    {
        Notification::fake();
        $owner = User::factory()->create(['phone' => '+9647709999999']);
        $vehicle = Vehicle::factory()->for($owner)->create();
        $tag = Tag::create([
            'public_token' => bin2hex(random_bytes(16)),
            'serial_number' => 'ST-Q-TEST-'.random_int(100000, 999999),
            'type' => 'qr', 'status' => 'assigned', 'vehicle_id' => $vehicle->id,
        ]);

        $response = $this->post("/t/{$tag->public_token}/contact", [
            'message' => 'أحتاج التواصل معك',
            'visitor_name' => 'زائر',
        ]);

        $response->assertRedirect();
        $response->assertDontSee('+9647709999999');

        $this->assertDatabaseHas('contact_requests', ['vehicle_id' => $vehicle->id, 'visitor_name' => 'زائر']);
    }

    public function test_public_action_rate_limit_blocks_excessive_requests_from_the_same_ip(): void
    {
        $tag = $this->makeAssignedTag();

        // 'public-action' limiter allows 6/min per IP.
        for ($i = 0; $i < 6; $i++) {
            $this->post("/t/{$tag->public_token}/vehicle-block");
        }

        $this->post("/t/{$tag->public_token}/vehicle-block")->assertStatus(429);
    }
}
