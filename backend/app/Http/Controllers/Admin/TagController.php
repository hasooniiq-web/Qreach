<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Services\TagLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
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

    public function image(Tag $tag, string $format): Response
    {
        abort_unless(in_array($format, ['png', 'svg'], true), 404);
        abort_unless($tag->qrTag, 404);

        $path = $format === 'png' ? $tag->qrTag->image_png_path : $tag->qrTag->image_svg_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type' => $format === 'png' ? 'image/png' : 'image/svg+xml',
        ]);
    }
}
