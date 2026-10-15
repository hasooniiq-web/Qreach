<?php

namespace Tests\Feature\Api;

use App\Models\ContactRequest;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\ContactReplyNotification;
use App\Notifications\ContactRequestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Another customer scans a vehicle's QR code with the app and carries on the
 * conversation from there: same six reasons, owner never learns who it is,
 * no message cap for the app conversation, deleted after a week like the rest.
 */
class ScanContactApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $scanner;

    private Tag $tag;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->owner = User::factory()->create();
        $this->scanner = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $this->owner->id, 'nickname' => 'سيارة العمل', 'make' => 'Toyota']);
        $this->tag = Tag::create([
            'public_token' => strtoupper(bin2hex(random_bytes(8))),
            'serial_number' => 'ST-Q-TEST-'.random_int(100000, 999999),
            'type' => 'qr', 'status' => 'assigned', 'vehicle_id' => $vehicle->id,
        ]);
    }

    private function as(User $u): array
    {
        // One test talks as several people: the guard must not keep the previous one.
        app('auth')->forgetGuards();

        return ['Authorization' => 'Bearer '.$u->createToken('t')->plainTextToken];
    }

    private function url(): string
    {
        return 'https://qreach.online/t/'.$this->tag->public_token;
    }

    public function test_lookup_shows_what_the_public_page_shows_and_the_six_reasons(): void
    {
        $this->postJson('/api/v1/scan/lookup', ['token' => $this->url()], $this->as($this->scanner))
            ->assertOk()
            ->assertJsonPath('token', $this->tag->public_token)
            ->assertJsonPath('vehicle.nickname', 'سيارة العمل')
            ->assertJsonCount(6, 'reasons')
            ->assertJsonPath('conversation_id', null);

        // The bare token works too (typed by hand).
        $this->postJson('/api/v1/scan/lookup', ['token' => $this->tag->public_token], $this->as($this->scanner))->assertOk();
    }

    public function test_a_code_that_is_not_answering_is_one_generic_refusal(): void
    {
        $this->postJson('/api/v1/scan/lookup', ['token' => 'NOPE-NOPE'], $this->as($this->scanner))
            ->assertStatus(404)->assertJsonPath('error.code', 'TAG_NOT_FOUND');

        // QR side switched off (only the chip works).
        $this->tag->update(['qr_enabled' => false]);
        $this->postJson('/api/v1/scan/lookup', ['token' => $this->url()], $this->as($this->scanner))
            ->assertStatus(404)->assertJsonPath('error.code', 'TAG_NOT_FOUND');
        $this->tag->update(['qr_enabled' => true]);

        // Paused vehicle.
        $this->tag->vehicle->update(['status' => 'inactive']);
        $this->postJson('/api/v1/scan/lookup', ['token' => $this->url()], $this->as($this->scanner))
            ->assertStatus(404);
    }

    public function test_a_customer_cannot_message_their_own_vehicle(): void
    {
        $this->postJson('/api/v1/scan/contact', ['token' => $this->url(), 'reason' => 'damage'], $this->as($this->owner))
            ->assertStatus(422)->assertJsonPath('error.code', 'OWN_VEHICLE');
        $this->assertDatabaseCount('contact_requests', 0);
    }

    public function test_only_the_six_reasons_open_a_conversation(): void
    {
        $this->postJson('/api/v1/scan/contact', ['token' => $this->url(), 'reason' => 'other'], $this->as($this->scanner))
            ->assertStatus(422);
        $this->postJson('/api/v1/scan/contact', ['token' => $this->url()], $this->as($this->scanner))
            ->assertStatus(422);
        $this->assertDatabaseCount('contact_requests', 0);
    }

    public function test_the_conversation_reaches_the_owner_without_revealing_who_started_it(): void
    {
        Notification::fake();

        $id = $this->postJson('/api/v1/scan/contact', ['token' => $this->url(), 'reason' => 'lights'], $this->as($this->scanner))
            ->assertCreated()->json('id');

        Notification::assertSentTo($this->owner, ContactRequestNotification::class);

        $inbox = $this->getJson('/api/v1/contact-requests', $this->as($this->owner))->assertOk();
        $inbox->assertJsonPath('data.0.id', $id)->assertJsonPath('data.0.role', 'owner')
            ->assertJsonPath('data.0.visitor_name', null)->assertJsonPath('data.0.visitor_phone', null)
            ->assertJsonPath('data.0.message', 'أضواء المركبة مشتعلة')->assertJsonPath('unread_count', 1);
        $this->assertStringNotContainsString($this->scanner->phone, $inbox->getContent());

        // The scanner sees it in their own list, as the one who started it.
        $this->getJson('/api/v1/contact-requests', $this->as($this->scanner))->assertOk()
            ->assertJsonPath('data.0.id', $id)->assertJsonPath('data.0.role', 'reporter')
            ->assertJsonPath('data.0.vehicle', 'سيارة العمل')->assertJsonPath('unread_count', 0);
    }

    public function test_both_sides_can_write_and_each_is_notified(): void
    {
        Notification::fake();
        $id = $this->postJson('/api/v1/scan/contact', ['token' => $this->url(), 'reason' => 'blocking'], $this->as($this->scanner))
            ->json('id');

        $this->postJson("/api/v1/contact-requests/{$id}/reply", ['body' => 'أنا قادم'], $this->as($this->owner))
            ->assertCreated()->assertJsonPath('role', 'owner');
        Notification::assertSentTo($this->scanner, ContactReplyNotification::class);

        $this->postJson("/api/v1/contact-requests/{$id}/reply", ['body' => 'شكرًا'], $this->as($this->scanner))
            ->assertCreated()->assertJsonPath('role', 'reporter');

        $thread = $this->getJson("/api/v1/contact-requests/{$id}", $this->as($this->scanner))->assertOk();
        $thread->assertJsonPath('messages.0.sender', 'owner')->assertJsonPath('messages.1.sender', 'visitor');
        $this->assertSame('new', ContactRequest::where('uuid', $id)->value('status'));
    }

    public function test_the_app_conversation_has_no_message_limit_and_the_same_person_keeps_one_thread(): void
    {
        Notification::fake();
        $id = $this->postJson('/api/v1/scan/contact', ['token' => $this->url(), 'reason' => 'blocking'], $this->as($this->scanner))->json('id');
        $thread = ContactRequest::where('uuid', $id)->firstOrFail();

        foreach (range(1, ContactRequest::MAX_MESSAGES + 5) as $i) {
            $thread->addMessage('visitor', "رسالة $i");
        }

        $this->postJson("/api/v1/contact-requests/{$id}/reply", ['body' => 'ما زلت أكتب'], $this->as($this->scanner))
            ->assertCreated();

        // Reporting again goes into the same conversation.
        $again = $this->postJson('/api/v1/scan/contact', ['token' => $this->url(), 'reason' => 'damage'], $this->as($this->scanner))
            ->assertCreated()->json('id');
        $this->assertSame($id, $again);
        $this->assertSame(1, ContactRequest::count());
    }

    public function test_a_stranger_cannot_read_or_answer_it_and_the_scanner_cannot_mark_it_read(): void
    {
        Notification::fake();
        $id = $this->postJson('/api/v1/scan/contact', ['token' => $this->url(), 'reason' => 'safety'], $this->as($this->scanner))->json('id');
        $stranger = User::factory()->create();

        $this->getJson("/api/v1/contact-requests/{$id}", $this->as($stranger))->assertNotFound();
        $this->postJson("/api/v1/contact-requests/{$id}/reply", ['body' => 'x'], $this->as($stranger))->assertNotFound();
        $this->putJson("/api/v1/contact-requests/{$id}/read", [], $this->as($this->scanner))->assertNotFound();
    }

    public function test_the_conversation_is_deleted_after_a_week_like_every_other(): void
    {
        Notification::fake();
        $id = $this->postJson('/api/v1/scan/contact', ['token' => $this->url(), 'reason' => 'blocking'], $this->as($this->scanner))->json('id');

        $this->travel(8)->days();
        $this->artisan('contacts:purge');

        $this->assertDatabaseMissing('contact_requests', ['uuid' => $id]);
    }
}
