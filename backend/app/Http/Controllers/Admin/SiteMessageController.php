<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Messages visitors sent from the "Contact us" form on the public site. */
class SiteMessageController extends Controller
{
    use \App\Http\Controllers\Concerns\ExportsTables;

    public function index(Request $request): View|\Illuminate\Http\Response
    {
        $status = in_array($request->string('status')->toString(), ['new', 'read'], true)
            ? $request->string('status')->toString()
            : null;

        $query = SiteMessage::query()->when($status, fn ($q) => $q->where('status', $status))->latest('id');

        if ($export = $this->exportRows($request, 'site-messages', 'رسائل الموقع', ['الاسم', 'البريد', 'الهاتف', 'الرسالة', 'اللغة', 'الحالة', 'التاريخ'], $query,
            fn ($m) => [
                (string) $m->name,
                (string) ($m->email ?: '—'),
                (string) ($m->phone ?: '—'),
                (string) $m->message,
                (string) $m->locale,
                \App\Support\Export\Labels::status($m->status),
                \App\Support\Export\Labels::date($m->created_at, true),
            ],
            filters: $status ? ['الحالة: '.\App\Support\Export\Labels::status($status)] : [])) {
            return $export;
        }

        return view('admin.site-messages.index', [
            'messages' => $query->paginate(20)->withQueryString(),
            'newCount' => SiteMessage::where('status', 'new')->count(),
        ]);
    }

    public function markRead(SiteMessage $siteMessage): RedirectResponse
    {
        if ($siteMessage->status === 'new') {
            $siteMessage->update(['status' => 'read', 'read_at' => now()]);
        }

        return back()->with('status', 'عُلّمت الرسالة كمقروءة.');
    }
}
