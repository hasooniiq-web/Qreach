<?php

namespace App\Services;

use App\Exceptions\TagActivationException;
use App\Models\Tag;
use App\Models\TagAssignment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

/**
 * Section 11: one-time QR/NFC activation. This is the single most
 * security-critical piece of SmartTag — every method here must be
 * concurrency-safe against simultaneous requests for the same tag.
 *
 * Safety comes from TWO independent layers, deliberately redundant:
 *   1. `lockForUpdate()` inside a transaction — serializes concurrent
 *      requests for the same row.
 *   2. The UPDATE itself is conditioned on `status = 'available'` and its
 *      affected-row count is checked — even if locking were ever bypassed
 *      (e.g. a future code path forgets it), a stale/duplicate activation
 *      cannot silently succeed.
 */
class TagLifecycleService
{
    /**
     * @throws TagActivationException
     */
    public function activate(string $publicToken, Vehicle $vehicle, User $actor): Tag
    {
        return DB::transaction(function () use ($publicToken, $vehicle, $actor) {
            $tag = Tag::where('public_token', $publicToken)->lockForUpdate()->first();

            if (! $tag) {
                throw new TagActivationException('الرمز غير صالح.', 'TAG_NOT_FOUND');
            }

            if ($tag->status === 'revoked' || $tag->status === 'disabled') {
                throw new TagActivationException('هذا الرمز تم إلغاؤه ولا يمكن استخدامه.', 'TAG_REVOKED');
            }

            if ($tag->status !== 'available' && $tag->status !== 'generated') {
                throw new TagActivationException('هذا الرمز مُفعّل بالفعل ولا يمكن ربطه مرة أخرى.', 'TAG_ALREADY_ACTIVATED');
            }

            if ($vehicle->user_id !== $actor->id) {
                throw new TagActivationException('لا يمكنك ربط رمز بمركبة لا تملكها.', 'VEHICLE_NOT_OWNED');
            }

            $affected = Tag::where('id', $tag->id)
                ->whereIn('status', ['available', 'generated'])
                ->update([
                    'status' => 'assigned',
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

            AuditLogger::log(
                'qr.activated', 'customer', $actor->id, 'Tag', $tag->id, 'success',
                ['vehicle_id' => $vehicle->id, 'tag_type' => $tag->type]
            );

            return $tag->fresh();
        });
    }

    /**
     * Explicit, admin-controlled revocation (section 11). Never implicit.
     */
    public function revoke(Tag $tag, string $reason, string $actorType, ?int $actorId): Tag
    {
        return DB::transaction(function () use ($tag, $reason, $actorType, $actorId) {
            $tag = Tag::where('id', $tag->id)->lockForUpdate()->first();

            if ($tag->status === 'revoked') {
                throw new TagActivationException('الرمز ملغى بالفعل.', 'TAG_ALREADY_REVOKED');
            }

            $previousVehicleId = $tag->vehicle_id;

            $tag->update([
                'status' => 'revoked',
                'revoked_at' => now(),
                'revocation_reason' => $reason,
            ]);

            if ($previousVehicleId) {
                TagAssignment::where('tag_id', $tag->id)->whereNull('unassigned_at')->update(['unassigned_at' => now()]);

                TagAssignment::create([
                    'tag_id' => $tag->id,
                    'vehicle_id' => $previousVehicleId,
                    'user_id' => Vehicle::find($previousVehicleId)?->user_id,
                    'event' => 'revoked',
                    'performed_by_type' => $actorType,
                    'performed_by_id' => $actorId,
                    'reason' => $reason,
                    'assigned_at' => now(),
                    'unassigned_at' => now(),
                ]);
            }

            AuditLogger::log('qr.revoked', $actorType, $actorId, 'Tag', $tag->id, 'success', ['reason' => $reason]);

            return $tag->fresh();
        });
    }

    /**
     * Replacement: revoke the old tag and atomically activate a fresh one
     * onto the same vehicle, preserving full history via tag_assignments.
     */
    public function replace(Tag $oldTag, Tag $newTag, string $reason, string $actorType, ?int $actorId): Tag
    {
        return DB::transaction(function () use ($oldTag, $newTag, $reason, $actorType, $actorId) {
            $oldTag = Tag::where('id', $oldTag->id)->lockForUpdate()->first();
            $newTag = Tag::where('id', $newTag->id)->lockForUpdate()->first();

            if (! $oldTag->vehicle_id) {
                throw new TagActivationException('لا يمكن استبدال رمز غير مرتبط بمركبة.', 'TAG_NOT_ASSIGNED');
            }

            if (! in_array($newTag->status, ['available', 'generated'], true)) {
                throw new TagActivationException('رمز الاستبدال غير متاح.', 'REPLACEMENT_TAG_UNAVAILABLE');
            }

            $vehicleId = $oldTag->vehicle_id;
            $userId = Vehicle::find($vehicleId)?->user_id;

            $oldTag->update([
                'status' => 'replaced',
                'revoked_at' => now(),
                'revocation_reason' => $reason,
                'replaced_by_tag_id' => $newTag->id,
            ]);

            $newTag->update([
                'status' => 'assigned',
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

            AuditLogger::log('qr.replaced', $actorType, $actorId, 'Tag', $newTag->id, 'success', [
                'old_tag_id' => $oldTag->id, 'reason' => $reason,
            ]);

            return $newTag->fresh();
        });
    }
}
