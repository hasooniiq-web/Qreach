<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>طباعة الدفعة {{ $batch->batch_code }}</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/js/print-batch.js'])
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'IBM Plex Sans Arabic', sans-serif; margin: 0; padding: 16px; color: #000; }
        .toolbar { margin-bottom: 16px; }
        .toolbar button { font-family: inherit; background: #FFC107; border: none; border-radius: 6px; padding: 10px 20px; font-size: 14px; font-weight: 500; cursor: pointer; }
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .tag-card { border: 1px solid #D4D4D4; border-radius: 8px; padding: 12px; text-align: center; page-break-inside: avoid; }
        .tag-card img { width: 100%; height: auto; }
        .tag-card p { margin: 6px 0 0; font-size: 11px; font-weight: 500; direction: ltr; }
        @media print {
            .toolbar { display: none; }
            .grid { grid-template-columns: repeat(3, 1fr); }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" id="print-button">طباعة</button>
    </div>

    <div class="grid">
        @foreach ($tags as $tag)
            @if ($tag->qrTag)
                <div class="tag-card">
                    <img src="{{ route('admin.tags.image', [$tag, 'png']) }}" alt="QR {{ $tag->serial_number }}">
                    <p>{{ $tag->serial_number }}</p>
                </div>
            @endif
        @endforeach
    </div>

</body>
</html>
