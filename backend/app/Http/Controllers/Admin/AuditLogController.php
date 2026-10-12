<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only by design (section 21: audit_logs is append-only).
 */
class AuditLogController extends Controller
{
    use \App\Http\Controllers\Concerns\ExportsTables;

    public function index(Request $request): View|\Illuminate\Http\Response
    {
        $logs = AuditLog::query()
            ->when($request->filled('actor_type'), fn ($q) => $q->where('actor_type', $request->string('actor_type')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', '%'.$request->string('action').'%'))
            ->when($request->filled('result'), fn ($q) => $q->where('result', $request->string('result')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest();

        if ($export = $this->exportRows($request, 'audit-log', 'سجلات التدقيق', ['الإجراء', 'الجهة الفاعلة', 'المورد', 'النتيجة', 'عنوان IP', 'التاريخ'], $logs,
            fn ($l) => [
                (string) $l->action,
                trim($l->actor_type.' '.($l->actor_id ? '#'.$l->actor_id : '')),
                trim($l->resource_type.' '.($l->resource_id ? '#'.$l->resource_id : '')),
                \App\Support\Export\Labels::status($l->result),
                (string) $l->request_ip,
                \App\Support\Export\Labels::date($l->created_at, true),
            ],
            filters: $this->filterLines($request, ['actor_type' => 'الجهة', 'action' => 'الإجراء', 'result' => 'النتيجة', 'from' => 'من', 'to' => 'إلى']))) {
            return $export;
        }

        $logs = $logs->paginate(40)->withQueryString();

        return view('admin.audit.index', compact('logs'));
    }
}
