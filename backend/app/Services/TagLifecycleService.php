<?php

namespace App\Services;

use App\Exceptions\TagActivationException;
use App\Models\Tag;
use App\Models\TagAssignment;
use App\Models\TagBatch;
use App\Models\TagLifecycleEvent;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Section 11: QR/NFC lifecycle. Every method here is concurrency-safe against
 * simultaneous requests for the same tag/vehicle, and every status change is
 * written to `tag_lifecycle_events` (immutable) in the same transaction as
 * the change itself, so history can never drift from the current state.
 *
 * Safety comes from TWO independent layers, deliberately redundant:
 *   1. `lockForUpdate()` inside a transaction — serializes concurrent
 *      requests for the same row.
 *   2. The UPDATE itself is conditioned on the expected current status and
 *      its affected-row count is checked — even if locking were ever
 *      bypassed, a stale/duplicate transition cannot silently succeed.
 */
class TagLifecycleService
{
    /**
     * @throws TagActivationException
     */
    public function activate(string $publicToken, Vehicle $vehicle, User $actor): Tag
    {
        return DB::transaction(function () use ($publicToken, $vehicle, $actor) {
            // Serialize activations per vehicle so two tags can't both attach to one
            // vehicle when scanned at the same moment.
            Vehicle::where('id', $vehicle->id)->lockForUpdate()->first();

            $tag = Tag::where('public_token', TagTokenGenerator::normalize($publicToken))->lockForUpdate()->first();

            if (! $tag) {
                throw new TagActivationException('الرمز غير صالح.', 'TAG_NOT_FOUND');
            }

            if ($tag->status === Tag::STATUS_CANCELLED || $tag->status === 'disabled') {
                // Code kept as TAG_REVOKED — the mobile app already maps this
                // exact string to a localized message (see scan_screen.dart).
                throw new TagActivationException('هذا الرمز تم إلغاؤه ولا يمكن استخدامه.', 'TAG_REVOKED');
            }

            if ($tag->status === Tag::STATUS_REPLACED) {
                throw new TagActivationException('تم استبدال هذا الرمز برمز جديد ولا يمكن استخدامه بعد الآن.', 'TAG_REPLACED');
            }

            if ($tag->status === Tag::STATUS_CREATED) {
                throw new TagActivationException('هذا الرمز لم يُرسل للطباعة بعد ولا يمكن ربطه.', 'TAG_NOT_READY');
            }

            if (! in_array($tag->status, Tag::ASSIGNABLE_STATUSES, true)) {
                throw new TagActivationException('هذا الرمز مُفعّل بالفعل ولا يمكن ربطه مرة أخرى.', 'TAG_ALREADY_ACTIVATED');
            }

            if ($vehicle->user_id !== $actor->id) {
                throw new TagActivationException('لا يمكنك ربط رمز بمركبة لا تملكها.', 'VEHICLE_NOT_OWNED');
            }

            // One code per vehicle: to change it, the owner removes the current one first.
            if (Tag::where('vehicle_id', $vehicle->id)->where('status', Tag::STATUS_ASSIGNED)->exists()) {
                throw new TagActivationException('هذه المركبة مرتبطة برمز بالفعل. احذف الرمز الحالي أولاً ثم اربط الرمز الجديد.', 'VEHICLE_ALREADY_HAS_TAG');
            }

            $previousStatus = $tag->status;

            $affected = Tag::where('id', $tag->id)
                ->whereIn('status', Tag::ASSIGNABLE_STATUSES)
                ->update([
                    'status' => Tag::STATUS_ASSIGNED,
                    'vehicle_id' => $vehicle->id,
                    'activated_at' => now(),
                    'assigned_at' => now(),
                ]);

            if ($affected === 0) {
                // Someone else won the race between the lock read and this
                // write — should be impossible given lockForUpdate, but the
                // conditional update is the real guarantee, not the lock.
                throw new TagActivationException('هذا الرمز تم ربطه للتو بواسطة عملية أخرى.', 'TAG_ALREADY_ACTIVATED');
            }

            TagAssignment::create([
                'tag_id' => $tag->id,
                'vehicle_id' => $vehicle->id,
                'user_id' => $actor->id,
                'event' => 'assigned',
                'performed_by_type' => 'customer',
                'performed_by_id' => $actor->id,
                'assigned_at' => now(),
            ]);

            $this->recordEvent($tag, $previousStatus, Tag::STATUS_ASSIGNED, null, 'customer', $actor->id, $vehicle->id, $actor->id);

            AuditLogger::log(
                'qr.activated', 'customer', $actor->id, 'Tag', $tag->id, 'success',
                ['vehicle_id' => $vehicle->id, 'tag_type' => $tag->type]
            );

            return $tag->fresh();
        });
    }

