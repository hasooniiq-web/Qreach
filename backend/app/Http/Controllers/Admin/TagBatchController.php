<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTagBatchRequest;
use App\Models\TagBatch;
use App\Services\QrBatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TagBatchController extends Controller
{
    public function __construct(private readonly QrBatchService $batches) {}

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

    public function exportCsv(Request $request, TagBatch $tagBatch): StreamedResponse
    {
        \App\Services\AuditLogger::log(
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
