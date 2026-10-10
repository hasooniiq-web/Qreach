<?php

namespace Tests\Feature\Admin;

use App\Models\Tag;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\QrBatchService;
use App\Services\TagTokenGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTokenFormatTest extends TestCase
{
    use RefreshDatabase;

    public function test_tokens_are_26_chars_of_digits_and_capitals_and_never_start_with_a_formula_character(): void
    {
        $generator = new TagTokenGenerator;

        foreach (range(1, 3000) as $_) {
            $token = $generator->generatePublicToken();

            $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $token);
            $this->assertNotContains($token[0], ['=', '+', '-', '@', '_']);
        }
    }

    public function test_generate_many_returns_unique_tokens(): void
    {
        $tokens = (new TagTokenGenerator)->generateMany(2000);

        $this->assertCount(2000, $tokens);
        $this->assertCount(2000, array_unique($tokens));
    }

    public function test_a_large_batch_is_created_in_bulk_with_a_qr_row_per_tag(): void
    {
        $started = microtime(true);
        $batch = app(QrBatchService::class)->createBatch(3000, null, 'qr');
        $seconds = microtime(true) - $started;

        $this->assertSame(3000, $batch->tags()->count());
        $this->assertSame(3000, $batch->tags()->has('qrTag')->count());
        $this->assertSame(3000, $batch->tags()->pluck('public_token')->unique()->count());
        $this->assertSame('Q', $batch->tags()->first()->qrTag->error_correction_level);
        $this->assertLessThan(30, $seconds, 'bulk generation must not take per-tag time');
    }

    public function test_nfc_batches_get_nfc_rows(): void
    {
        $batch = app(QrBatchService::class)->createBatch(5, null, 'nfc');

        $this->assertSame(5, $batch->tags()->has('nfcTag')->count());
    }

    public function test_the_batch_ceiling_is_20000(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(QrBatchService::class)->createBatch(QrBatchService::MAX_QUANTITY + 1, null);
    }

    public function test_a_hand_typed_lowercase_token_with_look_alike_characters_still_resolves(): void
    {
        $this->assertSame('0123ABCD0123ABCD0123ABCD11', TagTokenGenerator::normalize('o123abcdO123ABCDo123abcdl1'));
        // Older 43-char tokens are case-sensitive and are left exactly as given.
        $legacy = '-K_Vof2ehNdUL-rjUi8PmD3w3UyZinK8WDmhedvE';
        $this->assertSame($legacy, TagTokenGenerator::normalize($legacy));

        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->for($owner)->create();
        $token = (new TagTokenGenerator)->generatePublicToken();
        Tag::create(['public_token' => $token, 'serial_number' => 'ST-Q-T-1', 'type' => 'qr', 'status' => 'assigned', 'vehicle_id' => $vehicle->id]);

        $this->get('/t/'.strtolower($token))->assertOk()->assertDontSee('هذا الرمز غير صالح');
    }

    public function test_an_old_43_character_token_keeps_working(): void
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->for($owner)->create(['nickname' => 'قديمة']);
        $legacy = '-K_Vof2ehNdUL-rjUi8PmD3w3UyZinK8WDmhedvE';
        Tag::create(['public_token' => $legacy, 'serial_number' => 'ST-Q-T-2', 'type' => 'qr', 'status' => 'assigned', 'vehicle_id' => $vehicle->id]);

        $this->get('/t/'.$legacy)->assertOk()->assertSee('قديمة');
    }
}
