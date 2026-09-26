<?php

namespace Tests\Feature;

use App\Models\ContactRequest;
use App\Models\Scan;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    private function assignedTag(User $owner): Tag
    {
        $vehicle = Vehicle::factory()->for($owner)->create();

        return Tag::create([
            'public_token' => bin2hex(random_bytes(16)), 'serial_number' => 'ST-Q-T-'.random_int(1000, 9999),
            'type' => 'qr', 'status' => 'assigned', 'vehicle_id' => $vehicle->id,
        ]);
    }

    private function bearer(User $u): array
    {
        return ['Authorization' => 'Bearer '.$u->createToken('t')->plainTextToken];
    }

    public function test_visitor_message_redirects_to_a_private_thread_and_owner_reply_appears_there(): void
    {
        $owner = User::factory()->create();
        $tag = $this->assignedTag($owner);

        $response = $this->post("/t/{$tag->public_token}/contact", ['reason' => 'damage']);
        $request = ContactRequest::firstOrFail();
        $response->assertRedirect("/t/{$tag->public_token}/c/{$request->uuid}");

        $this->getJson("/api/v1/contact-requests/{$request->uuid}/reply", $this->bearer($owner)); // GET not allowed
        $this->postJson("/api/v1/contact-requests/{$request->uuid}/reply", ['body' => 'أنا قادم الآن'], $this->bearer($owner))
            ->assertCreated()->assertJsonPath('status', 'responded')->assertJsonPath('messages.0.sender', 'owner');

        $this->get("/t/{$tag->public_token}/c/{$request->uuid}")
            ->assertOk()->assertSee('لاحظت ضررًا في المركبة')->assertSee('أنا قادم الآن');
    }

    public function test_visitor_follow_up_reopens_the_request_for_the_owner(): void
    {
        $owner = User::factory()->create();
        $tag = $this->assignedTag($owner);
        $request = ContactRequest::create(['vehicle_id' => $tag->vehicle_id, 'tag_id' => $tag->id, 'message' => 'مرحبا', 'status' => 'responded']);

        $this->post("/t/{$tag->public_token}/c/{$request->uuid}", ['body' => 'شكرًا، هل يمكنك التعجيل؟'])->assertRedirect();

        $this->assertSame('new', $request->fresh()->status);
        $this->getJson("/api/v1/contact-requests/{$request->uuid}", $this->bearer($owner))
            ->assertOk()->assertJsonPath('messages.0.sender', 'visitor');
    }

    public function test_a_thread_is_not_reachable_through_another_tags_token_or_by_another_owner(): void
    {
        $owner = User::factory()->create();
        $tag = $this->assignedTag($owner);
        $otherTag = $this->assignedTag(User::factory()->create());
        $request = ContactRequest::create(['vehicle_id' => $tag->vehicle_id, 'tag_id' => $tag->id, 'message' => 'سري']);

        $this->get("/t/{$otherTag->public_token}/c/{$request->uuid}")->assertOk()->assertDontSee('سري');
        $this->postJson("/api/v1/contact-requests/{$request->uuid}/reply", ['body' => 'x'], $this->bearer(User::factory()->create()))
            ->assertNotFound();
    }

    public function test_conversations_are_purged_fifteen_days_after_last_activity(): void
    {
        $owner = User::factory()->create();
        $tag = $this->assignedTag($owner);
        $old = ContactRequest::create(['vehicle_id' => $tag->vehicle_id, 'tag_id' => $tag->id, 'message' => 'قديمة']);
        $old->addMessage('owner', 'رد');
        $old->forceFill(['last_activity_at' => now()->subDays(16)])->save();
        Scan::create(['tag_id' => $tag->id, 'vehicle_id' => $tag->vehicle_id, 'action_type' => 'contact_owner', 'payload' => ['message' => 'قديمة'], 'status' => 'success']);
        Scan::query()->update(['created_at' => now()->subDays(16)]);

        $active = ContactRequest::create(['vehicle_id' => $tag->vehicle_id, 'tag_id' => $tag->id, 'message' => 'نشطة']);
        $active->forceFill(['created_at' => now()->subDays(30), 'last_activity_at' => now()->subDays(2)])->save();

        $this->artisan('contacts:purge')->assertSuccessful();

        $this->assertDatabaseMissing('contact_requests', ['id' => $old->id]);
        $this->assertDatabaseCount('contact_request_messages', 0);
        $this->assertDatabaseHas('contact_requests', ['id' => $active->id]);
        $this->assertNull(Scan::first()->payload);
    }

    public function test_customer_can_upload_and_remove_a_profile_photo(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $headers = $this->bearer($user);

        $this->post('/api/v1/profile/avatar', ['avatar' => UploadedFile::fake()->image('me.png', 300, 300)], $headers + ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('has_avatar', true);
        $this->get('/api/v1/profile/avatar', $headers)->assertOk()->assertHeader('Content-Type', 'image/jpeg');

        $this->deleteJson('/api/v1/profile/avatar', [], $headers)->assertOk()->assertJsonPath('has_avatar', false);
        $this->get('/api/v1/profile/avatar', $headers)->assertNotFound();
    }
}
