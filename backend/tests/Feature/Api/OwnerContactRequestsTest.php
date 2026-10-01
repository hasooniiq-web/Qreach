<?php

namespace Tests\Feature\Api;

use App\Models\ContactRequest;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OwnerContactRequestsTest extends TestCase
{
    use RefreshDatabase;

    private function requestFor(User $owner, string $message = 'مرحبا'): ContactRequest
    {
        $vehicle = Vehicle::factory()->for($owner)->create(['nickname' => 'سيارتي']);
        $tag = Tag::create([
            'public_token' => bin2hex(random_bytes(16)), 'serial_number' => 'ST-Q-C-'.random_int(1000, 9999),
            'type' => 'qr', 'status' => 'assigned', 'vehicle_id' => $vehicle->id,
        ]);

        return ContactRequest::create([
            'vehicle_id' => $vehicle->id, 'tag_id' => $tag->id, 'visitor_name' => 'زائر', 'visitor_phone' => '+9647701112233',
            'message' => $message, 'request_ip' => '203.0.113.5', 'user_agent' => 'TestAgent',
        ]);
    }

    private function bearer(User $u): array
    {
        return ['Authorization' => 'Bearer '.$u->createToken('t')->plainTextToken];
    }

    public function test_owner_sees_only_their_requests_without_ip_or_agent(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->requestFor($owner, 'رسالتي');
        $this->requestFor($other, 'رسالة غيري');

        $response = $this->getJson('/api/v1/contact-requests', $this->bearer($owner))->assertOk();

        $response->assertJsonCount(1, 'data')->assertJsonPath('data.0.message', 'رسالتي')
            ->assertJsonPath('data.0.vehicle', 'سيارتي')->assertJsonPath('unread_count', 1);
        $this->assertStringNotContainsString('203.0.113.5', $response->getContent());
        $this->assertStringNotContainsString('TestAgent', $response->getContent());
    }

    public function test_marking_read_then_responded_updates_status_and_counts(): void
    {
        $owner = User::factory()->create();
        $req = $this->requestFor($owner);
        $headers = $this->bearer($owner);

        $this->putJson("/api/v1/contact-requests/{$req->uuid}/read", [], $headers)->assertOk()->assertJsonPath('status', 'read');
        $this->assertNotNull($req->fresh()->read_at);
        $this->getJson('/api/v1/contact-requests', $headers)->assertJsonPath('unread_count', 0);

        $this->putJson("/api/v1/contact-requests/{$req->uuid}/responded", [], $headers)->assertOk()->assertJsonPath('status', 'responded');
        $this->assertNotNull($req->fresh()->responded_at);
    }

    public function test_another_users_request_is_a_404_for_read_and_responded(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $req = $this->requestFor($owner);

        $this->putJson("/api/v1/contact-requests/{$req->uuid}/read", [], $this->bearer($intruder))->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->putJson("/api/v1/contact-requests/{$req->uuid}/responded", [], $this->bearer($intruder))->assertNotFound();

        $this->assertSame('new', $req->fresh()->status);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/contact-requests')->assertUnauthorized();
    }

    public function test_owner_can_view_a_photo_the_visitor_attached_but_another_user_cannot(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $req = $this->requestFor($owner);
        $message = $req->addMessage('visitor', '', 'contact-message-images/test.jpg');
        Storage::disk('local')->put('contact-message-images/test.jpg', 'fake-jpeg-bytes');

        $this->getJson("/api/v1/contact-requests/{$req->uuid}", $this->bearer($owner))
            ->assertOk()
            ->assertJsonPath('messages.0.image_url', "/contact-requests/{$req->uuid}/messages/{$message->id}/image");

        $this->get("/api/v1/contact-requests/{$req->uuid}/messages/{$message->id}/image", $this->bearer($owner))
            ->assertOk()->assertHeader('Content-Type', 'image/jpeg');

        $this->app['auth']->forgetGuards();
        $this->get("/api/v1/contact-requests/{$req->uuid}/messages/{$message->id}/image", $this->bearer($intruder))
            ->assertNotFound();
    }
}
