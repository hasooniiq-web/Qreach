<?php

namespace Tests\Feature;

use App\Models\Scan;
use App\Models\SecurityEvent;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_scans_lose_identifiers_and_very_old_ones_are_deleted(): void
    {
        $vehicle = Vehicle::factory()->for(User::factory()->create())->create();
        $tag = Tag::create([
            'public_token' => bin2hex(random_bytes(16)), 'serial_number' => 'ST-Q-R-1',
            'type' => 'qr', 'status' => 'assigned', 'vehicle_id' => $vehicle->id,
        ]);
        $mk = fn (int $days) => tap(Scan::create([
            'tag_id' => $tag->id, 'vehicle_id' => $vehicle->id, 'action_type' => 'view', 'status' => 'success',
            'request_ip' => '203.0.113.9', 'user_agent' => 'UA',
        ]), fn ($s) => Scan::whereKey($s->id)->update(['created_at' => now()->subDays($days)]));

        $fresh = $mk(5);
        $old = $mk(120);
        $ancient = $mk(500);

        $this->artisan('data:prune')->assertSuccessful();

        $this->assertSame('203.0.113.9', $fresh->fresh()->request_ip);
        $this->assertNull($old->fresh()->request_ip);
        $this->assertNull($old->fresh()->user_agent);
        $this->assertNull(Scan::find($ancient->id));
    }

    public function test_old_security_events_keep_the_event_but_lose_the_ip(): void
    {
        $event = SecurityEvent::create([
            'event_type' => 'QR_GUESSING', 'severity' => 'info', 'actor_type' => 'unknown', 'request_ip' => '198.51.100.7', 'user_agent' => 'UA',
        ]);
        SecurityEvent::whereKey($event->id)->update(['created_at' => now()->subDays(200)]);

        $this->artisan('data:prune')->assertSuccessful();

        $this->assertNotNull(SecurityEvent::find($event->id));
        $this->assertNull($event->fresh()->request_ip);
    }
}
