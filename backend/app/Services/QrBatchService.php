<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\TagBatch;
use App\Models\TagLifecycleEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * QR / NFC batch generation (sections 10, 19). Runs inside a single transaction
 * so a batch is either fully generated or not created at all — no partial
 * batches with gaps in numbering.
 *
 * Rows are written in bulk, 500 at a time. The QR *image* is not rendered here:
 * it is drawn on demand from the current APP_URL (Admin\TagController::image),
 * so creating a batch of thousands of codes is a few seconds of inserts rather
 * than thousands of image renders — which is what used to force a low cap.
 */
class QrBatchService
{
    /** Hard ceiling per batch; the admin-editable setting (system settings) can only go up to this. */
    public const MAX_QUANTITY = 20000;

    /** Error-correction level stored with each QR code (Q = ~25% recoverable damage). */
    public const ERROR_CORRECTION = 'Q';

    private const CHUNK = 500;

    public function __construct(private readonly TagTokenGenerator $tokens) {}

    /** `code` = a unified code (QR + NFC); `qr` / `nfc` remain for the older single-purpose batches. */
    public const TYPES = ['code', 'qr', 'nfc'];

    public function createBatch(int $quantity, ?int $createdByAdminId, string $type = 'code'): TagBatch
    {
        if (! in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException('Unknown code type.');
        }

        if ($quantity < 1 || $quantity > self::MAX_QUANTITY) {
            throw new \InvalidArgumentException('Batch quantity must be between 1 and '.self::MAX_QUANTITY.'.');
        }

        return DB::transaction(function () use ($quantity, $createdByAdminId, $type) {
            $batch = TagBatch::create([
                'batch_code' => $this->generateBatchCode($type),
                'tag_type' => $type,
                'quantity' => $quantity,
                'status' => 'pending',
                'created_by' => $createdByAdminId,
            ]);

            $sequence = (Tag::max('id') ?? 0) + 1;
            $created = 0;

            foreach (array_chunk(range(1, $quantity), self::CHUNK) as $positions) {
                $this->insertChunk($batch, $type, $positions, $sequence + $created);
                $created += count($positions);
            }

            $batch->update(['status' => 'generated', 'generated_at' => now()]);

            // "Created" — the first entry in every tag's immutable lifecycle ledger.
            $now = now();
            foreach ($batch->tags()->pluck('id')->chunk(self::CHUNK) as $ids) {
                TagLifecycleEvent::insert($ids->map(fn ($id) => [
                    'tag_id' => $id,
                    'previous_status' => null,
                    'new_status' => 'generated',
                    'performed_by_type' => 'admin',
                    'performed_by_id' => $createdByAdminId,
                    'created_at' => $now,
                ])->all());
            }

            AuditLogger::log(
                'qr.batch_generated', 'admin', $createdByAdminId, 'TagBatch', $batch->id,
                'success', ['quantity' => $quantity, 'type' => $type]
            );

            return $batch->fresh();
        });
    }

    /**
     * @param  list<int>  $positions  1-based positions of this chunk inside the batch
     */
    private function insertChunk(TagBatch $batch, string $type, array $positions, int $firstSequence): void
    {
        $count = count($positions);
        $tokens = $this->tokens->generateMany($count);
        $now = now();

        // Serial numbers are sequential; skip past any that already exist (checked once per chunk).
        do {
            $serials = [];
            foreach ($positions as $i => $_) {
                $serials[] = $this->tokens->formatSerial($type, $firstSequence + $i);
            }
            $clash = Tag::whereIn('serial_number', $serials)->exists();
            if ($clash) {
                $firstSequence += $count;
            }
        } while ($clash);

        $rows = [];
        foreach ($positions as $i => $_) {
            $rows[] = [
                'uuid' => (string) Str::uuid(),
                'public_token' => $tokens[$i],
                'serial_number' => $serials[$i],
                'type' => $type,
                'batch_id' => $batch->id,
                'status' => 'generated',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        Tag::insert($rows);

        $ids = Tag::whereIn('serial_number', $serials)->pluck('id', 'serial_number');

        // Capabilities decide the child rows: a QR record for anything printable, an NFC record
        // for anything a chip can be written for. A unified code gets both.
        $qrRows = [];
        $nfcRows = [];
        foreach ($positions as $i => $position) {
            $tagId = $ids[$serials[$i]];
            if (in_array($type, ['code', 'qr'], true)) {
                $qrRows[] = ['tag_id' => $tagId, 'error_correction_level' => self::ERROR_CORRECTION, 'print_sheet_position' => $position, 'created_at' => $now, 'updated_at' => $now];
            }
            if (in_array($type, ['code', 'nfc'], true)) {
                $nfcRows[] = ['tag_id' => $tagId, 'provisioning_status' => 'unprovisioned', 'created_at' => $now, 'updated_at' => $now];
            }
        }

        if ($qrRows) {
            DB::table('qr_tags')->insert($qrRows);
        }
        if ($nfcRows) {
            DB::table('nfc_tags')->insert($nfcRows);
        }
    }

    private function generateBatchCode(string $type): string
    {
        $prefix = strtoupper($type);
        $sequence = TagBatch::where('tag_type', $type)->count() + 1;

        return sprintf('ST-%s-%s-%04d', $prefix, now()->format('Ymd'), $sequence);
    }
}
