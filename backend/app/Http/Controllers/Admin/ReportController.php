<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactRequest;
use App\Models\Scan;
use App\Services\AuditLogger;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Two operational reports (scans, contact requests): a date range, a
 * filter or two, a real-count summary, a paginated table, and a CSV export.
 * Viewing needs reports.view; exporting needs reports.export, is throttled,
 * and is audit-logged. Exports deliberately omit IP addresses and user
 * agents — the report is for operations, not for tracing individual visitors.
 */
class ReportController extends Controller
{
    use \App\Http\Controllers\Concerns\ExportsTables;

    private const REPORTS = [
        'scans' => 'عمليات المسح',
        'contact_requests' => 'طلبات التواصل',
    ];

    private const ACTIONS = [
        'view' => 'عرض الصفحة',
        'vehicle_block' => 'إعاقة الطريق',
        'lights_on' => 'الأضواء مضاءة',
        'important_message' => 'رسالة مهمة',
        'emergency' => 'حالة طارئة',
        'contact_owner' => 'مراسلة المالك',
    ];

    public function index(Request $request): View|\Illuminate\Http\Response
    {
        [$report, $from, $to] = $this->resolve($request);
        $query = $this->query($report, $request, $from, $to);

        // Excel / PDF of exactly this report and range. Needs the export permission, like the CSV.
        if ($this->exportFormat($request) !== null) {
            abort_unless($request->user('admin')->hasPermission('reports.export'), 403);

            $labels = \App\Support\Export\Labels::class;
            $filters = ['الفترة: من '.$from->toDateString().' إلى '.$to->toDateString()]
                + $this->filterLines($request, ['action' => 'الإجراء', 'status' => 'الحالة'], ['action' => self::ACTIONS, 'status' => ['new' => 'جديد', 'read' => 'مقروء', 'responded' => 'تم الرد', 'closed' => 'مغلق']]);

            $summaryRows = $report === 'scans'
                ? (clone $query)->reorder()->selectRaw('action_type, count(*) as total')->groupBy('action_type')->pluck('total', 'action_type')
                : (clone $query)->reorder()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
            $summary = ['الإجمالي' => (clone $query)->count()];
            foreach ($summaryRows as $key => $count) {
                $summary[$report === 'scans' ? (self::ACTIONS[$key] ?? $key) : $labels::status($key)] = (int) $count;
            }

            return $this->exportRows($request, $report, 'تقرير '.self::REPORTS[$report], $report === 'scans'
                ? ['التاريخ', 'الإجراء', 'الحالة', 'اللوحة', 'الرقم التسلسلي للرمز']
                : ['التاريخ', 'الحالة', 'اللوحة', 'الرقم التسلسلي للرمز', 'الرسالة'], $query,
                fn ($row) => $report === 'scans' ? [
                    $row->created_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i'),
                    self::ACTIONS[$row->action_type] ?? $row->action_type,
                    $row->status === 'success' ? 'نجاح' : 'فشل',
                    $row->vehicle?->plate_number ?? '—',
                    $row->tag?->serial_number ?? '—',
                ] : [
                    $row->created_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i'),
                    $labels::status($row->status),
                    $row->vehicle?->plate_number ?? '—',
                    $row->tag?->serial_number ?? '—',
                    (string) $row->message,
                ],
                summary: $summary,
                filters: $filters,
            );
        }

        $summary = $report === 'scans'
            ? (clone $query)->reorder()->selectRaw('action_type, count(*) as total')->groupBy('action_type')->pluck('total', 'action_type')
            : (clone $query)->reorder()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.reports.index', [
            'report' => $report,
            'reports' => self::REPORTS,
            'actions' => self::ACTIONS,
            'summary' => $summary,
            'total' => (clone $query)->count(),
            'rows' => $query->paginate(25)->withQueryString(),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$report, $from, $to] = $this->resolve($request);
        $query = $this->query($report, $request, $from, $to);

        AuditLogger::log('report.exported', 'admin', $request->user('admin')->id, 'Report', null, 'success', [
            'report' => $report, 'from' => $from->toDateString(), 'to' => $to->toDateString(),
            'filters' => $request->only(['action', 'status']),
        ], $request);

        $filename = "{$report}-{$from->toDateString()}_{$to->toDateString()}.csv";

        return response()->streamDownload(function () use ($report, $query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads Arabic correctly
            fputcsv($out, $report === 'scans'
                ? ['التاريخ', 'الإجراء', 'الحالة', 'اللوحة', 'الرقم التسلسلي للرمز']
                : ['التاريخ', 'الحالة', 'اللوحة', 'الرقم التسلسلي للرمز', 'الرسالة']);

            $query->chunk(500, function ($chunk) use ($out, $report) {
                foreach ($chunk as $row) {
                    fputcsv($out, array_map([$this, 'safeCell'], $report === 'scans' ? [
                        $row->created_at->format('Y-m-d H:i'),
                        self::ACTIONS[$row->action_type] ?? $row->action_type,
                        $row->status,
                        $row->vehicle?->plate_number ?? '',
                        $row->tag?->serial_number ?? '',
                    ] : [
                        $row->created_at->format('Y-m-d H:i'),
                        $row->status,
                        $row->vehicle?->plate_number ?? '',
                        $row->tag?->serial_number ?? '',
                        $row->message,
                    ]));
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0: string, 1: Carbon, 2: Carbon} */
    private function resolve(Request $request): array
    {
        $report = array_key_exists($request->string('report')->toString(), self::REPORTS)
            ? $request->string('report')->toString()
            : 'scans';

        $parse = fn (string $key, Carbon $default) => $request->filled($key) && strtotime($request->string($key)->toString())
            ? Carbon::parse($request->string($key)->toString())
            : $default;

        $to = $parse('to', now())->endOfDay();
        $from = $parse('from', now()->subDays(29))->startOfDay();

        return [$report, $from->min($to), $to];
    }

    private function query(string $report, Request $request, Carbon $from, Carbon $to): Builder
    {
        if ($report === 'scans') {
            return Scan::query()->with(['vehicle', 'tag'])
                ->whereBetween('created_at', [$from, $to])
                ->when(array_key_exists($request->string('action')->toString(), self::ACTIONS), fn ($q) => $q->where('action_type', $request->string('action')->toString()))
                ->latest('id');
        }

        return ContactRequest::query()->with(['vehicle', 'tag'])
            ->whereBetween('created_at', [$from, $to])
            ->when(in_array($request->string('status')->toString(), ['new', 'read', 'responded', 'closed'], true), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->latest('id');
    }

    /** Neutralizes spreadsheet formula injection: a cell starting with = + - @ is prefixed with a quote. */
    private function safeCell(mixed $value): string
    {
        $text = (string) $value;

        return $text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$text : $text;
    }
}
