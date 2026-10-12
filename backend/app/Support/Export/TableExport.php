<?php

namespace App\Support\Export;

use Illuminate\Http\Response;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * One table, two files: the same title, filters, summary and rows become an Excel
 * workbook or a PDF in the dashboard's look (black and yellow, Noto Sans Arabic).
 */
final class TableExport
{
    /**
     * @param  list<string>  $headers
     * @param  list<list<scalar|null>>  $rows
     * @param  array<string, scalar>  $summary
     * @param  list<string>  $filters  human-readable filter lines ("الحالة: نشط")
     */
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly array $headers,
        public readonly array $rows,
        public readonly array $summary = [],
        public readonly array $filters = [],
        public readonly bool $truncated = false,
        public readonly int $total = 0,
        public readonly string $generatedBy = '',
    ) {}

    public function filename(string $ext): string
    {
        return $this->key.'-'.now()->timezone(config('app.display_timezone'))->format('Y-m-d_Hi').'.'.$ext;
    }

    /** @return list<string> */
    private function metaLines(): array
    {
        $lines = ['تاريخ التصدير: '.now()->timezone(config('app.display_timezone'))->format('Y-m-d H:i')
            .($this->generatedBy !== '' ? ' — بواسطة: '.$this->generatedBy : '')];
        $lines[] = 'عدد السجلات: '.number_format(count($this->rows)).($this->truncated ? ' من '.number_format($this->total).' (اقتُصر على الحد الأقصى)' : '');
        foreach ($this->filters as $f) {
            $lines[] = $f;
        }

        return $lines;
    }

    public function xlsx(): Response
    {
        $binary = XlsxWriter::build($this->title, $this->title, $this->metaLines(), $this->headers, $this->rows, $this->summary);

        return new Response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$this->filename('xlsx').'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function pdf(): Response
    {
        // Large tables are heavy to lay out; the cap in ExportsTables keeps this bounded,
        // the limit below only protects against a caller that forgot it.
        @ini_set('memory_limit', '512M');

        $landscape = count($this->headers) > 6;
        $tmp = storage_path('app/mpdf');
        if (! is_dir($tmp)) {
            @mkdir($tmp, 0775, true);
        }

        $fontDirs = (new ConfigVariables)->getDefaults()['fontDir'];
        $fontData = (new FontVariables)->getDefaults()['fontdata'];

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => $landscape ? 'A4-L' : 'A4',
            'tempDir' => $tmp,
            'fontDir' => array_merge($fontDirs, [resource_path('fonts')]),
            'fontdata' => $fontData + [
                // Shaping is done by mPDF's own Arabic engine (OTL tables of this font are not parsed).
                'notosansarabic' => ['R' => 'NotoSansArabic.ttf', 'B' => 'NotoSansArabic.ttf', 'useOTL' => 0],
            ],
            'default_font' => 'notosansarabic',
            'directionality' => 'rtl',
            'autoScriptToLang' => true,
            'autoLangToFont' => false,
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 12,
            'margin_bottom' => 14,
        ]);
        $mpdf->SetTitle($this->title.' — Qreach');
        $mpdf->SetAuthor('Qreach SmartTag');
        $mpdf->SetHTMLFooter('<div style="text-align:center;font-size:8pt;color:#71717A;">Qreach SmartTag — {PAGENO} / {nbpg}</div>');

        $html = view('exports.pdf', [
            'title' => $this->title,
            'headers' => $this->headers,
            'rows' => $this->rows,
            'summary' => $this->summary,
            'meta' => $this->metaLines(),
            'logo' => resource_path('brand/logo-pdf.png'),
            'truncated' => $this->truncated,
        ])->render();

        $mpdf->WriteHTML($html);

        return new Response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filename('pdf').'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
