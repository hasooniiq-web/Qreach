<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Role;
use App\Models\Tag;
use App\Services\QrBatchService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * NFC reuses the generic tag/batch admin UI (section 12: shared lifecycle
 * with QR) rather than a separate NFC-only controller. This locks in that
 * the reuse actually works end to end.
 */
class NfcTagManagementTest extends TestCase
{
    use RefreshDatabase;

    private function actingSuperAdmin(): Admin
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);

        return $admin;
    }

    public function test_admin_can_create_an_nfc_batch(): void
    {
        $admin = $this->actingSuperAdmin();

        $response = $this->actingAs($admin, 'admin')->post('/admin/batches', [
            'tag_type' => 'nfc',
            'quantity' => 5,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tag_batches', ['tag_type' => 'nfc', 'quantity' => 5]);
        $this->assertDatabaseCount('nfc_tags', 5);
        $this->assertDatabaseCount('qr_tags', 0);
    }

    public function test_nfc_tags_are_filterable_in_the_tag_list(): void
    {
        $admin = $this->actingSuperAdmin();
        app(QrBatchService::class)->createBatch(2, $admin->id, 'nfc');
        app(QrBatchService::class)->createBatch(2, $admin->id, 'qr');

        $response = $this->actingAs($admin, 'admin')->get('/admin/qr?type=nfc');

        $response->assertStatus(200);
        $response->assertViewHas('tags', fn ($tags) => $tags->total() === 2);
    }

    public function test_nfc_batch_detail_hides_the_print_action(): void
    {
        $admin = $this->actingSuperAdmin();
        $batch = app(QrBatchService::class)->createBatch(1, $admin->id, 'nfc');

        $response = $this->actingAs($admin, 'admin')->get("/admin/batches/{$batch->id}");

        $response->assertStatus(200)->assertDontSee('طباعة الرموز');
    }

    public function test_admin_can_view_a_tag_and_get_its_qr_image_for_both_types(): void
    {
        $admin = $this->actingSuperAdmin();
        config(['app.url' => 'https://qreach.example']);

        foreach (['qr', 'nfc'] as $type) {
            $tag = Tag::where('id', app(QrBatchService::class)->createBatch(1, $admin->id, $type)->tags()->value('id'))->first();

            $this->actingAs($admin, 'admin')->get("/admin/qr/{$tag->public_token}")
                ->assertOk()->assertSee("https://qreach.example/t/{$tag->public_token}");

            $this->actingAs($admin, 'admin')->get("/admin/qr/{$tag->public_token}/image.png")
                ->assertOk()->assertHeader('Content-Type', 'image/png');
        }
    }
}
