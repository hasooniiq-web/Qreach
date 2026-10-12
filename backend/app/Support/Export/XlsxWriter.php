<?php

namespace App\Support\Export;

use RuntimeException;
use ZipArchive;

/**
 * A small, dependency-free .xlsx writer for dashboard exports: one right-to-left
 * sheet with a title block, a branded header row (black with yellow text, like the
 * dashboard), zebra rows, thin borders, frozen header, filter buttons and column
 * widths. Text is written as inline strings, so a cell that looks like a formula
 * (=, +, -, @) is never evaluated by Excel.
 */
final class XlsxWriter
{
    // cellXfs indexes (see styles()).
    private const S_TITLE = 1;
    private const S_META = 2;
    private const S_HEADER = 3;
    private const S_TEXT = 4;
    private const S_TEXT_ALT = 5;
    private const S_NUM = 6;
    private const S_NUM_ALT = 7;
    private const S_KPI_LABEL = 8;
    private const S_KPI_VALUE = 9;
    private const S_LTR = 10;
    private const S_LTR_ALT = 11;

    /**
     * @param  list<string>  $metaLines  small grey lines under the title (date, filters, count)
     * @param  list<string>  $headers
     * @param  list<list<scalar|null>>  $rows
     * @param  array<string, scalar>  $summary  label => value, shown above the table
     * @param  list<int>  $widths  optional column widths (characters)
     */
    public static function build(string $sheetName, string $title, array $metaLines, array $headers, array $rows, array $summary = [], array $widths = []): string
    {
        $cols = max(1, count($headers));
        $xml = [];
        $r = 1;
        $merges = [];

        $xml[] = '<row r="'.$r.'" ht="30" customHeight="1">'.self::str(1, $r, $title, self::S_TITLE).'</row>';
        $merges[] = 'A'.$r.':'.self::col($cols).$r;
        $r++;

        foreach ($metaLines as $line) {
            $xml[] = '<row r="'.$r.'">'.self::str(1, $r, $line, self::S_META).'</row>';
            $merges[] = 'A'.$r.':'.self::col($cols).$r;
            $r++;
        }

        if ($summary !== []) {
            $r++;
            $labels = '';
            $values = '';
            $i = 1;
            foreach ($summary as $label => $value) {
                if ($i > $cols) {
                    break;
                }
                $labels .= self::str($i, $r, (string) $label, self::S_KPI_LABEL);
                $values .= self::cell($i, $r + 1, $value, self::S_KPI_VALUE, self::S_KPI_VALUE);
                $i++;
            }
            $xml[] = '<row r="'.$r.'">'.$labels.'</row>';
            $xml[] = '<row r="'.($r + 1).'" ht="24" customHeight="1">'.$values.'</row>';
            $r += 2;
        }

        $r++; // blank line before the table
        $headerRow = $r;
        $cells = '';
        foreach ($headers as $i => $h) {
            $cells .= self::str($i + 1, $r, (string) $h, self::S_HEADER);
        }
        $xml[] = '<row r="'.$r.'" ht="26" customHeight="1">'.$cells.'</row>';
        $r++;

        $alt = false;
        foreach ($rows as $row) {
            $cells = '';
            foreach (array_values($row) as $i => $value) {
                $cells .= self::cell($i + 1, $r, $value, $alt ? self::S_TEXT_ALT : self::S_TEXT, $alt ? self::S_NUM_ALT : self::S_NUM, $alt ? self::S_LTR_ALT : self::S_LTR);
            }
            $xml[] = '<row r="'.$r.'">'.$cells.'</row>';
            $r++;
            $alt = ! $alt;
        }
        $lastRow = $r - 1;

        $colXml = '';
        for ($i = 1; $i <= $cols; $i++) {
            $w = $widths[$i - 1] ?? self::guessWidth($headers[$i - 1] ?? '', $rows, $i - 1);
            $colXml .= '<col min="'.$i.'" max="'.$i.'" width="'.$w.'" customWidth="1"/>';
        }

        $mergeXml = '';
        if ($merges !== []) {
            $mergeXml = '<mergeCells count="'.count($merges).'">'.implode('', array_map(fn ($m) => '<mergeCell ref="'.$m.'"/>', $merges)).'</mergeCells>';
        }

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView rightToLeft="1" workbookViewId="0" showGridLines="0">'
            .'<pane ySplit="'.$headerRow.'" topLeftCell="A'.($headerRow + 1).'" activePane="bottomLeft" state="frozen"/>'
            .'</sheetView></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="18"/>'
            .'<cols>'.$colXml.'</cols>'
            .'<sheetData>'.implode('', $xml).'</sheetData>'
            .'<autoFilter ref="A'.$headerRow.':'.self::col($cols).max($headerRow, $lastRow).'"/>'
            .$mergeXml
            .'<pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.3" footer="0.3"/>'
            .'<pageSetup orientation="'.($cols > 6 ? 'landscape' : 'portrait').'" fitToHeight="0"/>'
            .'</worksheet>';

        return self::zip($sheetName, $sheet);
    }

