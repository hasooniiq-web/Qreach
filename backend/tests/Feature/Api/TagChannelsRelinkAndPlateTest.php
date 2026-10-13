<?php

namespace Tests\Feature\Api;

use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\QrBatchService;
use App\Services\TagLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * QR / NFC switched on or off per code, re-linking the code that was removed last,
 * and the plate rules (one letter, at most six digits).
 */
class TagChannelsRelinkAndPlateTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Vehicle $vehicle;

    private Tag $tag;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->withoutVite();

        $this->owner = User::factory()->create();
        $this->vehicle = Vehicle::factory()->create(['user_id' => $this->owner->id, 'plate_number' => '12345']);

        $batch = app(QrBatchService::class)->createBatch(3, null);
        app(TagLifecycleService::class)->sendBatchToPrinting($batch, 'system', null);
        app(TagLifecycleService::class)->markBatchUnassigned($batch, 'system', null);

        $this->tag = $batch->tags()->orderBy('id')->first();
        app(TagLifecycleService::class)->activate($this->tag->public_token, $this->vehicle, $this->owner);
        $this->tag = $this->tag->fresh();
    }

    private function scan(string $query = '')
    {
        return $this->get('/t/'.$this->tag->public_token.$query);
    }

    // ---- QR / NFC ---------------------------------------------------------------------------

    public function test_both_channels_are_on_by_default(): void
    {
        $this->assertTrue($this->tag->qr_enabled);
        $this->assertTrue($this->tag->nfc_enabled);

        $this->scan()->assertOk()->assertDontSee('هذا الرمز غير صالح');
        $this->scan(strstr($this->tag->nfcUrl(), '?'))->assertOk();
    }

    public function test_switching_qr_off_keeps_the_nfc_chip_working(): void
    {
        $this->actingAs($this->owner, 'sanctum')
            ->putJson("/api/v1/vehicles/{$this->vehicle->uuid}/tag/channels", ['qr_enabled' => false, 'nfc_enabled' => true])
            ->assertOk()->assertJsonPath('data.qr_enabled', false)->assertJsonPath('data.nfc_enabled', true);

        // The printed QR (the plain address) no longer answers ...
        $this->scan()->assertOk()->assertSee('غير صالح');
        // ... the NFC chip (the signed address) still does.
        $this->scan(strstr($this->tag->nfcUrl(), '?'))->assertOk()->assertDontSee('غير صالح');
    }

    public function test_a_photographed_qr_cannot_pretend_to_be_the_chip(): void
    {
        $this->tag->update(['qr_enabled' => false]);

        // Adding ?c=n is not enough: the chip's address carries a signature only the server can make.
        $this->scan('?c=n')->assertSee('غير صالح');
        $this->scan('?c=n&s=0000000000000000')->assertSee('غير صالح');
        $this->scan('?c=n&s='.$this->tag->nfcSignature())->assertDontSee('غير صالح');
    }

    public function test_switching_nfc_off_keeps_the_qr(): void
    {
        $this->tag->update(['nfc_enabled' => false]);

        $this->scan()->assertDontSee('غير صالح');
        $this->scan(strstr($this->tag->nfcUrl(), '?'))->assertSee('غير صالح');
    }

    public function test_with_both_off_the_code_does_not_answer_at_all(): void
    {
        $this->tag->update(['qr_enabled' => false, 'nfc_enabled' => false]);

        $this->scan()->assertSee('غير صالح');
        $this->scan(strstr($this->tag->nfcUrl(), '?'))->assertSee('غير صالح');
    }

    public function test_actions_are_refused_without_having_opened_the_page_through_an_allowed_channel(): void
    {
        $this->tag->update(['qr_enabled' => false]);
        $payload = ['reason' => 'blocking'];

        // Straight to the endpoint with only the token from a photographed QR: refused.
        $this->post('/t/'.$this->tag->public_token.'/contact', $payload)->assertNotFound();

        // After opening the page through the chip, the same session may act.
        $this->scan(strstr($this->tag->nfcUrl(), '?'))->assertOk();
        $this->post('/t/'.$this->tag->public_token.'/contact', $payload)->assertRedirect();
    }

    public function test_the_owner_needs_a_code_to_change_channels_and_cannot_touch_anothers(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'sanctum')
            ->putJson("/api/v1/vehicles/{$this->vehicle->uuid}/tag/channels", ['qr_enabled' => false, 'nfc_enabled' => true])
            ->assertForbidden();

        $bare = Vehicle::factory()->create(['user_id' => $this->owner->id]);
        $this->actingAs($this->owner, 'sanctum')
            ->putJson("/api/v1/vehicles/{$bare->uuid}/tag/channels", ['qr_enabled' => true, 'nfc_enabled' => true])
            ->assertStatus(404)->assertJsonPath('error.code', 'NO_TAG');
    }

    public function test_the_chips_address_is_in_every_export(): void
    {
        $admin = \App\Models\Admin::factory()->create();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin->roles()->sync([\App\Models\Role::where('slug', 'super_admin')->value('id')]);
        $batch = $this->tag->batch;

        $json = $this->actingAs($admin, 'admin')->get("/admin/batches/{$batch->id}/export/codes")->assertOk()->json('codes.0');
        $this->assertStringContainsString('?c=n&s=', $json['nfc_url']);
        $this->assertStringNotContainsString('?c=n', $json['public_url']);

        $text = $this->get("/admin/batches/{$batch->id}/export/text")->streamedContent();
        $this->assertStringContainsString("\t".rtrim(config('app.url'), '/').'/t/'.$this->tag->public_token."\t", $text);
        $this->assertStringContainsString('?c=n&s=', $text);
    }

    // ---- Re-linking the removed code ---------------------------------------------------------------

    public function test_the_removed_code_is_offered_and_can_be_linked_again(): void
    {
        $this->actingAs($this->owner, 'sanctum')->deleteJson("/api/v1/vehicles/{$this->vehicle->uuid}/tag")->assertOk();

        $this->getJson("/api/v1/vehicles/{$this->vehicle->uuid}")
            ->assertOk()
            ->assertJsonPath('data.previous_tag.serial_number', $this->tag->serial_number);

        $this->postJson("/api/v1/vehicles/{$this->vehicle->uuid}/tag/relink")
            ->assertOk()->assertJsonPath('data.status', 'assigned')->assertJsonPath('data.token', $this->tag->public_token);

        $this->assertSame('assigned', $this->tag->fresh()->status);
        $this->scan()->assertOk()->assertDontSee('غير صالح');

        // Nothing left to offer once it is linked.
        $this->getJson("/api/v1/vehicles/{$this->vehicle->uuid}")->assertJsonPath('data.previous_tag', null);
    }

    public function test_only_the_last_removed_code_comes_back(): void
    {
        $first = $this->tag;
        $this->actingAs($this->owner, 'sanctum')->deleteJson("/api/v1/vehicles/{$this->vehicle->uuid}/tag")->assertOk();

        $second = Tag::where('batch_id', $first->batch_id)->where('id', '!=', $first->id)->first();
        app(TagLifecycleService::class)->activate($second->public_token, $this->vehicle, $this->owner);
        $this->travel(1)->minutes();
        $this->deleteJson("/api/v1/vehicles/{$this->vehicle->uuid}/tag")->assertOk();

        $this->postJson("/api/v1/vehicles/{$this->vehicle->uuid}/tag/relink")->assertOk()->assertJsonPath('data.token', $second->public_token);
        $this->assertSame('revoked', $first->fresh()->status);
    }

    public function test_a_code_cancelled_by_staff_is_never_offered(): void
    {
        app(TagLifecycleService::class)->revoke($this->tag, 'lost and cancelled by support', 'admin', null);

        $this->actingAs($this->owner, 'sanctum')
            ->getJson("/api/v1/vehicles/{$this->vehicle->uuid}")->assertJsonPath('data.previous_tag', null);
        $this->postJson("/api/v1/vehicles/{$this->vehicle->uuid}/tag/relink")
            ->assertStatus(422)->assertJsonPath('error.code', 'NO_PREVIOUS_TAG');
    }

    public function test_relinking_is_refused_while_the_vehicle_has_a_code_or_belongs_to_someone_else(): void
    {
        $this->actingAs($this->owner, 'sanctum')->postJson("/api/v1/vehicles/{$this->vehicle->uuid}/tag/relink")
            ->assertStatus(422)->assertJsonPath('error.code', 'VEHICLE_ALREADY_HAS_TAG');

        $this->deleteJson("/api/v1/vehicles/{$this->vehicle->uuid}/tag")->assertOk();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson("/api/v1/vehicles/{$this->vehicle->uuid}/tag/relink")->assertForbidden();
    }

    // ---- Plate ------------------------------------------------------------------------------------

    private function vehiclePayload(array $overrides = []): array
    {
        return array_merge([
            'plate_number' => '65600', 'plate_letter' => 'B', 'plate_governorate' => 'كربلاء',
            'make' => 'Kia', 'model' => 'Optima', 'year' => 2023, 'color' => 'white', 'vehicle_type' => 'sedan',
        ], $overrides);
    }

    public function test_the_plate_has_one_letter_and_at_most_six_digits(): void
    {
        $this->actingAs($this->owner, 'sanctum');

        $this->postJson('/api/v1/vehicles', $this->vehiclePayload())->assertCreated();
        $this->postJson('/api/v1/vehicles', $this->vehiclePayload(['plate_number' => '123456', 'plate_letter' => 'أ']))->assertCreated();

        $this->postJson('/api/v1/vehicles', $this->vehiclePayload(['plate_letter' => 'ddxB']))->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['plate_letter']]]);
        $this->postJson('/api/v1/vehicles', $this->vehiclePayload(['plate_letter' => 'AB']))->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['plate_letter']]]);
        $this->postJson('/api/v1/vehicles', $this->vehiclePayload(['plate_number' => '1234567']))->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['plate_number']]]);
        $this->postJson('/api/v1/vehicles', $this->vehiclePayload(['plate_number' => '12A45']))->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['plate_number']]]);
    }

    public function test_an_older_vehicle_with_a_longer_letter_can_still_be_edited(): void
    {
        $old = Vehicle::factory()->create(['user_id' => $this->owner->id, 'plate_number' => '9876543', 'plate_letter' => 'ABC']);

        // Sent back unchanged while editing something else: accepted.
        $this->actingAs($this->owner, 'sanctum')
            ->putJson("/api/v1/vehicles/{$old->uuid}", ['plate_number' => '9876543', 'plate_letter' => 'ABC', 'color' => 'blue'])
            ->assertOk();

        // But a new value must follow the rule.
        $this->putJson("/api/v1/vehicles/{$old->uuid}", ['plate_letter' => 'XY'])->assertStatus(422)->assertJsonStructure(['error' => ['fields' => ['plate_letter']]]);
    }
}
