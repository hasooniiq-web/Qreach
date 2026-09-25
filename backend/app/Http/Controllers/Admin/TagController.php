<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Services\TagLifecycleService;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TagController extends Controller
{
    public function __construct(private readonly TagLifecycleService $lifecycle) {}

    public function index(Request $request): View
    {
        $tags = Tag::query()
            ->with('vehicle.user')
            ->when($request->filled('q'), fn ($q) => $q->where('serial_number', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.tags.index', compact('tags'));
    }

    public function revoke(Request $request, Tag $tag): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $this->lifecycle->revoke($tag, $request->input('reason'), 'admin', $request->user('admin')->id);

        return back()->with('status', 'تم إلغاء الرمز بنجاح.');
    }

    public function show(Tag $tag): View
    {
        $tag->load('vehicle.user', 'qrTag', 'nfcTag', 'batch');

        return view('admin.tags.show', ['tag' => $tag, 'url' => $this->publicUrl($tag)]);
    }

    /**
     * Rendered from the current APP_URL on every request rather than read from the
     * file saved at batch time, so a sticker/NFC chip always encodes the address
     * the server is actually reachable at today (the stored file kept whatever
     * APP_URL was when the batch was made). Works for NFC tags too: their QR is
     * a printable/scan fallback for the same URL written to the chip.
     */
    public function image(Tag $tag, string $format): Response
    {
        abort_unless(in_array($format, ['png', 'svg'], true), 404);

        $writer = $format === 'png' ? new PngWriter : new SvgWriter;
        $image = (new Builder(writer: $writer))->build(
            data: $this->publicUrl($tag),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 600,
            margin: 16,
        );

        return response($image->getString(), 200, [
            'Content-Type' => $format === 'png' ? 'image/png' : 'image/svg+xml',
            'Cache-Control' => 'private, max-age=60',
        ]);
    }

    private function publicUrl(Tag $tag): string
    {
        return rtrim(config('app.url'), '/').'/t/'.$tag->public_token;
    }
}
