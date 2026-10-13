<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTagBatchRequest;
use App\Models\TagBatch;
use App\Services\AuditLogger;
use App\Services\QrBatchService;
use App\Services\TagLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TagBatchController extends Controller
{
    use \App\Http\Controllers\Concerns\ExportsTables;

    public function __construct(
        private readonly QrBatchService $batches,
        private readonly TagLifecycleService $lifecycle,
    ) {}

    public function index(Request $request): View|\Illuminate\Http\Response
    {
        $query = TagBatch::withCount('tags')->latest();

        if ($export = $this->exportRows($request, 'batches', 'دفعات الرموز', ['رمز الدفعة', 'النوع', 'الكمية', 'ملاحظات', 'الحالة', 'تاريخ الإنشاء'], $query,
            fn ($b) => [
                (string) $b->batch_code,
                \App\Models\Tag::typeLabels()[$b->tag_type] ?? strtoupper((string) $b->tag_type),
                (int) $b->quantity,
                (string) ($b->notes ?: '—'),
                \App\Support\Export\Labels::status($b->status),
                \App\Support\Export\Labels::date($b->created_at, true),
            ])) {
            return $export;
        }

        $batches = $query->paginate(20);

        return view('admin.tag-batches.index', compact('batches'));
    }

    public function create(): View
    {
        return view('admin.tag-batches.create');
    }

    public function store(StoreTagBatchRequest $request): RedirectResponse
    {
        $admin = $request->user('admin');

        $batch = $this->batches->createBatch(
            $request->integer('quantity'),
            $admin->id,
            $request->input('tag_type')
        );

        if ($request->filled('notes')) {
            $batch->update(['notes' => $request->input('notes')]);
        }

        return redirect()->route('admin.tag-batches.show', $batch)->with('status', 'تم إنشاء الدفعة بنجاح.');
    }

    public function show(Request $request, TagBatch $tagBatch): View|\Illuminate\Http\Response
    {
        $query = $tagBatch->tags()->with('qrTag', 'nfcTag', 'vehicle.user')->orderBy('id');

        // The list carries each code's secret address, so it needs the same permission as the other exports.
        if ($request->query('export')
            && ! $request->user('admin')->hasPermission('printing.export')
            && ! $request->user('admin')->hasPermission('qr.export')) {
            abort(403);
        }

        if ($export = $this->exportRows($request, 'batch-'.$tagBatch->batch_code, 'دفعة الرموز '.$tagBatch->batch_code, ['الرقم التسلسلي', 'الرابط الفريد (QR)', 'رابط شريحة NFC', 'الحالة', 'المركبة'], $query,
            fn ($t) => [
                (string) $t->serial_number,
                rtrim(config('app.url'), '/').'/t/'.$t->public_token,
                $t->nfcUrl(),
                \App\Support\Export\Labels::status($t->status),
                $t->vehicle?->plate_number ?? '—',
            ],
            filters: [$tagBatch->quantity.' رمز — '.(\App\Models\Tag::typeLabels()[$tagBatch->tag_type] ?? $tagBatch->tag_type)])) {
            return $export;
        }

        $tags = $query->paginate(50);

        return view('admin.tag-batches.show', ['batch' => $tagBatch, 'tags' => $tags]);
    }

    public function print(TagBatch $tagBatch): View
    {
        $tags = $tagBatch->tags()->with('qrTag')->get();

        return view('admin.tag-batches.print', ['batch' => $tagBatch, 'tags' => $tags]);
    }

    /** Lifecycle step 1: "Sent to Printing" — bulk, idempotent. */
    public function sendToPrinting(Request $request, TagBatch $tagBatch): RedirectResponse
    {
        $count = $this->lifecycle->sendBatchToPrinting($tagBatch, 'admin', $request->user('admin')->id);

        return back()->with('status', $count > 0
            ? "تم تحويل {$count} رمزًا إلى حالة «أُرسل للطباعة»."
            : 'لا توجد رموز في حالة «تم الإنشاء» لتحويلها.');
    }

    /** Lifecycle step 2: codes are back from the printer and ready to hand out — "Unassigned". */
    public function markUnassigned(Request $request, TagBatch $tagBatch): RedirectResponse
    {
        $count = $this->lifecycle->markBatchUnassigned($tagBatch, 'admin', $request->user('admin')->id);

        return back()->with('status', $count > 0
            ? "تم تحويل {$count} رمزًا إلى حالة «غير مُعيّن» — جاهزة للربط الآن."
            : 'لا توجد رموز في حالة «أُرسل للطباعة» لتحويلها.');
    }

    /** Several batches at once: send them all to printing, or mark them all as back from the printer. */
    public function bulkBatches(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer', 'exists:tag_batches,id'],
            'action' => ['required', 'in:send_to_printing,mark_unassigned'],
        ], [
            'ids.required' => 'حدّد دفعة واحدة على الأقل.',
            'ids.min' => 'حدّد دفعة واحدة على الأقل.',
        ]);

        $actorId = $request->user('admin')->id;
        $moved = 0;
        $batchesTouched = 0;

        foreach (TagBatch::whereIn('id', $data['ids'])->get() as $batch) {
            $count = $data['action'] === 'send_to_printing'
                ? $this->lifecycle->sendBatchToPrinting($batch, 'admin', $actorId)
                : $this->lifecycle->markBatchUnassigned($batch, 'admin', $actorId);

            if ($count > 0) {
                $moved += $count;
                $batchesTouched++;
            }
        }

        return back()->with('status', $this->bulkBatchesMessage($data['action'], $batchesTouched, count($data['ids']), $moved));
    }

    /** Several codes of this batch (or one): the same two steps, on exactly the codes ticked. */
    public function bulkTags(Request $request, TagBatch $tagBatch): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:1000'],
            'ids.*' => ['integer'],
            'action' => ['required', 'in:send_to_printing,mark_unassigned'],
        ], [
            'ids.required' => 'حدّد رمزًا واحدًا على الأقل.',
            'ids.min' => 'حدّد رمزًا واحدًا على الأقل.',
        ]);

        // Only codes that really belong to this batch can be moved from its page.
        $ids = \App\Models\Tag::where('batch_id', $tagBatch->id)->whereIn('id', $data['ids'])->pluck('id');

        [$from, $to] = $data['action'] === 'send_to_printing'
            ? [\App\Models\Tag::STATUS_CREATED, \App\Models\Tag::STATUS_PRINTING]
            : [\App\Models\Tag::STATUS_PRINTING, \App\Models\Tag::STATUS_UNASSIGNED];

        $moved = $this->lifecycle->advanceTags($ids, $from, $to, 'admin', $request->user('admin')->id);

        return back()->with('status', $this->bulkCodesMessage($data['action'], $moved, count($data['ids'])));
    }

    private function bulkBatchesMessage(string $action, int $touched, int $selected, int $moved): string
    {
        $state = $action === 'send_to_printing' ? 'أُرسل للطباعة' : 'غير مُعيّن (جاهز للربط)';

        return $moved > 0
            ? "تم تحويل {$moved} رمزًا في {$touched} من {$selected} دفعة إلى «{$state}»."
            : 'لم يتغير شيء: لا توجد رموز مؤهلة لهذه الخطوة في الدفعات المحددة.';
    }

    private function bulkCodesMessage(string $action, int $moved, int $selected): string
    {
        $state = $action === 'send_to_printing' ? 'أُرسل للطباعة' : 'غير مُعيّن (جاهز للربط)';

        if ($moved === 0) {
            return 'لم يتغير شيء: حالة الرموز المحددة لا تسمح بهذه الخطوة.';
        }

        $left = $selected - $moved;

        return "تم تحويل {$moved} رمزًا إلى «{$state}»."
            .($left > 0 ? " وتُرك {$left} لأن حالته لا تسمح بهذه الخطوة." : '');
    }

    /** Machine-readable export: every field needed to reprint/re-issue, no user/vehicle data. */
    public function exportCodes(Request $request, TagBatch $tagBatch): JsonResponse
    {
        AuditLogger::log('qr.batch_exported', 'admin', $request->user('admin')->id, 'TagBatch', $tagBatch->id, 'success', ['format' => 'codes']);

        $codes = $tagBatch->tags()->get(['serial_number', 'public_token', 'type', 'status', 'qr_enabled', 'nfc_enabled'])
            ->map(fn ($tag) => [
                'serial_number' => $tag->serial_number,
                'public_token' => $tag->public_token,
                'public_url' => url('/t/'.$tag->public_token),
                // Write THIS address on the NFC chip (it carries the signature that marks it as NFC).
                'nfc_url' => $tag->nfcUrl(),
                'type' => $tag->type,
                'status' => $tag->status,
            ]);

        return response()->json(['batch' => $tagBatch->batch_code, 'count' => $codes->count(), 'codes' => $codes])
            ->header('Content-Disposition', "attachment; filename=\"{$tagBatch->batch_code}.json\"");
    }

    /**
     * Plain-text export: one code per line — the serial number, then the unique
     * address that goes into the QR/NFC chip, tab-separated (opens cleanly in a
     * spreadsheet and is trivial to split in a print shop's own tooling).
     */
    public function exportText(Request $request, TagBatch $tagBatch): StreamedResponse
    {
        AuditLogger::log('qr.batch_exported', 'admin', $request->user('admin')->id, 'TagBatch', $tagBatch->id, 'success', ['format' => 'text']);

        $filename = "{$tagBatch->batch_code}.txt";

        return response()->streamDownload(function () use ($tagBatch) {
            $base = rtrim(config('app.url'), '/');

            $tagBatch->tags()->orderBy('id')->chunk(500, function ($tags) use ($base) {
                foreach ($tags as $tag) {
                    // serial <TAB> QR address <TAB> NFC chip address
                    echo $tag->serial_number."\t".$base.'/t/'.$tag->public_token."\t".$tag->nfcUrl().PHP_EOL;
                }
            });
        }, $filename, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function exportCsv(Request $request, TagBatch $tagBatch): StreamedResponse
    {
        AuditLogger::log(
            'qr.batch_exported', 'admin', $request->user('admin')->id, 'TagBatch', $tagBatch->id, 'success', [], $request
        );

        $filename = "{$tagBatch->batch_code}.csv";

        return response()->streamDownload(function () use ($tagBatch) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['serial_number', 'public_token', 'public_url', 'nfc_url', 'status', 'type']);

            $tagBatch->tags()->chunk(200, function ($tags) use ($handle) {
                foreach ($tags as $tag) {
                    fputcsv($handle, [
                        $tag->serial_number,
                        $tag->public_token,
                        url('/t/'.$tag->public_token),
                        $tag->nfcUrl(),
                        $tag->status,
                        $tag->type,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
