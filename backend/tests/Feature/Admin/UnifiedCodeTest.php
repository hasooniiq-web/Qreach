<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Role;
use App\Models\Tag;
use App\Models\TagBatch;
use App\Services\QrBatchService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** A code is now one thing: a QR for the sticker and an NFC record for the chip, same link. */
class UnifiedCodeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);

        return $admin;
    }

    public function test_the_dashboard_creates_unified_codes_by_default_with_both_records(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post('/admin/batches', ['quantity' => '12'])->assertSessionHasNoErrors();

        $batch = TagBatch::firstOrFail();
        $this->assertSame('code', $batch->tag_type);
        $this->assertSame(12, $batch->tags()->count());
        $this->assertSame(12, $batch->tags()->has('qrTag')->count());
        $this->assertSame(12, $batch->tags()->has('nfcTag')->count());

        $tag = $batch->tags()->first();
        $this->assertMatchesRegularExpression('/^ST-C\d{2}-[0-9A-HJKMNP-TV-Z]{6}-[0-9A-HJKMNP-TV-Z]$/', $tag->serial_number);
        $this->assertTrue(\App\Services\TagTokenGenerator::isValidSerial($tag->serial_number));
        $this->assertTrue($tag->hasQr() && $tag->hasNfc());
        $this->assertSame('QR + NFC', $tag->typeLabel());
        $this->assertSame('Q', $tag->qrTag->error_correction_level);
        $this->assertSame('unprovisioned', $tag->nfcTag->provisioning_status);
    }

    public function test_the_new_batch_form_offers_only_the_unified_code(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->get('/admin/batches/create')->assertOk()
            ->assertSee('رمز موحّد: QR + NFC')->assertSee('name="tag_type" value="code"', false);
    }

    public function test_a_unified_code_page_shows_the_qr_the_nfc_status_and_the_link(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $batch = app(QrBatchService::class)->createBatch(1, null);
        $tag = $batch->tags()->first();

        $this->get("/admin/qr/{$tag->public_token}")->assertOk()
            ->assertSee('QR + NFC')->assertSee('حالة الشريحة')->assertSee('لم تُكتب بعد')
            ->assertSee('/t/'.$tag->public_token);
        $this->get("/admin/qr/{$tag->public_token}/image.png")->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_a_unified_batch_can_be_printed_and_exported(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $batch = app(QrBatchService::class)->createBatch(3, null);

        $this->get("/admin/batches/{$batch->id}")->assertOk()->assertSee('طباعة الرموز');
        $this->get("/admin/batches/{$batch->id}/print")->assertOk();
        $csv = $this->get("/admin/batches/{$batch->id}/export")->assertOk()->streamedContent();
        $this->assertMatchesRegularExpression('/ST-C\d{2}-/', $csv);
        $this->assertStringContainsString('code', $csv);
    }

    public function test_the_list_filters_by_unified_and_still_shows_older_kinds(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $unified = app(QrBatchService::class)->createBatch(2, null, 'code');
        $legacy = app(QrBatchService::class)->createBatch(2, null, 'qr');

        $this->get('/admin/qr?type=code')->assertOk()
            ->assertSee($unified->tags()->first()->serial_number)->assertDontSee($legacy->tags()->first()->serial_number);
        $this->get('/admin/qr?type=qr')->assertOk()->assertSee($legacy->tags()->first()->serial_number);
        $this->get('/admin/qr')->assertOk()->assertSee('QR + NFC');
    }

    public function test_new_serials_use_the_year_random_payload_and_check_character_format(): void
    {
        $batch = app(QrBatchService::class)->createBatch(50, null, 'code');
        $serials = $batch->tags()->pluck('serial_number');

        $this->assertCount(50, $serials->unique());
        foreach ($serials as $serial) {
            $this->assertMatchesRegularExpression('/^ST-C'.now()->format('y').'-[0-9A-HJKMNP-TV-Z]{6}-[0-9A-HJKMNP-TV-Z]$/', $serial);
            $this->assertTrue(\App\Services\TagTokenGenerator::isValidSerial($serial), $serial);
        }

        // A mistyped character is caught by the check character.
        $good = $serials->first();
        $payload = $good[7];
        $wrong = $good;
        $wrong[7] = $payload === '2' ? '3' : '2';
        $this->assertFalse(\App\Services\TagTokenGenerator::isValidSerial($wrong));
        $this->assertFalse(\App\Services\TagTokenGenerator::isValidSerial('ST-C-20261010-000150'));
    }

    public function test_legacy_serial_formatting_is_kept_for_existing_rows(): void
    {
        $generator = new \App\Services\TagTokenGenerator;
        $this->assertSame('ST-C-'.now()->format('Ymd').'-000150', $generator->formatSerial('code', 150));
        $this->assertSame('ST-Q-'.now()->format('Y').'-000150', $generator->formatSerial('qr', 150));
    }

    public function test_the_text_export_has_the_serial_and_the_unique_address(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $batch = app(QrBatchService::class)->createBatch(2, null);
        $tag = $batch->tags()->first();

        $text = $this->get("/admin/batches/{$batch->id}/export/text")->assertOk()->streamedContent();
        $this->assertStringContainsString($tag->serial_number."\t".rtrim(config('app.url'), '/').'/t/'.$tag->public_token, $text);
    }

    public function test_older_single_purpose_batches_still_work(): void
    {
        $qr = app(QrBatchService::class)->createBatch(2, null, 'qr');
        $nfc = app(QrBatchService::class)->createBatch(2, null, 'nfc');

        $this->assertSame(2, $qr->tags()->has('qrTag')->count());
        $this->assertSame(0, $qr->tags()->has('nfcTag')->count());
        $this->assertSame(2, $nfc->tags()->has('nfcTag')->count());
        $this->assertMatchesRegularExpression('/^ST-N\d{2}-/', $nfc->tags()->first()->serial_number);
        $this->assertMatchesRegularExpression('/^ST-Q\d{2}-/', $qr->tags()->first()->serial_number);
    }

    public function test_an_unknown_type_is_rejected(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post('/admin/batches', ['quantity' => 5, 'tag_type' => 'rfid'])->assertSessionHasErrors('tag_type');
        $this->expectException(\InvalidArgumentException::class);
        app(QrBatchService::class)->createBatch(1, null, 'rfid');
    }

    public function test_a_unified_code_can_replace_an_older_one(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $old = app(QrBatchService::class)->createBatch(1, null, 'qr')->tags()->first();
        $new = app(QrBatchService::class)->createBatch(1, null, 'code')->tags()->first();
        $old->update(['status' => 'assigned', 'vehicle_id' => \App\Models\Vehicle::factory()->create()->id]);
        // Only a code that was sent to print can take over a vehicle.
        $new->update(['status' => 'unassigned']);

        $this->get("/admin/qr/{$old->public_token}")->assertOk()->assertSee($new->serial_number);
    }
}
