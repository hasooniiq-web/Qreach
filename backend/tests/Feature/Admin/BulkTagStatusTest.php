<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Role;
use App\Models\Tag;
use App\Models\TagBatch;
use App\Models\TagLifecycleEvent;
use App\Services\QrBatchService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Codes and batches that already exist can be moved along the printing path one at a
 * time or many together: created -> sent to printing -> unassigned (ready to link).
 */
class BulkTagStatusTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->withoutVite();

        $this->admin = Admin::factory()->create();
        $this->admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);
    }

    private function batch(int $quantity = 6): TagBatch
    {
        return app(QrBatchService::class)->createBatch($quantity, $this->admin->id);
    }

    private function statuses(TagBatch $batch): array
    {
        return $batch->tags()->orderBy('id')->pluck('status')->all();
    }

    public function test_one_code_can_be_sent_to_printing_on_its_own(): void
    {
        $batch = $this->batch(4);
        $one = $batch->tags()->orderBy('id')->first();

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.tag-batches.tags.bulk', $batch), ['ids' => [$one->id], 'action' => 'send_to_printing'])
            ->assertRedirect()->assertSessionHas('status');

        $this->assertSame(['printing', 'generated', 'generated', 'generated'], $this->statuses($batch));
        $this->assertSame(1, TagLifecycleEvent::where('tag_id', $one->id)->where('new_status', 'printing')->count());
        // Not every code has moved, so the batch itself is not "sent to printing" yet.
        $this->assertNotSame('printed', $batch->fresh()->status);
    }

    public function test_a_group_of_codes_moves_together_and_the_rest_stay(): void
    {
        $batch = $this->batch(6);
        $ids = $batch->tags()->orderBy('id')->pluck('id');

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.tag-batches.tags.bulk', $batch), ['ids' => $ids->take(3)->all(), 'action' => 'send_to_printing'])
            ->assertRedirect();

        $this->assertSame(['printing', 'printing', 'printing', 'generated', 'generated', 'generated'], $this->statuses($batch));
    }

    public function test_the_second_step_marks_printed_codes_ready_and_completes_the_batch_when_all_are_done(): void
    {
        $batch = $this->batch(3);
        $ids = $batch->tags()->orderBy('id')->pluck('id')->all();

        $this->actingAs($this->admin, 'admin')->post(route('admin.tag-batches.tags.bulk', $batch), ['ids' => $ids, 'action' => 'send_to_printing']);
        $this->assertSame('printed', $batch->fresh()->status);

        $this->actingAs($this->admin, 'admin')->post(route('admin.tag-batches.tags.bulk', $batch), ['ids' => [$ids[0]], 'action' => 'mark_unassigned']);
        $this->assertSame(['unassigned', 'printing', 'printing'], $this->statuses($batch));
        $this->assertSame('printed', $batch->fresh()->status, 'Two codes are still at the printer.');

        $this->actingAs($this->admin, 'admin')->post(route('admin.tag-batches.tags.bulk', $batch), ['ids' => [$ids[1], $ids[2]], 'action' => 'mark_unassigned']);
        $this->assertSame(['unassigned', 'unassigned', 'unassigned'], $this->statuses($batch));
        $this->assertSame('completed', $batch->fresh()->status);
    }

    public function test_a_mixed_selection_only_moves_what_the_step_allows(): void
    {
        $batch = $this->batch(4);
        $ids = $batch->tags()->orderBy('id')->pluck('id')->all();
        Tag::whereKey($ids[0])->update(['status' => 'printing']);
        Tag::whereKey($ids[1])->update(['status' => 'assigned']);

        // "Send to printing" on: printing, assigned, generated, generated -> only the two created ones move.
        $this->actingAs($this->admin, 'admin')->post(route('admin.tag-batches.tags.bulk', $batch), ['ids' => $ids, 'action' => 'send_to_printing']);

        $this->assertSame(['printing', 'assigned', 'printing', 'printing'], $this->statuses($batch));
    }

    public function test_a_code_cannot_skip_a_step_or_go_backwards(): void
    {
        $batch = $this->batch(2);
        $ids = $batch->tags()->orderBy('id')->pluck('id')->all();

        // "Ready" straight from "created" is refused: it has not been through printing.
        $this->actingAs($this->admin, 'admin')->post(route('admin.tag-batches.tags.bulk', $batch), ['ids' => $ids, 'action' => 'mark_unassigned']);
        $this->assertSame(['generated', 'generated'], $this->statuses($batch));
    }

    public function test_codes_of_another_batch_cannot_be_moved_from_this_batchs_page(): void
    {
        $mine = $this->batch(2);
        $other = $this->batch(2);
        $foreign = $other->tags()->pluck('id')->all();

        $this->actingAs($this->admin, 'admin')->post(route('admin.tag-batches.tags.bulk', $mine), ['ids' => $foreign, 'action' => 'send_to_printing']);

        $this->assertSame(['generated', 'generated'], $this->statuses($other));
    }

    public function test_several_batches_are_moved_at_once_from_the_list(): void
    {
        $a = $this->batch(3);
        $b = $this->batch(2);
        $c = $this->batch(4); // left out

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.tag-batches.bulk'), ['ids' => [$a->id, $b->id], 'action' => 'send_to_printing'])
            ->assertRedirect()->assertSessionHas('status');

        $this->assertSame(['printing', 'printing', 'printing'], $this->statuses($a));
        $this->assertSame(['printing', 'printing'], $this->statuses($b));
        $this->assertSame(['generated', 'generated', 'generated', 'generated'], $this->statuses($c));
        $this->assertSame('printed', $a->fresh()->status);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.tag-batches.bulk'), ['ids' => [$a->id, $b->id, $c->id], 'action' => 'mark_unassigned']);

        $this->assertSame(['unassigned', 'unassigned', 'unassigned'], $this->statuses($a));
        $this->assertSame('completed', $b->fresh()->status);
        $this->assertSame(['generated', 'generated', 'generated', 'generated'], $this->statuses($c), 'A created batch cannot jump to ready.');
    }

    public function test_codes_ticked_in_the_all_codes_list_move_too(): void
    {
        $a = $this->batch(2);
        $b = $this->batch(2);
        $ids = [$a->tags()->first()->id, $b->tags()->first()->id];

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.tags.bulk'), ['ids' => $ids, 'action' => 'send_to_printing'])
            ->assertRedirect()->assertSessionHas('status');

        $this->assertSame(2, Tag::whereIn('id', $ids)->where('status', 'printing')->count());
        $this->assertSame(2, Tag::where('status', 'printing')->count());
    }

    public function test_nothing_selected_is_refused_with_a_clear_message(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.tag-batches.bulk'), ['ids' => [], 'action' => 'send_to_printing'])
            ->assertSessionHasErrors('ids');

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.tag-batches.bulk'), ['ids' => [1], 'action' => 'delete_everything'])
            ->assertSessionHasErrors('action');
    }

    public function test_it_needs_the_printing_permission(): void
    {
        $batch = $this->batch(2);
        $support = Admin::factory()->create();
        $support->roles()->sync([Role::where('slug', 'support_admin')->value('id')]);

        $this->actingAs($support, 'admin')
            ->post(route('admin.tag-batches.bulk'), ['ids' => [$batch->id], 'action' => 'send_to_printing'])
            ->assertForbidden();

        $this->actingAs($support, 'admin')
            ->post(route('admin.tags.bulk'), ['ids' => $batch->tags()->pluck('id')->all(), 'action' => 'send_to_printing'])
            ->assertForbidden();

        $this->assertSame(['generated', 'generated'], $this->statuses($batch));
    }

    public function test_the_pages_show_checkboxes_and_the_bar_only_to_those_who_may_use_them(): void
    {
        $batch = $this->batch(3);

        $this->actingAs($this->admin, 'admin')->get('/admin/batches')
            ->assertOk()->assertSee('data-bulk-all', false)->assertSee('name="ids[]"', false)->assertSee('إرسال للطباعة');
        $this->actingAs($this->admin, 'admin')->get('/admin/batches/'.$batch->id)
            ->assertOk()->assertSee('data-bulk-item', false);
        $this->actingAs($this->admin, 'admin')->get('/admin/qr')
            ->assertOk()->assertSee('data-bulk-item', false);

        $viewer = Admin::factory()->create();
        $viewer->roles()->sync([Role::where('slug', 'viewer')->value('id')]);
        $this->actingAs($viewer, 'admin')->get('/admin/batches')->assertOk()->assertDontSee('data-bulk-all', false);
    }
}