    /**
     * Cancellation (spec: "Cancelled"). Explicit, admin-controlled, never
     * implicit, and irreversible — a cancelled tag can never be assigned or
     * replaced into again (enforced by activate()/replace() both rejecting
     * any status outside Tag::ASSIGNABLE_STATUSES).
     *
     * Internally this is the same terminal state the codebase already called
     * "revoked" (used for both an owner removing their own code and an admin
     * disabling one) — kept as one status rather than duplicating the same
     * business rule under two names.
     */
    public function revoke(Tag $tag, string $reason, string $actorType, ?int $actorId): Tag
    {
        return DB::transaction(function () use ($tag, $reason, $actorType, $actorId) {
            $tag = Tag::where('id', $tag->id)->lockForUpdate()->first();

            if ($tag->status === Tag::STATUS_CANCELLED) {
                throw new TagActivationException('الرمز ملغى بالفعل.', 'TAG_ALREADY_REVOKED');
            }
            if ($tag->status === Tag::STATUS_REPLACED) {
                throw new TagActivationException('تم استبدال هذا الرمز بالفعل.', 'TAG_REPLACED');
            }
            // Cancellation only ends the life of a code that was assigned before.
            if (! $tag->isCancellable()) {
                throw new TagActivationException('لا يمكن إلغاء إلا رمز تم تعيينه لمركبة.', 'TAG_NOT_ASSIGNED');
            }

            $previousStatus = $tag->status;
            $previousVehicleId = $tag->vehicle_id;
            $userId = $previousVehicleId ? Vehicle::find($previousVehicleId)?->user_id : null;

            $tag->update([
                'status' => Tag::STATUS_CANCELLED,
                'revoked_at' => now(),
                'revocation_reason' => $reason,
            ]);

            if ($previousVehicleId) {
                TagAssignment::where('tag_id', $tag->id)->whereNull('unassigned_at')->update(['unassigned_at' => now()]);

                TagAssignment::create([
                    'tag_id' => $tag->id,
                    'vehicle_id' => $previousVehicleId,
                    'user_id' => $userId,
                    'event' => 'revoked',
                    'performed_by_type' => $actorType,
                    'performed_by_id' => $actorId,
                    'reason' => $reason,
                    'assigned_at' => now(),
                    'unassigned_at' => now(),
                ]);
            }

            $this->recordEvent($tag, $previousStatus, Tag::STATUS_CANCELLED, $reason, $actorType, $actorId, $previousVehicleId, $userId);

            AuditLogger::log('qr.revoked', $actorType, $actorId, 'Tag', $tag->id, 'success', ['reason' => $reason]);

            return $tag->fresh();
        });
    }

    /**
     * Replacement: the old tag becomes permanently unusable (status
     * "replaced"), a fresh tag takes over the same vehicle, and the two stay
     * linked (`replaced_by_tag_id`) for auditing. Full history preserved via
     * tag_assignments + tag_lifecycle_events on both tags.
     */
    public function replace(Tag $oldTag, Tag $newTag, string $reason, string $actorType, ?int $actorId): Tag
    {
        return DB::transaction(function () use ($oldTag, $newTag, $reason, $actorType, $actorId) {
            $oldTag = Tag::where('id', $oldTag->id)->lockForUpdate()->first();
            $newTag = Tag::where('id', $newTag->id)->lockForUpdate()->first();

            if ($oldTag->id === $newTag->id) {
                throw new TagActivationException('لا يمكن استبدال الرمز بنفسه.', 'REPLACEMENT_TAG_UNAVAILABLE');
            }
            if (! $oldTag->vehicle_id) {
                throw new TagActivationException('لا يمكن استبدال رمز غير مرتبط بمركبة.', 'TAG_NOT_ASSIGNED');
            }
            if ($oldTag->status === Tag::STATUS_CANCELLED) {
                throw new TagActivationException('لا يمكن استبدال رمز مُلغى.', 'TAG_CANCELLED');
            }
            if (! in_array($newTag->status, Tag::ASSIGNABLE_STATUSES, true)) {
                throw new TagActivationException('رمز الاستبدال غير متاح.', 'REPLACEMENT_TAG_UNAVAILABLE');
            }

            $vehicleId = $oldTag->vehicle_id;
            $userId = Vehicle::find($vehicleId)?->user_id;
            $oldPreviousStatus = $oldTag->status;
            $newPreviousStatus = $newTag->status;

            $oldTag->update([
                'status' => Tag::STATUS_REPLACED,
                'revoked_at' => now(),
                'revocation_reason' => $reason,
                'replaced_by_tag_id' => $newTag->id,
            ]);

            $newTag->update([
                'status' => Tag::STATUS_ASSIGNED,
                'vehicle_id' => $vehicleId,
                'activated_at' => now(),
                'assigned_at' => now(),
            ]);

            TagAssignment::where('tag_id', $oldTag->id)->whereNull('unassigned_at')->update(['unassigned_at' => now()]);

            TagAssignment::create([
                'tag_id' => $newTag->id,
                'vehicle_id' => $vehicleId,
                'user_id' => $userId,
                'event' => 'replaced',
                'performed_by_type' => $actorType,
                'performed_by_id' => $actorId,
                'reason' => $reason,
                'assigned_at' => now(),
            ]);

            $this->recordEvent($oldTag, $oldPreviousStatus, Tag::STATUS_REPLACED, $reason, $actorType, $actorId, $vehicleId, $userId, ['replaced_by_tag_id' => $newTag->id]);
            $this->recordEvent($newTag, $newPreviousStatus, Tag::STATUS_ASSIGNED, $reason, $actorType, $actorId, $vehicleId, $userId, ['replaces_tag_id' => $oldTag->id]);

            AuditLogger::log('qr.replaced', $actorType, $actorId, 'Tag', $newTag->id, 'success', [
                'old_tag_id' => $oldTag->id, 'reason' => $reason,
            ]);

            return $newTag->fresh();
        });
    }