    private static function zip(string $sheetName, string $sheetXml): string
    {
        $name = htmlspecialchars(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/u', ' ', $sheetName) ?: 'Sheet1', 0, 31), ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                .'<Default Extension="xml" ContentType="application/xml"/>'
                .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                .'</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                .'</Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                .'<bookViews><workbookView rightToLeft="1"/></bookViews>'
                .'<sheets><sheet name="'.$name.'" sheetId="1" r:id="rId1"/></sheets>'
                .'</workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                .'</Relationships>',
            'xl/styles.xml' => self::styles(),
            'xl/worksheets/sheet1.xml' => $sheetXml,
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Cannot create the Excel file.');
        }
        foreach ($files as $path => $content) {
            $zip->addFromString($path, $content);
        }
        $zip->close();

        $binary = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $binary;
    }

    private static function styles(): string
    {
        $border = '<border><left style="thin"><color rgb="FFD4D4D8"/></left><right style="thin"><color rgb="FFD4D4D8"/></right>'
            .'<top style="thin"><color rgb="FFD4D4D8"/></top><bottom style="thin"><color rgb="FFD4D4D8"/></bottom><diagonal/></border>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0"/></numFmts>'
            .'<fonts count="6">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="16"/><color rgb="FF09090B"/><name val="Calibri"/></font>'
            .'<font><sz val="10"/><color rgb="FF71717A"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFC107"/><name val="Calibri"/></font>'
            .'<font><sz val="10"/><color rgb="FF52525B"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="14"/><color rgb="FF09090B"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="5">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF09090B"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFF4F4F5"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFFFF3C4"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'.$border.'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="12">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            // 1 title
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment horizontal="right" vertical="center" readingOrder="2"/></xf>'
            // 2 meta
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment horizontal="right" vertical="center" readingOrder="2"/></xf>'
            // 3 header
            .'<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1" readingOrder="2"/></xf>'
            // 4 text
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"><alignment horizontal="right" vertical="top" wrapText="1" readingOrder="2"/></xf>'
            // 5 text, zebra
            .'<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment horizontal="right" vertical="top" wrapText="1" readingOrder="2"/></xf>'
            // 6 number
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"><alignment horizontal="center" vertical="top"/></xf>'
            // 7 number, zebra
            .'<xf numFmtId="164" fontId="0" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="top"/></xf>'
            // 8 KPI label
            .'<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1" readingOrder="2"/></xf>'
            // 9 KPI value
            .'<xf numFmtId="164" fontId="5" fillId="4" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>'
            // 10 left-to-right text
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"><alignment horizontal="right" vertical="top" wrapText="1" readingOrder="1"/></xf>'
            // 11 left-to-right text, zebra
            .'<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment horizontal="right" vertical="top" wrapText="1" readingOrder="1"/></xf>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    private static function cell(int $col, int $row, mixed $value, int $textStyle, int $numStyle, ?int $ltrStyle = null): string
    {
        if (is_int($value) || is_float($value)) {
            return '<c r="'.self::col($col).$row.'" s="'.$numStyle.'"><v>'.$value.'</v></c>';
        }

        // Phone numbers, serials and dates are left-to-right text: in a right-to-left sheet a
        // leading + or - would otherwise jump to the wrong end.
        if ($ltrStyle !== null && is_string($value) && $value !== '' && preg_match('/^[ -~]+$/', $value)) {
            return self::str($col, $row, $value, $ltrStyle);
        }

        return self::str($col, $row, $value === null ? '' : (string) $value, $textStyle);
    }

    private static function str(int $col, int $row, string $text, int $style): string
    {
        // Characters that are not allowed in XML 1.0 would corrupt the whole file.
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text) ?? '';
        $text = htmlspecialchars($text, ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');

        return '<c r="'.self::col($col).$row.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$text.'</t></is></c>';
    }

    private static function col(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $s = chr(65 + $m).$s;
            $n = intdiv($n - $m - 1, 26);
        }

        return $s;
    }

    private static function guessWidth(string $header, array $rows, int $index): int
    {
        $max = mb_strlen($header) + 4;
        foreach (array_slice($rows, 0, 200) as $row) {
            $v = array_values($row)[$index] ?? '';
            $max = max($max, min(60, mb_strlen((string) $v) + 2));
        }

        return max(10, min(60, $max));
    }
}
