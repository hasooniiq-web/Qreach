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

    public function test_owner_can_reply_with_an_image_stored_privately_and_served_only_to_them(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $tag = $this->assignedTag($owner);
        $thread = ContactRequest::create(['vehicle_id' => $tag->vehicle_id, 'tag_id' => $tag->id, 'message' => 'x']);
        $headers = $this->bearer($owner) + ['Accept' => 'application/json'];

        $res = $this->post("/api/v1/contact-requests/{$thread->uuid}/reply", ['image' => UploadedFile::fake()->image('p.png', 900, 700)], $headers)
            ->assertCreated()->assertJsonPath('messages.0.sender', 'owner');
        $url = $res->json('messages.0.image_url');
        $this->assertNotNull($url);
        Storage::disk('local')->assertExists($thread->messages()->first()->image_path);

        $this->get('/api/v1'.$url, $headers)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->app['auth']->forgetGuards(); // Sanctum caches the first user within one test
        $this->get('/api/v1'.$url, $this->bearer(User::factory()->create()))->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1'.$url)->assertUnauthorized();
    }

    public function test_owner_reply_rejects_wrong_file_types_oversize_images_and_empty_messages(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $tag = $this->assignedTag($owner);
        $thread = ContactRequest::create(['vehicle_id' => $tag->vehicle_id, 'tag_id' => $tag->id, 'message' => 'x']);
        $headers = $this->bearer($owner) + ['Accept' => 'application/json'];
        $url = "/api/v1/contact-requests/{$thread->uuid}/reply";

        $this->post($url, ['image' => UploadedFile::fake()->create('evil.pdf', 10, 'application/pdf')], $headers)->assertUnprocessable();
        $this->post($url, ['image' => UploadedFile::fake()->create('evil.php', 10, 'text/x-php')], $headers)->assertUnprocessable();
        $this->post($url, ['image' => UploadedFile::fake()->image('big.jpg')->size(9000)], $headers)->assertUnprocessable();
        $this->post($url, [], $headers)->assertUnprocessable();
        $this->assertSame(0, $thread->messages()->count());
    }

    public function test_a_chat_that_keeps_going_still_ends_seven_days_after_its_first_message(): void
    {
        $owner = User::factory()->create();
        $tag = $this->assignedTag($owner);
        $thread = ContactRequest::create(['vehicle_id' => $tag->vehicle_id, 'tag_id' => $tag->id, 'message' => 'x']);
        $thread->forceFill(['created_at' => now()->subDays(8)])->save();
        $thread->addMessage('visitor', 'ما زلت هنا'); // activity today does not extend its life

        $this->assertTrue($thread->fresh()->expiresAt()->isPast());
        $this->artisan('contacts:purge')->assertSuccessful();

        $this->assertDatabaseMissing('contact_requests', ['id' => $thread->id]);
    }

    public function test_expiry_date_is_seven_days_from_the_start(): void
    {
        $owner = User::factory()->create();
        $tag = $this->assignedTag($owner);
        $thread = ContactRequest::create(['vehicle_id' => $tag->vehicle_id, 'tag_id' => $tag->id, 'message' => 'x']);

        $this->assertEquals($thread->created_at->copy()->addDays(7)->timestamp, $thread->expiresAt()->timestamp);
    }
    public function test_purge_deletes_expired_photos_and_sweeps_orphans_but_keeps_live_ones(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $tag = $this->assignedTag($owner);

        $old = ContactRequest::create(['vehicle_id' => $tag->vehicle_id, 'tag_id' => $tag->id, 'message' => 'x']);
        Storage::disk('local')->put('contact-message-images/old.jpg', 'a');
        $old->addMessage('visitor', '', 'contact-message-images/old.jpg');
        $old->forceFill(['created_at' => now()->subDays(8)])->save();

        $live = ContactRequest::create(['vehicle_id' => $tag->vehicle_id, 'tag_id' => $tag->id, 'message' => 'y']);
        Storage::disk('local')->put('contact-message-images/live.jpg', 'b');
        $live->addMessage('visitor', '', 'contact-message-images/live.jpg');

        $orphan = 'contact-message-images/orphan.jpg';
        Storage::disk('local')->put($orphan, 'c');
        touch(Storage::disk('local')->path($orphan), now()->subDays(3)->getTimestamp());

        $this->artisan('contacts:purge')->assertSuccessful();
        $this->artisan('contacts:purge')->assertSuccessful(); // idempotent

        Storage::disk('local')->assertMissing('contact-message-images/old.jpg');
        Storage::disk('local')->assertMissing($orphan);
        Storage::disk('local')->assertExists('contact-message-images/live.jpg');
        $this->assertDatabaseHas('contact_requests', ['id' => $live->id]);
        $this->assertDatabaseMissing('contact_requests', ['id' => $old->id]);
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

    public function test_conversations_are_purged_seven_days_after_they_started(): void
    {
        $owner = User::factory()->create();
        $tag = $this->assignedTag($owner);
        $old = ContactRequest::create(['vehicle_id' => $tag->vehicle_id, 'tag_id' => $tag->id, 'message' => 'قديمة']);
        $old->addMessage('owner', 'رد');
        $old->forceFill(['created_at' => now()->subDays(8)])->save();
        Scan::create(['tag_id' => $tag->id, 'vehicle_id' => $tag->vehicle_id, 'action_type' => 'contact_owner', 'payload' => ['message' => 'قديمة'], 'status' => 'success']);
        Scan::query()->update(['created_at' => now()->subDays(8)]);

        $active = ContactRequest::create(['vehicle_id' => $tag->vehicle_id, 'tag_id' => $tag->id, 'message' => 'نشطة']);
        $active->forceFill(['created_at' => now()->subDays(2), 'last_activity_at' => now()])->save();

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