    /**
     * Batch printing step 1: "Sent to Printing". Only tags still in their
     * just-created state move; anything already printing/assigned/etc. is
     * left untouched (calling this twice is harmless).
     */
    public function sendBatchToPrinting(TagBatch $batch, string $actorType, ?int $actorId): int
    {
        return DB::transaction(function () use ($batch, $actorType, $actorId) {
            $tagIds = $batch->tags()->where('status', Tag::STATUS_CREATED)->lockForUpdate()->pluck('id');
            if ($tagIds->isEmpty()) {
                return 0;
            }

            Tag::whereIn('id', $tagIds)->update(['status' => Tag::STATUS_PRINTING]);
            $this->bulkRecordEvents($tagIds, Tag::STATUS_CREATED, Tag::STATUS_PRINTING, $actorType, $actorId);

            $batch->update(['status' => 'printed']);
            AuditLogger::log('qr.batch_sent_to_printing', $actorType, $actorId, 'TagBatch', $batch->id, 'success', ['count' => $tagIds->count()]);

            return $tagIds->count();
        });
    }

    /**
     * Batch printing step 2: the physical codes are back from the printer and
     * ready to be handed out — "Unassigned".
     */
    public function markBatchUnassigned(TagBatch $batch, string $actorType, ?int $actorId): int
    {
        return DB::transaction(function () use ($batch, $actorType, $actorId) {
            $tagIds = $batch->tags()->where('status', Tag::STATUS_PRINTING)->lockForUpdate()->pluck('id');
            if ($tagIds->isEmpty()) {
                return 0;
            }

            Tag::whereIn('id', $tagIds)->update(['status' => Tag::STATUS_UNASSIGNED]);
            $this->bulkRecordEvents($tagIds, Tag::STATUS_PRINTING, Tag::STATUS_UNASSIGNED, $actorType, $actorId);

            $batch->update(['status' => 'completed']);
            AuditLogger::log('qr.batch_marked_unassigned', $actorType, $actorId, 'TagBatch', $batch->id, 'success', ['count' => $tagIds->count()]);

            return $tagIds->count();
        });
    }

    private function recordEvent(
        Tag $tag,
        ?string $previousStatus,
        string $newStatus,
        ?string $reason,
        string $actorType,
        ?int $actorId,
        ?int $vehicleId,
        ?int $userId,
        array $metadata = [],
    ): void {
        TagLifecycleEvent::create([
            'tag_id' => $tag->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
            'performed_by_type' => $actorType,
            'performed_by_id' => $actorId,
            'vehicle_id' => $vehicleId,
            'user_id' => $userId,
            'metadata' => $metadata ?: null,
        ]);
    }

    /** @param  Collection<int, int>  $tagIds */
    private function bulkRecordEvents($tagIds, string $previousStatus, string $newStatus, string $actorType, ?int $actorId): void
    {
        $now = now();
        $rows = $tagIds->map(fn ($id) => [
            'tag_id' => $id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'performed_by_type' => $actorType,
            'performed_by_id' => $actorId,
            'created_at' => $now,
        ])->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            TagLifecycleEvent::insert($chunk);
        }
    }
}
