<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConversationReport;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Complaints customers filed from a conversation in the app (harassment, spam, abuse...). */
class ConversationReportController extends Controller
{
    public const REASON_LABELS = [
        'harassment' => 'مضايقة', 'spam' => 'إزعاج متكرر', 'abuse' => 'إساءة أو ألفاظ نابية',
        'scam' => 'احتيال', 'other' => 'سبب آخر',
    ];

    public function index(Request $request): View
    {
        $status = in_array($request->string('status')->toString(), ['new', 'reviewed'], true)
            ? $request->string('status')->toString() : null;

        $reports = ConversationReport::query()
            ->with(['filedBy', 'reportedUser', 'conversation.vehicle'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByRaw("case status when 'new' then 0 else 1 end")
            ->latest('id')
            ->paginate(20)->withQueryString();

        return view('admin.conversation-reports.index', [
            'reports' => $reports,
            'reasonLabels' => self::REASON_LABELS,
        ]);
    }

    public function review(Request $request, ConversationReport $conversationReport): RedirectResponse
    {
        if ($conversationReport->status === 'new') {
            $conversationReport->update(['status' => 'reviewed', 'reviewed_at' => now()]);
            AuditLogger::log('admin.conversation_report_reviewed', 'admin', $request->user('admin')->id, 'ConversationReport', $conversationReport->id, 'success', [], $request);
        }

        return back()->with('status', 'عُلّم البلاغ كمُراجَع.');
    }
}
