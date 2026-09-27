<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\TagBatch;
use App\Models\TagLifecycleEvent;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * QR batch generation (sections 10, 19). Runs inside a single transaction
 * so a batch is either fully generated or not created at all — no partial
 * batches with gaps in numbering.
 */
class QrBatchService
{
    public function __construct(private readonly TagTokenGenerator $tokens) {}

    public function createBatch(int $quantity, ?int $createdByAdminId, string $type = 'qr'): TagBatch
    {
        if ($quantity < 1 || $quantity > 5000) {
            throw new \InvalidArgumentException('Batch quantity must be between 1 and 5000.');
        }

        return DB::transaction(function () use ($quantity, $createdByAdminId, $type) {
            $batch = TagBatch::create([
                'batch_code' => $this->generateBatchCode($type),
                'tag_type' => $type,
                'quantity' => $quantity,
                'status' => 'pending',
                'created_by' => $createdByAdminId,
            ]);

            $sequenceStart = (Tag::max('id') ?? 0) + 1;

            for ($i = 0; $i < $quantity; $i++) {
                $tag = Tag::create([
                    'public_token' => $this->tokens->generatePublicToken(),
                    'serial_number' => $this->tokens->generateSerialNumber($type, $sequenceStart + $i),
                    'type' => $type,
                    'batch_id' => $batch->id,
                    'status' => 'generated',
                ]);

                if ($type === 'qr') {
                    $tag->qrTag()->create([
                        'error_correction_level' => 'H',
                        'print_sheet_position' => $i + 1,
                    ]);
                    $this->renderQrImages($tag);
                } else {
                    $tag->nfcTag()->create(['provisioning_status' => 'unprovisioned']);
                }
            }

            $batch->update(['status' => 'generated', 'generated_at' => now()]);

            // "Created" — the first entry in every tag's immutable lifecycle ledger.
            $now = now();
            $tagIds = $batch->tags()->pluck('id');
            foreach ($tagIds->chunk(500) as $chunk) {
                TagLifecycleEvent::insert($chunk->map(fn ($id) => [
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
     * Renders and stores PNG + SVG for the public scan URL. High error
     * correction ('H') per section 19 so a printed, slightly damaged/dirty
     * sticker still scans reliably.
     */
    private function renderQrImages(Tag $tag): void
    {
        $url = config('app.url').'/t/'.$tag->public_token;

        $png = (new Builder(writer: new PngWriter))->build(
            data: $url,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 600,
            margin: 16,
        );

        $svg = (new Builder(writer: new SvgWriter))->build(
            data: $url,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 600,
            margin: 16,
        );

        $pngPath = "qr/{$tag->serial_number}.png";
        $svgPath = "qr/{$tag->serial_number}.svg";

        Storage::disk('local')->put($pngPath, $png->getString());
        Storage::disk('local')->put($svgPath, $svg->getString());

        $tag->qrTag->update(['image_png_path' => $pngPath, 'image_svg_path' => $svgPath]);
    }

    private function generateBatchCode(string $type): string
    {
        $prefix = strtoupper($type);
        $sequence = TagBatch::where('tag_type', $type)->count() + 1;

        return sprintf('ST-%s-%s-%04d', $prefix, now()->format('Y'), $sequence);
    }
}
