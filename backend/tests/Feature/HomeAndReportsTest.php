<?php

namespace Tests\Feature;

use App\Models\ContactRequest;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HomeAndReportsTest extends TestCase
{
    use RefreshDatabase;

    private function tagFor(User $owner): Tag
    {
        $vehicle = Vehicle::factory()->for($owner)->create();

        return Tag::create([
            'public_token' => bin2hex(random_bytes(16)), 'serial_number' => 'ST-Q-H-'.random_int(1000, 9999),
            'type' => 'qr', 'status' => 'assigned', 'vehicle_id' => $vehicle->id,
        ]);
    }

    private function bearer(User $u): array
    {
        return ['Authorization' => 'Bearer '.$u->createToken('t')->plainTextToken];
    }

    public function test_quick_report_reasons_are_stored_and_shown_with_their_status(): void
    {
        $owner = User::factory()->create();
        $tag = $this->tagFor($owner);

        foreach (['malfunction', 'assistance'] as $reason) {
            $this->post("/t/{$tag->public_token}/contact", ['reason' => $reason])->assertRedirect();
            $this->assertDatabaseHas('contact_requests', ['reason' => $reason]);
        }

        Cache::flush(); // reset the per-minute throttle
        $this->post("/t/{$tag->public_token}/contact", ['reason' => 'other'])->assertSessionHasErrors('message');
        $this->post("/t/{$tag->public_token}/contact", ['reason' => 'other', 'message' => 'شيء آخر'])->assertRedirect();

        $request = ContactRequest::where('reason', 'malfunction')->first();
        $this->get("/t/{$tag->public_token}/c/{$request->uuid}")->assertOk()->assertSee('بانتظار قراءة المالك')->assertSee('عطل');
        $this->getJson("/t/{$tag->public_token}/c/{$request->uuid}/messages")
            ->assertOk()->assertJsonPath('status', 'new')->assertJsonPath('messages.0.sender', 'visitor');
    }

    public function test_home_endpoint_returns_only_the_latest_three_conversations_and_activity_areas(): void
    {
        $owner = User::factory()->create();
        $tag = $this->tagFor($owner);
        foreach (range(1, 5) as $i) {
            ContactRequest::create(['vehicle_id' => $tag->vehicle_id, 'tag_id' => $tag->id, 'message' => "رسالة $i", 'reason' => 'damage']);
        }
        $other = $this->tagFor(User::factory()->create());
        ContactRequest::create(['vehicle_id' => $other->vehicle_id, 'tag_id' => $other->id, 'message' => 'غير مرئية']);

        $response = $this->getJson('/api/v1/home', $this->bearer($owner))->assertOk();

        $response->assertJsonCount(3, 'conversations')
            ->assertJsonPath('unread_messages', 5)
            ->assertJsonStructure(['activities' => ['latest', 'communication', 'account', 'reports']]);
        $this->assertStringNotContainsString('غير مرئية', $response->getContent());
        $response->assertJsonPath('activities.reports.0.text', 'ضرر في المركبة — جديد');
    }

    public function test_vehicle_stores_iraqi_plate_details(): void
    {
        $owner = User::factory()->create();

        $this->postJson('/api/v1/vehicles', [
            'plate_number' => '12345', 'plate_type' => 'taxi', 'plate_letter' => 'ب', 'plate_governorate' => 'بغداد',
        ], $this->bearer($owner))->assertCreated()
            ->assertJsonPath('data.plate_type', 'taxi')->assertJsonPath('data.plate_letter', 'ب')
            ->assertJsonPath('data.plate_governorate', 'بغداد');

        $this->postJson('/api/v1/vehicles', ['plate_number' => '1', 'plate_type' => 'spaceship'], $this->bearer($owner))->assertStatus(422);
    }

    public function test_admin_poll_endpoint_reports_new_work(): void
    {
        $this->seed(SubscriptionPlanSeeder::class);
        $owner = User::factory()->create();
        $tag = $this->tagFor($owner);
        ContactRequest::create(['vehicle_id' => $tag->vehicle_id, 'tag_id' => $tag->id, 'message' => 'x']);

        $this->get('/admin/poll')->assertRedirect();
    }
}
