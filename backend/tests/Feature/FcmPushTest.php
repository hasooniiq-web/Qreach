<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\FcmSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FcmPushTest extends TestCase
{
    use RefreshDatabase;

    private string $keyFile;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        // A throw-away key generated for these tests only (never a real credential).
        $pem = <<<'PEM'
-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCguP4TAMUmvF4Z
izcDTkSiMRDdx7PkXclJyhDwgqz1HFjWtpWB0qvXdijSTsxqE7RpgSJCih9HaY/p
rXePoc00bn+GgLOkuYYv6ngy20+cJcA8g2ftefWQYRtxtv3g20pyf/N4SToVZuWJ
5YVWvuA539mDOPlORQfH+doPg6nIaH+e/R/Pn6iI6QdC9s4jz/yC+wvyMUgnJ8Qp
Pfm5W5iy3ScP1yt7hSlGq+zP9BHlpXuoPCwPwv5J9PUAV236ASQ/HSL+9NLxc5in
FCN1egTTMRWbA2cUEbvb7YJft7JHkAEhMFar20941Aoi0/12wumQY/6L0GipYvHl
EC4ijmdLAgMBAAECggEAF4uiZSRKvacA3hsf/xL7SQuJHgSAnScEMxQwMRvjvLDf
bu0mr/MuC42a41x549xpJL+vRjI37N1RIPmxt1AL4O+DLTAzoSEwwFpIK6MnYYfC
Rts1YXvpdrJTM/CL9uIJYQbPo0KPCM2fOPlv9EpYTQI3OlshYs8IGcrHoFg4ikxR
K5BzZ1/I6puRrCYa7kQUIKn5xHYNo4uLD4llJcruCuszHoFkd132Y1595uHCvUJS
jxZKjzRXMPr/It/DfHl5LW2Ax6hSYxbX1Dums8aZnV9cqo7/YpN8zvz66ijKgBzb
RJLaIEXYLBsqCHoeDniuAhX4o6OyAOtvG5PqclCnCQKBgQDcQkythjsRJYLk87tR
Y4Tb5PSWpZweyt7prnIou9KQbpf0g7nullBaPpW7kpMFrxdBj85zmKSGirNcLnl8
hxBSwQrBIMFKgnNHLY5NtFnFd2PpHbRvQLPDqJYeCw0mrDBFltdXfkgPemyqpt6x
S/G3b/BV6t8Z1f+Mtq19d9FQ9wKBgQC6zYOm5jK/sZCFqqs09kmfrXX7U64W8kpv
5uf0h0CQcXWJvwB+tU53RZp9yCWfvm7gA44FHFuY9jSbyG49D7m2ni8Dht/VdGGX
HmZEQ8wxR+JYCKLhfiBCUEArlhSflKv7LPeufWGJX8V3VM/EZ9ubzVB4Pn2YnW9/
+mPqfqkbTQKBgEMuAZ8ngtqO+2aDpI2Fch9zrNpJ9t05eVYC/BTKfMXKxZQxnUNV
G3r6/cZxRWCFb+1yREDjvMLAiUZDcpB1zZfAA1y2dd4Y/bom/fFbYcolyRZwFfmc
3Xd+8xKEwmUaPbrr8BBilu4JpB1EukWPaVFO1hoOgi+qIsCzcyUCp5gvAoGAWe2i
GXDssXgL+S1maoK+gs6A49MvFgDicOiadg++t0rHqN54oIcsFSM+8pqt8i89NT7A
2hkCx6qPPQfKej7dytpwC6SckSgL6zJJTUfOP1M51eOidie/4+y6V8FSgFiUSsEl
I61cjUO7zmzX8NeG04SN3rVGxXfVO6kQ/MypIGkCgYAyWZBUe2v8ixdEp8kU8e7C
aX24icZOkAtykbc6NX6IeBolm2aBGvaEjcnsBByXRt/juHuKdoPk18dJGtPhioys
fqD0ojcx2qqB50duzVIEtvR/hbuZ/p8+YOCh1XwPZ2i/JJRBhjZVPRZTDUyxVPKx
7MVqzB7K7vZf6evMx3Su9w==
-----END PRIVATE KEY-----
PEM;
        $this->keyFile = tempnam(sys_get_temp_dir(), 'fcm');
        file_put_contents($this->keyFile, json_encode([
            'type' => 'service_account', 'project_id' => 'qreach-test', 'client_email' => 'svc@qreach-test.iam.gserviceaccount.com', 'private_key' => $pem,
        ]));
    }

    protected function tearDown(): void
    {
        @unlink($this->keyFile);
        parent::tearDown();
    }

    private function ownerWithDevice(): array
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->for($owner)->create();
        $tag = Tag::create([
            'public_token' => bin2hex(random_bytes(16)), 'serial_number' => 'ST-Q-F-'.random_int(1000, 9999),
            'type' => 'qr', 'status' => 'assigned', 'vehicle_id' => $vehicle->id,
        ]);
        $device = Device::create(['user_id' => $owner->id, 'platform' => 'android', 'push_provider' => 'fcm', 'push_token' => 'device-token-1', 'is_active' => true]);

        return [$owner, $tag, $device];
    }

    public function test_nothing_is_sent_without_a_service_account_key(): void
    {
        config(['services.firebase.credentials' => '/nonexistent.json']);
        Http::fake();
        [, $tag] = $this->ownerWithDevice();

        $this->post("/t/{$tag->public_token}/contact", ['reason' => 'damage'])->assertRedirect();

        $this->assertFalse(app(FcmSender::class)->configured());
        Http::assertNothingSent();
    }

    public function test_a_visitor_message_pushes_to_the_owners_device(): void
    {
        config(['services.firebase.credentials' => $this->keyFile]);
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'ya29.test']),
            'fcm.googleapis.com/*' => Http::response(['name' => 'projects/qreach-test/messages/1']),
        ]);
        [, $tag] = $this->ownerWithDevice();

        $this->post("/t/{$tag->public_token}/contact", ['reason' => 'malfunction'])->assertRedirect();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'fcm.googleapis.com/v1/projects/qreach-test/messages:send')
            && $request['message']['token'] === 'device-token-1'
            && ((array) $request['message']['data'])['type'] === 'contact_request'
            && $request->hasHeader('Authorization', 'Bearer ya29.test'));
    }

    public function test_an_unregistered_token_deactivates_the_device(): void
    {
        config(['services.firebase.credentials' => $this->keyFile]);
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'ya29.test']),
            'fcm.googleapis.com/*' => Http::response(['error' => ['status' => 'NOT_FOUND', 'details' => [['errorCode' => 'UNREGISTERED']]]], 404),
        ]);
        [$owner, , $device] = $this->ownerWithDevice();

        app(FcmSender::class)->sendToUser($owner, 't', 'b');

        $this->assertFalse($device->fresh()->is_active);
    }
}
