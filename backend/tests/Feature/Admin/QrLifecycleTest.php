<?php

namespace Tests\Feature\Admin;

use App\Exceptions\TagActivationException;
use App\Models\Admin;
use App\Models\Role;
use App\Models\Tag;
use App\Models\TagLifecycleEvent;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\QrBatchService;
use App\Services\TagLifecycleService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The QR/NFC lifecycle rebuild: printing workflow, replacement (admin UI +
 * service), the immutable history ledger, and that a cancelled/replaced
 * code can never become active again.
 */
class QrLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): Admin
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);

        return $admin;
    }

    public function test_a_new_batch_is_created_then_sent_to_printing_then_marked_unassigned(): void
    {
        $admin = $this->superAdmin();
        $batch = app(QrBatchService::class)->createBatch(3, $admin->id, 'qr');

        $this->assertTrue($batch->tags()->where('status', 'generated')->count() === 3);
        $this->assertSame(3, TagLifecycleEvent::where('new_status', 'generated')->count());

        $this->actingAs($admin, 'admin')->post(route('admin.tag-batches.send-to-printing', $batch))->assertRedirect();
        $this->assertSame(3, $batch->tags()->where('status', 'printing')->count());
        $this->assertSame('printed', $batch->fresh()->status);

        $this->actingAs($admin, 'admin')->post(route('admin.tag-batches.mark-unassigned', $batch))->assertRedirect();
        $this->assertSame(3, $batch->tags()->where('status', 'unassigned')->count());
        $this->assertSame('completed', $batch->fresh()->status);

        // Every transition left an immutable trail.
        $tag = $batch->tags()->first();
        // lifecycleEvents() orders newest-first.
        $this->assertSame(['unassigned', 'printing', 'generated'], $tag->lifecycleEvents()->pluck('new_status')->all());
    }

    public function test_an_unassigned_tag_can_be_activated_by_its_owner(): void
    {
        $admin = $this->superAdmin();
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user)->create();
        $batch = app(QrBatchService::class)->createBatch(1, $admin->id, 'qr');
        $tag = $batch->tags()->first();
        app(TagLifecycleService::class)->sendBatchToPrinting($batch, 'admin', $admin->id);
        app(TagLifecycleService::class)->markBatchUnassigned($batch, 'admin', $admin->id);

        $this->assertSame('unassigned', $tag->fresh()->status);

        $activated = app(TagLifecycleService::class)->activate($tag->public_token, $vehicle, $user);
        $this->assertSame('assigned', $activated->status);
    }

    public function test_admin_replaces_an_assigned_tag_and_the_old_one_is_never_reusable(): void
    {
        $admin = $this->superAdmin();
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user)->create();

        $oldTag = Tag::factory()->create(['status' => 'assigned', 'vehicle_id' => $vehicle->id, 'type' => 'qr']);
        $newTag = Tag::factory()->create(['status' => 'unassigned', 'type' => 'qr']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.tags.show', $oldTag))->assertOk()->assertSee($newTag->serial_number, false);

        $this->actingAs($admin, 'admin')->post(route('admin.tags.replace', $oldTag), [
            'new_tag_serial' => $newTag->serial_number,
            'reason' => 'الرمز القديم تالف',
        ])->assertRedirect(route('admin.tags.show', $newTag));

        $oldTag->refresh();
        $newTag->refresh();
        $this->assertSame('replaced', $oldTag->status);
        $this->assertSame($newTag->id, $oldTag->replaced_by_tag_id);
        $this->assertSame('assigned', $newTag->status);
        $this->assertSame($vehicle->id, $newTag->vehicle_id);

        // The old (replaced) code can never be assigned again.
        $this->expectExceptionMessage('تم استبدال هذا الرمز');
        app(TagLifecycleService::class)->activate($oldTag->public_token, Vehicle::factory()->for(User::factory()->create())->create(), $user);
    }

    public function test_a_cancelled_tag_can_never_be_reassigned_or_replaced_into(): void
    {
        $admin = $this->superAdmin();
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user)->create();
        $tag = Tag::factory()->create(['status' => 'assigned', 'vehicle_id' => $vehicle->id]);

        $this->actingAs($admin, 'admin')->post(route('admin.tags.revoke', $tag), ['reason' => 'مفقود'])->assertRedirect();
        $this->assertSame('revoked', $tag->fresh()->status);

        $other = Tag::factory()->create(['status' => 'assigned', 'vehicle_id' => Vehicle::factory()->for(User::factory()->create())->create()->id]);
        $this->expectException(TagActivationException::class);
        app(TagLifecycleService::class)->replace($other, $tag, 'x', 'admin', $admin->id);
    }

    public function test_the_tag_show_page_lists_its_immutable_lifecycle_history(): void
    {
        $admin = $this->superAdmin();
        $batch = app(QrBatchService::class)->createBatch(1, $admin->id, 'qr');
        $tag = $batch->tags()->first();

        $this->actingAs($admin, 'admin')->get(route('admin.tags.show', $tag))
            ->assertOk()->assertSee('سجلّ دورة حياة الرمز')->assertSee('أُنشئ');
    }

    public function test_printer_role_can_export_but_cannot_see_the_users_list(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = Admin::factory()->create();
        $admin->roles()->sync([Role::where('slug', 'printer')->value('id')]);
        $batch = app(QrBatchService::class)->createBatch(1, Admin::factory()->create()->id, 'qr');

        $this->actingAs($admin, 'admin')->get(route('admin.tag-batches.export.text', $batch))->assertOk();
        $this->actingAs($admin, 'admin')->get(route('admin.users.index'))->assertForbidden();
    }
}
