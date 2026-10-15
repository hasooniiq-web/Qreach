<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Notifications\SupportReplyNotification;
use App\Rules\PlainTextOnly;
use App\Services\AuditLogger;
use App\Services\MessageImageProcessor;
use App\Services\SupportContacts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** Technical-support tickets customers opened from the app, and the WhatsApp numbers shown there. */
class SupportController extends Controller
{
    public const CATEGORY_LABELS = [
        'general' => 'استفسار عام', 'account' => 'الحساب', 'vehicle' => 'المركبة والرمز',
        'payment' => 'الدفع والاشتراك', 'bug' => 'مشكلة في التطبيق', 'other' => 'أخرى',
    ];

    public const STATUS_LABELS = [
        'open' => 'بانتظار الدعم', 'answered' => 'تم الرد', 'closed' => 'مغلقة',
    ];

    public function index(Request $request): View
    {
        $status = array_key_exists($request->string('status')->toString(), self::STATUS_LABELS)
            ? $request->string('status')->toString() : null;

        $tickets = SupportTicket::query()->with('user')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByRaw("case status when 'open' then 0 when 'answered' then 1 else 2 end")
            ->orderByDesc('last_activity_at')
            ->paginate(20)->withQueryString();

        return view('admin.support.index', [
            'tickets' => $tickets,
            'openCount' => SupportTicket::where('status', 'open')->count(),
            'statusLabels' => self::STATUS_LABELS,
            'categoryLabels' => self::CATEGORY_LABELS,
        ]);
    }

    public function show(SupportTicket $supportTicket): View
    {
        return view('admin.support.show', [
            'ticket' => $supportTicket->load(['user', 'messages']),
            'statusLabels' => self::STATUS_LABELS,
            'categoryLabels' => self::CATEGORY_LABELS,
        ]);
    }

    public function reply(Request $request, SupportTicket $supportTicket): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:2000', new PlainTextOnly, 'required_without:image'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], [
            'body.required_without' => 'اكتب الرد أو أرفق صورة.',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $jpeg = app(MessageImageProcessor::class)->normalize($request->file('image'));
            if ($jpeg === null) {
                return back()->withErrors(['image' => 'تعذّرت معالجة الصورة. جرّب صورة أخرى.']);
            }
            $imagePath = 'support-images/'.Str::uuid().'.jpg';
            Storage::disk('local')->put($imagePath, $jpeg);
        }

        $body = trim((string) ($data['body'] ?? ''));
        $supportTicket->addMessage('staff', $body, $imagePath, $request->user('admin')->id);
        $supportTicket->update(['status' => 'answered', 'closed_at' => null]);

        $supportTicket->user?->notify(new SupportReplyNotification($supportTicket, $body !== '' ? $body : '📷 صورة'));

        AuditLogger::log('admin.support_replied', 'admin', $request->user('admin')->id, 'SupportTicket', $supportTicket->id, 'success', [], $request);

        return back()->with('status', 'أُرسل الرد للعميل.');
    }

    public function close(Request $request, SupportTicket $supportTicket): RedirectResponse
    {
        $supportTicket->update(['status' => 'closed', 'closed_at' => now()]);
        AuditLogger::log('admin.support_closed', 'admin', $request->user('admin')->id, 'SupportTicket', $supportTicket->id, 'success', [], $request);

        return back()->with('status', 'أُغلقت التذكرة.');
    }

    public function reopen(Request $request, SupportTicket $supportTicket): RedirectResponse
    {
        $supportTicket->update(['status' => 'open', 'closed_at' => null]);

        return back()->with('status', 'أُعيد فتح التذكرة.');
    }

    public function image(SupportTicket $supportTicket, int $message): Response
    {
        $msg = $supportTicket->messages()->whereKey($message)->firstOrFail();
        abort_unless($msg->image_path && Storage::disk('local')->exists($msg->image_path), 404);

        return Storage::disk('local')->response($msg->image_path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function settings(SupportContacts $contacts): View
    {
        $rows = $contacts->all();
        while (count($rows) < SupportContacts::MAX) {
            $rows[] = ['label' => '', 'phone' => '', 'url' => ''];
        }

        return view('admin.support.settings', ['rows' => $rows]);
    }

    public function saveSettings(Request $request, SupportContacts $contacts): RedirectResponse
    {
        $data = $request->validate([
            'numbers' => ['array', 'max:'.SupportContacts::MAX],
            'numbers.*.label' => ['nullable', 'string', 'max:40', new PlainTextOnly],
            'numbers.*.phone' => ['nullable', 'string', 'max:32'],
        ], [], ['numbers.*.label' => 'الاسم', 'numbers.*.phone' => 'الرقم']);

        // A typed number must be a real Iraqi number; an empty row is simply dropped.
        foreach ($data['numbers'] ?? [] as $i => $row) {
            $phone = trim((string) ($row['phone'] ?? ''));
            if ($phone !== '' && \App\Services\IraqiPhone::normalize($phone) === null) {
                return back()->withInput()->withErrors(["numbers.$i.phone" => 'رقم الهاتف غير صحيح.']);
            }
        }

        $contacts->save($data['numbers'] ?? [], $request->user('admin')->id);
        AuditLogger::log('admin.support_contacts_updated', 'admin', $request->user('admin')->id, 'SystemSetting', null, 'success', [], $request);

        return back()->with('status', 'حُفظت أرقام الدعم.');
    }
}
