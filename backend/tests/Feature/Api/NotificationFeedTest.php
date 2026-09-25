<?php

namespace Tests\Feature\Api;

use App\Models\Scan;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\TagActionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationFeedTest extends TestCase
{
    use RefreshDatabase;

    private function bearer(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('t')->plainTextToken];
    }

    private function notifyOwner(User $owner, string $action = 'vehicle_block'): void
    {
        $vehicle = Vehicle::factory()->for($owner)->create(['nickname' => 'سيارتي']);
        $tag = Tag::create([
            'public_token' => bin2hex(random_bytes(16)), 'serial_number' => 'ST-Q-N-'.random_int(1000, 9999),
            'type' => 'qr', 'status' => 'assigned', 'vehicle_id' => $vehicle->id,
        ]);
        $scan = Scan::create(['tag_id' => $tag->id, 'vehicle_id' => $vehicle->id, 'action_type' => $action, 'status' => 'success']);

        $owner->notifyNow(new TagActionNotification($scan));
    }

    public function test_feed_lists_own_notifications_with_vehicle_name_and_unread_count(): void
    {
        $owner = User::factory()->create();
        $this->notifyOwner($owner, 'vehicle_block');
        $this->notifyOwner($owner, 'emergency');

        $response = $this->getJson('/api/v1/notifications', $this->bearer($owner))->assertOk();

        $response->assertJsonPath('unread_count', 2)->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.vehicle', 'سيارتي')
            ->assertJsonPath('data.0.read', false);
        $this->assertContains('emergency', collect($response->json('data'))->pluck('action_type')->all());
    }

    public function test_feed_never_includes_another_users_notifications(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->notifyOwner($owner);

        $this->getJson('/api/v1/notifications', $this->bearer($other))->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('unread_count', 0);
    }

    public function test_marking_read_updates_the_count_and_cannot_touch_someone_elses(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->notifyOwner($owner);
        $id = $owner->notifications()->first()->id;

        $this->putJson("/api/v1/notifications/{$id}/read", [], $this->bearer($other))->assertNotFound();
        $this->assertSame(1, $owner->unreadNotifications()->count());

        // Guards cache the resolved user across simulated requests in one test.
        $this->app['auth']->forgetGuards();

        $this->putJson("/api/v1/notifications/{$id}/read", [], $this->bearer($owner))->assertOk()->assertJsonPath('unread_count', 0);
        $this->assertNotNull($owner->notifications()->first()->read_at);
    }

    public function test_mark_all_read_only_affects_the_caller(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->notifyOwner($owner);
        $this->notifyOwner($owner);
        $this->notifyOwner($other);

        $this->putJson('/api/v1/notifications/read-all', [], $this->bearer($owner))->assertOk()->assertJsonPath('unread_count', 0);

        $this->assertSame(0, $owner->unreadNotifications()->count());
        $this->assertSame(1, $other->unreadNotifications()->count());
    }

    public function test_feed_requires_authentication(): void
    {
        $this->getJson('/api/v1/notifications')->assertUnauthorized();
    }
}
