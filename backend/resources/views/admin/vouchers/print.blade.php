<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>بطاقات {{ $batch->batch_code }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;600;800&display=swap" rel="stylesheet">
    @vite(['resources/js/print-batch.js'])
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Noto Sans Arabic', sans-serif; margin: 0; padding: 14px; color: #09090B; }
        .toolbar { margin-bottom: 14px; display: flex; gap: 12px; align-items: center; }
        .toolbar button { font-family: inherit; background: #FFC107; border: none; border-radius: 6px; padding: 10px 20px; font-size: 14px; font-weight: 600; cursor: pointer; }
        .note { font-size: 12px; color: #52525B; }
        .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .card { border: 1.5px solid #09090B; border-radius: 10px; padding: 10px 12px; display: flex; align-items: center; gap: 12px; page-break-inside: avoid; background: #fff; }
        .card .qr { width: 96px; height: 96px; flex: none; }
        .card .qr img { width: 100%; height: 100%; }
        .card .brand { font-weight: 800; font-size: 14px; }
        .card .plan { font-size: 11px; color: #52525B; margin-top: 2px; }
        .card .code { margin-top: 8px; font-size: 17px; font-weight: 800; letter-spacing: 1px; direction: ltr; text-align: right; font-variant-numeric: tabular-nums; }
        .card .hint { margin-top: 4px; font-size: 9.5px; color: #71717A; }
        .bar { height: 4px; background: #FFC107; margin: -10px -12px 8px; border-radius: 8px 8px 0 0; }
        @media print { .toolbar { display: none; } body { padding: 0; } .grid { gap: 8px; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" id="print-button">طباعة</button>
        <span class="note">{{ $cards->count() }} بطاقة — {{ $batch->plan->name_ar }} — {{ $batch->duration_days }} يوم. أُدخل الرقم أو امسح الـ QR من «الاشتراك» في تطبيق Qreach.</span>
    </div>

    <div class="grid">
        @foreach ($cards as $card)
            <div class="card">
                <div class="qr"><img src="{{ $card['qr'] }}" alt="QR"></div>
                <div style="min-width:0;">
                    <div class="bar" style="display:none"></div>
                    <div class="brand">Qreach SmartTag</div>
                    <div class="plan">قسيمة تفعيل — {{ $batch->plan->name_ar }} — {{ $batch->duration_days }} يوم</div>
                    <div class="code">{{ $card['code'] }}</div>
                    <div class="hint">استخدمها مرة واحدة من تطبيق Qreach ← الاشتراك ← لديّ قسيمة</div>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
