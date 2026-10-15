<?php

namespace Tests\Feature\Public;

use App\Models\ContactRequest;
use App\Models\Scan;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\TagActionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_a_paused_vehicle_shows_the_generic_page_and_accepts_no_contact_until_reactivated(): void
    {
        $tag = $this->makeAssignedTag(['nickname' => 'سيارة موقوفة', 'status' => 'inactive']);

        $this->get("/t/{$tag->public_token}")->assertOk()->assertDontSee('سيارة موقوفة')->assertSee('أوقف صاحبه المركبة مؤقتًا');
        $this->assertDatabaseMissing('scans', ['tag_id' => $tag->id]);
        $this->post("/t/{$tag->public_token}/contact", ['reason' => 'damage'])->assertNotFound();
        $this->assertSame(0, ContactRequest::count());

        $tag->vehicle->update(['status' => 'active']);
        $this->get("/t/{$tag->public_token}")->assertOk()->assertSee('سيارة موقوفة');
    }

    public function test_scanning_a_valid_tag_shows_the_public_page_without_auth(): void
    {
        $tag = $this->makeAssignedTag(['nickname' => 'سيارتي']);

        $response = $this->get("/t/{$tag->public_token}");

        $response->assertStatus(200)->assertSee('سيارتي');
        $this->assertDatabaseHas('scans', ['tag_id' => $tag->id, 'action_type' => 'view']);
    }

    public function test_the_visitor_page_keeps_the_send_button_in_view(): void
    {
        $tag = $this->makeAssignedTag(['nickname' => 'سيارتي']);

        $html = $this->get("/t/{$tag->public_token}")->assertOk()->getContent();

        // The one action sticks to the bottom of the screen, so it never needs a scroll to reach.
        $this->assertStringContainsString('data-submit-bar', $html);
        $this->assertMatchesRegularExpression('/sticky bottom-0[^"]*"[^>]*data-submit-bar/', $html);
        $this->assertStringContainsString('إرسال البلاغ', $html);

        // The six reasons are low rows (icon beside the words), not tall tiles.
        $this->assertStringNotContainsString('min-h-24', $html);
        // ... and all six are exactly the same size (a fixed height, never content-driven).
        $this->assertSame(6, substr_count($html, 'flex h-14 items-center gap-2.5 rounded-xl border-2'));
        $this->assertSame(6, substr_count($html, 'name="reason"'));
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
            'reason' => 'assistance',
        ]);

        $response->assertRedirect();
        $response->assertDontSee('+9647709999999');

        $this->assertDatabaseHas('contact_requests', ['vehicle_id' => $vehicle->id, 'reason' => 'assistance']);
    }

    public function test_contact_owner_accepts_a_reason_without_a_typed_message_and_stores_its_label(): void
    {
        Notification::fake();
        $tag = $this->makeAssignedTag();

        $this->post("/t/{$tag->public_token}/contact", ['reason' => 'damage'])
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('contact_requests', ['tag_id' => $tag->id, 'message' => 'لاحظت ضررًا في المركبة']);
    }

    public function test_nothing_typed_by_the_visitor_is_stored(): void
    {
        Notification::fake();
        $tag = $this->makeAssignedTag();

        $this->post("/t/{$tag->public_token}/contact", [
            'reason' => 'assistance', 'visitor_name' => 'اسم', 'visitor_phone' => '07701234567', 'other_details' => 'نص',
        ])->assertSessionDoesntHaveErrors()->assertRedirect();

        $row = ContactRequest::where('tag_id', $tag->id)->firstOrFail();
        $this->assertNull($row->visitor_name);
        $this->assertNull($row->visitor_phone);
        $this->assertSame('أحتاج إلى مساعدة', $row->message);
    }
    public function test_a_reason_outside_the_predefined_list_is_not_accepted(): void
    {
        $tag = $this->makeAssignedTag();

        $this->post("/t/{$tag->public_token}/contact", ['message' => 'بدون سبب'])->assertSessionHasErrors('reason');
        \Illuminate\Support\Facades\Cache::flush(); // the throttle counts every attempt
        $this->post("/t/{$tag->public_token}/contact", ['reason' => '<script>'])->assertSessionHasErrors('reason');
        $this->assertDatabaseCount('contact_requests', 0);
    }

    public function test_the_blocking_reason_is_accepted_and_stores_its_fixed_label(): void
    {
        Notification::fake();
        $tag = $this->makeAssignedTag();

        $this->post("/t/{$tag->public_token}/contact", ['reason' => 'blocking'])
            ->assertSessionDoesntHaveErrors()->assertRedirect();

        $this->assertDatabaseHas('contact_requests', ['tag_id' => $tag->id, 'reason' => 'blocking', 'message' => 'سيارتك تعيق الطريق']);
    }

    public function test_the_other_reason_is_gone_and_the_lights_reason_takes_its_place(): void
    {
        Notification::fake();
        $tag = $this->makeAssignedTag();

        $this->post("/t/{$tag->public_token}/contact", ['reason' => 'other', 'other_details' => 'x'])->assertSessionHasErrors('reason');
        \Illuminate\Support\Facades\Cache::flush(); // the throttle counts every attempt
        $this->assertDatabaseCount('contact_requests', 0);

        $this->post("/t/{$tag->public_token}/contact", ['reason' => 'lights'])->assertSessionDoesntHaveErrors()->assertRedirect();
        $this->assertDatabaseHas('contact_requests', ['tag_id' => $tag->id, 'reason' => 'lights', 'message' => 'أضواء المركبة مشتعلة']);
    }
    public function test_the_scan_page_offers_the_six_reasons_and_nothing_to_type(): void
    {
        $tag = $this->makeAssignedTag();

        $this->get("/t/{$tag->public_token}")->assertOk()
            ->assertSee('سيارتك تعيق الطريق')->assertSee('ضرر في المركبة')->assertSee('أحتاج مساعدة')
            ->assertSee('الأضواء مشتعلة')->assertDontSee('سبب آخر')
            ->assertDontSee('name="message"', false)->assertDontSee('other_details', false)
            ->assertDontSee('name="visitor_name"', false)->assertDontSee('name="visitor_phone"', false);
    }

    public function test_the_visitor_thread_page_is_read_only_and_points_to_the_app(): void
    {
        Notification::fake();
        $tag = $this->makeAssignedTag();

        $this->post("/t/{$tag->public_token}/contact", ['reason' => 'blocking']);
        $thread = ContactRequest::where('tag_id', $tag->id)->firstOrFail();

        $this->get("/t/{$tag->public_token}/c/{$thread->uuid}")->assertOk()
            ->assertDontSee('<textarea', false)->assertDontSee('type="file"', false)
            ->assertSee('تطبيق Qreach');

        // There is no endpoint to post to any more.
        $this->post("/t/{$tag->public_token}/c/{$thread->uuid}", ['body' => 'مرحبا'])->assertStatus(405);
        $this->assertSame(0, $thread->messages()->count());
    }

    public function test_the_same_address_cannot_report_the_same_code_every_minute_or_more_than_five_times_a_day(): void
    {
        Notification::fake();
        $tag = $this->makeAssignedTag();

        $this->post("/t/{$tag->public_token}/contact", ['reason' => 'blocking'])->assertRedirect();
        // Straight away again: refused.
        $this->post("/t/{$tag->public_token}/contact", ['reason' => 'blocking'])->assertStatus(429);

        foreach (range(2, 5) as $i) {
            $this->travel(61)->seconds();
            $this->post("/t/{$tag->public_token}/contact", ['reason' => 'blocking'])->assertRedirect();
        }

        // The sixth in the same day is refused even after a quiet minute.
        $this->travel(61)->seconds();
        $this->post("/t/{$tag->public_token}/contact", ['reason' => 'blocking'])->assertStatus(429);
        $this->assertSame(5, ContactRequest::where('tag_id', $tag->id)->count());
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
