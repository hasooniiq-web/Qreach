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
    public function __construct(
        private readonly QrBatchService $batches,
        private readonly TagLifecycleService $lifecycle,
    ) {}

    public function index(): View
    {
        $batches = TagBatch::withCount('tags')->latest()->paginate(20);

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

    public function show(TagBatch $tagBatch): View
    {
        $tags = $tagBatch->tags()->with('qrTag', 'nfcTag', 'vehicle.user')->paginate(50);

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

    /** Machine-readable export: every field needed to reprint/re-issue, no user/vehicle data. */
    public function exportCodes(Request $request, TagBatch $tagBatch): JsonResponse
    {
        AuditLogger::log('qr.batch_exported', 'admin', $request->user('admin')->id, 'TagBatch', $tagBatch->id, 'success', ['format' => 'codes']);

        $codes = $tagBatch->tags()->get(['serial_number', 'public_token', 'type', 'status'])
            ->map(fn ($tag) => [
                'serial_number' => $tag->serial_number,
                'public_token' => $tag->public_token,
                'public_url' => url('/t/'.$tag->public_token),
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
                    echo $tag->serial_number."\t".$base.'/t/'.$tag->public_token.PHP_EOL;
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
            fputcsv($handle, ['serial_number', 'public_token', 'public_url', 'status', 'type']);

            $tagBatch->tags()->chunk(200, function ($tags) use ($handle) {
                foreach ($tags as $tag) {
                    fputcsv($handle, [
                        $tag->serial_number,
                        $tag->public_token,
                        url('/t/'.$tag->public_token),
                        $tag->status,
                        $tag->type,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
