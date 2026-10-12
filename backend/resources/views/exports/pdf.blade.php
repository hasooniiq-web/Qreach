<html dir="rtl">
<head>
<meta charset="utf-8">
<style>
    body { font-family: plexarabic; direction: rtl; text-align: right; color: #09090B; font-size: 9.5pt; }
    .head { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .head td { vertical-align: middle; }
    .title { font-size: 18pt; font-weight: bold; color: #09090B; }
    .bar { height: 4px; background: #FFC107; }
    .meta { font-size: 8.5pt; color: #52525B; line-height: 1.6; }
    .kpis { width: 100%; border-collapse: separate; border-spacing: 6px; margin: 6px -6px 8px -6px; }
    .kpi { background: #FFF3C4; border: 1px solid #E4D08A; text-align: center; padding: 6px 4px; }
    .kpi .v { font-size: 15pt; font-weight: bold; color: #09090B; }
    .kpi .l { font-size: 8pt; color: #52525B; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data th { background: #09090B; color: #FFC107; padding: 7px 6px; font-size: 9pt; text-align: center; border: 1px solid #09090B; }
    table.data td { padding: 6px; border: 1px solid #D4D4D8; font-size: 9pt; vertical-align: top; text-align: right; }
    table.data tr.alt td { background: #F4F4F5; }
    .note { margin-top: 8px; padding: 6px 8px; background: #FFF3C4; color: #09090B; font-size: 8.5pt; }
</style>
</head>
<body>
<table class="head">
    <tr>
        <td style="width:70%;">
            <div class="title">{{ $title }}</div>
            @foreach ($meta as $line)
                <div class="meta">{{ $line }}</div>
            @endforeach
        </td>
        <td style="width:30%; text-align:left;">
            @if (is_file($logo))
                <img src="{{ $logo }}" width="110">
            @endif
        </td>
    </tr>
</table>
<div class="bar"></div>

@if ($summary !== [])
    <table class="kpis">
        <tr>
            @foreach ($summary as $label => $value)
                <td class="kpi">
                    <div class="v">{{ is_numeric($value) ? number_format($value) : $value }}</div>
                    <div class="l">{{ $label }}</div>
                </td>
            @endforeach
        </tr>
    </table>
@endif

<table class="data" dir="rtl" repeat_header="1">
    <thead>
        <tr>
            @foreach ($headers as $h)
                <th>{{ $h }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $i => $row)
            <tr class="{{ $i % 2 ? 'alt' : '' }}">
                @foreach ($row as $cell)
                    @php
                        $isNumber = is_int($cell) || is_float($cell);
                        $text = $isNumber ? number_format($cell) : (string) $cell;
                        // Phone numbers, serials, dates and links read left to right.
                        $ltr = ! $isNumber && $text !== '' && preg_match('/^[ -~]+$/', $text);
                    @endphp
                    <td>@if ($ltr)<span dir="ltr" style="word-break: break-all;{{ strlen($text) > 28 ? " font-size: 7pt;" : "" }}">{{ $text }}</span>@else{{ $text }}@endif</td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ count($headers) }}" style="text-align:center; color:#71717A;">لا توجد سجلات مطابقة.</td></tr>
        @endforelse
    </tbody>
</table>

@if ($truncated)
    <div class="note">عُرض أول {{ number_format(count($rows)) }} سجل فقط من هذا التقرير. ضيّق الفلاتر أو استخدم تصدير Excel للحصول على عدد أكبر.</div>
@endif
</body>
</html>
