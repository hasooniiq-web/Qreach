<?php

namespace App\Http\Controllers\Concerns;

use App\Services\AuditLogger;
use App\Support\Export\TableExport;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Adds "export this list to Excel / PDF" to a dashboard page. The page's own
 * filtered query is reused, so the file always matches what the admin sees (all
 * pages of it, up to a cap). Each export is rate-limited per admin and audit-logged.
 *
 * Usage in an index() action, after the filters are applied and before paginate():
 *
 *     if ($export = $this->exportRows($request, 'users', 'المستخدمون', ['الاسم', ...],
 *             $query, fn ($u) => [$u->display_name, ...], filters: ['الحالة: نشط'])) {
 *         return $export;
 *     }
 */
trait ExportsTables
{
    private const EXPORT_XLSX_CAP = 20000;

    private const EXPORT_PDF_CAP = 1000;

    protected function exportFormat(Request $request): ?string
    {
        $format = $request->query('export');

        return in_array($format, ['xlsx', 'pdf'], true) ? $format : null;
    }

    /**
     * @param  list<string>  $headers
     * @param  EloquentBuilder|Relation  $query  already filtered, ordered, NOT paginated
     * @param  callable(mixed): list<scalar|null>  $row  one table row from one model
     * @param  array<string, scalar>  $summary
     * @param  list<string>  $filters
     */
    protected function exportRows(
        Request $request,
        string $key,
        string $title,
        array $headers,
        EloquentBuilder|Relation $query,
        callable $row,
        array $summary = [],
        array $filters = [],
    ): ?Response {
        $format = $this->exportFormat($request);
        if ($format === null) {
            return null;
        }

        $admin = $request->user('admin');
        $limiterKey = 'export:'.($admin?->id ?? $request->ip());
        abort_if(RateLimiter::tooManyAttempts($limiterKey, 15), 429, 'عدد مرات التصدير كبير. حاول بعد دقيقة.');
        RateLimiter::hit($limiterKey, 60);

        $cap = $format === 'pdf' ? self::EXPORT_PDF_CAP : self::EXPORT_XLSX_CAP;
        $models = (clone $query)->limit($cap + 1)->get();
        $truncated = $models->count() > $cap;
        if ($truncated) {
            $models = $models->take($cap);
        }
        $total = $truncated ? (clone $query)->toBase()->getCountForPagination() : $models->count();

        $export = new TableExport(
            key: $key,
            title: $title,
            headers: $headers,
            rows: $models->map(fn ($m) => array_values($row($m)))->all(),
            summary: $summary,
            filters: $filters,
            truncated: $truncated,
            total: $total,
            generatedBy: (string) ($admin?->name ?? ''),
        );

        AuditLogger::log('export.generated', 'admin', $admin?->id, 'Export', null, 'success', [
            'page' => $key, 'format' => $format, 'rows' => count($export->rows), 'filters' => $request->except(['page', 'export']),
        ], $request);

        return $format === 'pdf' ? $export->pdf() : $export->xlsx();
    }

    /** "label: value" lines for the filters that are actually set. */
    protected function filterLines(Request $request, array $labels, array $valueMaps = []): array
    {
        $lines = [];
        foreach ($labels as $param => $label) {
            $value = $request->query($param);
            if ($value === null || $value === '') {
                continue;
            }
            $shown = $valueMaps[$param][$value] ?? $value;
            $lines[] = $label.': '.$shown;
        }

        return $lines;
    }
}
