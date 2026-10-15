<?php

namespace Tests\Feature\Api;

use App\Models\Admin;
use App\Models\ContactRequest;
use App\Models\ConversationReport;
use App\Models\Role;
use App\Models\SupportTicket;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\SupportReplyNotification;
use App\Services\SupportContacts;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Technical support from the app (WhatsApp numbers, tickets with photos), and the
 * conversation tools: report the other side, show or hide your name, trust badge.
 */
class SupportAndReportingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function as(User $u): array
    {
        app('auth')->forgetGuards();

        return ['Authorization' => 'Bearer '.$u->createToken('t')->plainTextToken];
    }

    private function admin(): Admin
    {
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);

        return $admin;
    }

    // ---- support -----------------------------------------------------------------------------------------

    public function test_the_whatsapp_numbers_are_set_in_the_dashboard_and_shown_in_the_app(): void
    {
        $this->actingAs($this->admin(), 'admin')->put('/admin/support/settings', ['numbers' => [
            ['label' => 'الدعم', 'phone' => '07701234567'],
            ['label' => '', 'phone' => ''],
        ]])->assertSessionHasNoErrors();

        $this->getJson('/api/v1/support/info', $this->as(User::factory()->create()))
            ->assertOk()
            ->assertJsonCount(1, 'whatsapp')
            ->assertJsonPath('whatsapp.0.label', 'الدعم')
            ->assertJsonPath('whatsapp.0.phone', '+9647701234567')
            ->assertJsonPath('whatsapp.0.url', 'https://wa.me/9647701234567');

        $this->actingAs($this->admin(), 'admin')->put('/admin/support/settings', ['numbers' => [
            ['label' => 'x', 'phone' => '123'],
        ]])->assertSessionHasErrors('numbers.0.phone');
    }

    public function test_a_ticket_with_a_photo_is_answered_from_the_dashboard(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $created = $this->post('/api/v1/support/tickets', [
            'category' => 'bug', 'subject' => 'التطبيق يتوقف', 'body' => 'يتوقف عند مسح الرمز',
            'image' => UploadedFile::fake()->image('shot.png', 800, 600),
        ], $this->as($user) + ['Accept' => 'application/json'])->assertCreated();

        $id = $created->json('id');
        $created->assertJsonPath('status', 'open')->assertJsonCount(1, 'messages');
        $this->assertNotNull($created->json('messages.0.image_url'));

        // The photo is served only to its owner.
        $mid = SupportTicket::where('uuid', $id)->firstOrFail()->messages()->value('id');
        $this->get("/api/v1/support/tickets/{$id}/messages/{$mid}/image", $this->as($user))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->get("/api/v1/support/tickets/{$id}/messages/{$mid}/image", $this->as(User::factory()->create()))->assertNotFound();

        // The team answers.
        $ticket = SupportTicket::where('uuid', $id)->firstOrFail();
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->get("/admin/support/{$ticket->id}")->assertOk()->assertSee('التطبيق يتوقف');
        $this->actingAs($admin, 'admin')->post("/admin/support/{$ticket->id}/reply", ['body' => 'جرّب تحديث التطبيق'])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($user, SupportReplyNotification::class);
        $this->getJson("/api/v1/support/tickets/{$id}", $this->as($user))
            ->assertJsonPath('status', 'answered')->assertJsonPath('messages.1.sender', 'staff');

        // The customer follows up; the ticket waits for support again.
        $this->postJson("/api/v1/support/tickets/{$id}/reply", ['body' => 'تم، ما زالت المشكلة'], $this->as($user))
            ->assertCreated()->assertJsonPath('status', 'open');

        // Closed: no more replies.
        $this->actingAs($admin, 'admin')->post("/admin/support/{$ticket->id}/close")->assertSessionHasNoErrors();
        $this->postJson("/api/v1/support/tickets/{$id}/reply", ['body' => 'x'], $this->as($user))
            ->assertStatus(422)->assertJsonPath('error.code', 'TICKET_CLOSED');
    }

    public function test_tickets_are_private_and_limited(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $id = $this->postJson('/api/v1/support/tickets', ['category' => 'general', 'subject' => 'س', 'body' => 'نص'], $this->as($user))
            ->assertCreated()->json('id');

        $this->getJson("/api/v1/support/tickets/{$id}", $this->as($other))->assertNotFound();
        $this->getJson('/api/v1/support/tickets', $this->as($other))->assertJsonCount(0, 'data');

        foreach (range(2, SupportTicket::MAX_OPEN) as $i) {
            $this->postJson('/api/v1/support/tickets', ['category' => 'general', 'subject' => "س$i", 'body' => 'نص'], $this->as($user))->assertCreated();
        }
        $this->postJson('/api/v1/support/tickets', ['category' => 'general', 'subject' => 'زائد', 'body' => 'نص'], $this->as($user))
            ->assertStatus(422)->assertJsonPath('error.code', 'TOO_MANY_TICKETS');
    }

    // ---- reporting and identity -------------------------------------------------------------------------

    private function appConversation(): array
    {
        Notification::fake();
        $owner = User::factory()->create();
        $scanner = User::factory()->create(['created_at' => now()->subDays(60)]);
        $vehicle = Vehicle::factory()->create(['user_id' => $owner->id]);
        $tag = Tag::create([
            'public_token' => strtoupper(bin2hex(random_bytes(8))), 'serial_number' => 'ST-Q-TEST-'.random_int(100000, 999999),
            'type' => 'qr', 'status' => 'assigned', 'vehicle_id' => $vehicle->id,
        ]);
        $id = $this->postJson('/api/v1/scan/contact', ['token' => $tag->public_token, 'reason' => 'blocking'], $this->as($scanner))->json('id');

        return [$owner, $scanner, $id];
    }

    public function test_the_name_is_hidden_by_default_and_the_owner_sees_a_trust_badge(): void
    {
        [$owner, $scanner, $id] = $this->appConversation();

        $r = $this->getJson("/api/v1/contact-requests/{$id}", $this->as($owner))->assertOk();
        $r->assertJsonPath('sender.kind', 'app')->assertJsonPath('sender.identity_visible', false)
            ->assertJsonPath('sender.name', null)->assertJsonPath('sender.badge', 'verified');

        // An older account with a linked code is "trusted".
        $v = Vehicle::factory()->create(['user_id' => $scanner->id]);
        Tag::create([
            'public_token' => strtoupper(bin2hex(random_bytes(8))), 'serial_number' => 'ST-Q-TEST-'.random_int(100000, 999999),
            'type' => 'qr', 'status' => 'assigned', 'vehicle_id' => $v->id,
        ]);
        $this->getJson("/api/v1/contact-requests/{$id}", $this->as($owner))->assertJsonPath('sender.badge', 'trusted');
    }

    public function test_the_sender_can_show_and_hide_their_name_and_nobody_else_can(): void
    {
        [$owner, $scanner, $id] = $this->appConversation();

        $this->putJson("/api/v1/contact-requests/{$id}/identity", ['visible' => true], $this->as($scanner))
            ->assertOk()->assertJsonPath('identity_visible', true);

        $r = $this->getJson("/api/v1/contact-requests/{$id}", $this->as($owner))->assertOk();
        $r->assertJsonPath('sender.identity_visible', true)->assertJsonPath('sender.name', $scanner->display_name)
            ->assertJsonPath('sender.phone', $scanner->phone);

        $this->putJson("/api/v1/contact-requests/{$id}/identity", ['visible' => false], $this->as($scanner))->assertOk();
        $hidden = $this->getJson("/api/v1/contact-requests/{$id}", $this->as($owner));
        $hidden->assertJsonPath('sender.name', null)->assertJsonPath('sender.phone', null)->assertJsonPath('sender.has_avatar', false);
        $this->assertStringNotContainsString($scanner->phone, $hidden->getContent());

        // The owner cannot change it for them.
        $this->putJson("/api/v1/contact-requests/{$id}/identity", ['visible' => true], $this->as($owner))->assertNotFound();
    }

    public function test_either_side_can_report_and_it_reaches_the_dashboard(): void
    {
        [$owner, $scanner, $id] = $this->appConversation();

        $this->postJson("/api/v1/contact-requests/{$id}/report", ['reason' => 'harassment', 'details' => 'يكرر الرسائل'], $this->as($owner))
            ->assertCreated();
        $this->postJson("/api/v1/contact-requests/{$id}/report", ['reason' => 'spam'], $this->as($scanner))
            ->assertCreated();
        $this->postJson("/api/v1/contact-requests/{$id}/report", ['reason' => 'nonsense'], $this->as($scanner))
            ->assertStatus(422);

        $byOwner = ConversationReport::where('reported_by_role', 'owner')->firstOrFail();
        $this->assertSame($scanner->id, $byOwner->reported_user_id);
        $byScanner = ConversationReport::where('reported_by_role', 'reporter')->firstOrFail();
        $this->assertSame($owner->id, $byScanner->reported_user_id);

        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->get('/admin/conversation-reports')->assertOk()
            ->assertSee('مضايقة')->assertSee('يكرر الرسائل');
        $this->actingAs($admin, 'admin')->post("/admin/conversation-reports/{$byOwner->id}/review")->assertSessionHasNoErrors();
        $this->assertSame('reviewed', $byOwner->fresh()->status);

        // A stranger cannot report a conversation they are not part of.
        $this->postJson("/api/v1/contact-requests/{$id}/report", ['reason' => 'spam'], $this->as(User::factory()->create()))->assertNotFound();
    }

    public function test_the_owner_can_report_a_web_visitor_too(): void
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->create(['user_id' => $owner->id]);
        $tag = Tag::create([
            'public_token' => strtoupper(bin2hex(random_bytes(8))), 'serial_number' => 'ST-Q-TEST-'.random_int(100000, 999999),
            'type' => 'qr', 'status' => 'assigned', 'vehicle_id' => $vehicle->id,
        ]);
        $thread = ContactRequest::create(['vehicle_id' => $vehicle->id, 'tag_id' => $tag->id, 'message' => 'x', 'request_ip' => '10.1.1.1']);

        $this->getJson("/api/v1/contact-requests/{$thread->uuid}", $this->as($owner))->assertJsonPath('sender.kind', 'web');
        $this->postJson("/api/v1/contact-requests/{$thread->uuid}/report", ['reason' => 'abuse'], $this->as($owner))->assertCreated();

        $this->assertNull(ConversationReport::firstOrFail()->reported_user_id);
    }

    public function test_the_photo_is_served_to_the_owner_only_while_the_identity_is_shown(): void
    {
        [$owner, $scanner, $id] = $this->appConversation();

        $path = 'avatars/test-'.uniqid().'.jpg';
        \Illuminate\Support\Facades\Storage::disk('local')->put($path, 'jpeg-bytes');
        $scanner->forceFill(['avatar_path' => $path])->save();

        // Hidden: no photo, nothing to fetch.
        $this->get("/api/v1/contact-requests/{$id}/sender-avatar", $this->as($owner))->assertNotFound();

        $this->putJson("/api/v1/contact-requests/{$id}/identity", ['visible' => true], $this->as($scanner))->assertOk();
        $this->getJson("/api/v1/contact-requests/{$id}", $this->as($owner))->assertJsonPath('sender.has_avatar', true);
        $this->get("/api/v1/contact-requests/{$id}/sender-avatar", $this->as($owner))->assertOk()->assertHeader('Content-Type', 'image/jpeg');

        // Nobody else, and not the sender through the owner's route.
        $this->get("/api/v1/contact-requests/{$id}/sender-avatar", $this->as(User::factory()->create()))->assertNotFound();
        $this->get("/api/v1/contact-requests/{$id}/sender-avatar", $this->as($scanner))->assertNotFound();

        \Illuminate\Support\Facades\Storage::disk('local')->delete($path);
    }
}