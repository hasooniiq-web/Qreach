<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\TagActivationException;
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
    use \App\Http\Controllers\Concerns\ExportsTables;

    public function __construct(private readonly TagLifecycleService $lifecycle) {}

    public function index(Request $request): View|\Illuminate\Http\Response
    {
        $tags = Tag::query()
            ->with('vehicle.user')
            ->when($request->filled('q'), fn ($q) => $q->where('serial_number', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->latest();

        if ($export = $this->exportRows($request, 'codes', 'رموز QR و NFC', ['الرقم التسلسلي', 'النوع', 'المركبة', 'الحالة', 'تاريخ الإنشاء'], $tags,
            fn ($t) => [
                (string) $t->serial_number,
                $t->typeLabel(),
                $t->vehicle?->plate_number ?? '—',
                \App\Support\Export\Labels::status($t->status),
                \App\Support\Export\Labels::date($t->created_at),
            ],
            filters: $this->filterLines($request, ['q' => 'بحث', 'type' => 'النوع', 'status' => 'الحالة'], ['type' => \App\Models\Tag::typeLabels()]))) {
            return $export;
        }

        $tags = $tags->paginate(30)->withQueryString();

        return view('admin.tags.index', compact('tags'));
    }

    public function revoke(Request $request, Tag $tag): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:255']]);

        try {
            $this->lifecycle->revoke($tag, $request->input('reason'), 'admin', $request->user('admin')->id);
        } catch (TagActivationException $e) {
            return back()->withErrors(['reason' => $e->getMessage()]);
        }

        return back()->with('status', 'تم إلغاء الرمز بنجاح.');
    }

    /**
     * Replacement workflow: pick an existing unassigned/generated tag by its
     * serial number and swap it in for this one. The old tag becomes
     * permanently unusable ("Replacement" status); the new one takes over the
     * same vehicle. Both stay linked for auditing.
     */
    public function replace(Request $request, Tag $tag): RedirectResponse
    {
        $data = $request->validate([
            'new_tag_serial' => ['required', 'string'],
            'reason' => ['required', 'string', 'max:255'],
        ], [], ['new_tag_serial' => 'الرقم التسلسلي للرمز الجديد']);

        $newTag = Tag::where('serial_number', $data['new_tag_serial'])->first();

        if (! $newTag) {
            return back()->withErrors(['new_tag_serial' => 'لا يوجد رمز بهذا الرقم التسلسلي.'])->withInput();
        }

        try {
            $this->lifecycle->replace($tag, $newTag, $data['reason'], 'admin', $request->user('admin')->id);
        } catch (TagActivationException $e) {
            return back()->withErrors(['new_tag_serial' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.tags.show', $newTag)->with('status', "تم استبدال الرمز {$tag->serial_number} بالرمز {$newTag->serial_number} بنجاح.");
    }

    public function show(Tag $tag): View
    {
        $tag->load('vehicle.user', 'qrTag', 'nfcTag', 'batch', 'lifecycleEvents', 'replaces');

        // A unified code may replace an older single-purpose one (and vice versa), so any unassigned code qualifies.
        $replacementCandidates = Tag::query()
            ->whereIn('status', Tag::ASSIGNABLE_STATUSES)
            ->where('id', '!=', $tag->id)
            ->orderByDesc('id')
            ->limit(50)
            ->pluck('serial_number');

        return view('admin.tags.show', [
            'tag' => $tag,
            'url' => $this->publicUrl($tag),
            'replacementCandidates' => $replacementCandidates,
        ]);
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
            errorCorrectionLevel: ErrorCorrectionLevel::Quartile,
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
