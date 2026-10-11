<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>وصل قبض {{ $entry->receipt_number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(["resources/css/app.css", "resources/js/print-batch.js"])
    <style>
        @page { size: A5 landscape; margin: 12mm; }
        body { background: #fff; color: #09090B; font-family: "Noto Sans Arabic", sans-serif; }
        .receipt { max-width: 720px; margin: 24px auto; border: 2px solid #09090B; border-radius: 12px; padding: 28px; }
        .row { display: flex; justify-content: space-between; gap: 16px; padding: 10px 0; border-bottom: 1px dashed #d4d4d8; }
        .row:last-child { border-bottom: 0; }
        .label { color: #52525b; }
        .amount { font-size: 28px; font-weight: 800; }
        @media print { .no-print { display: none !important; } .receipt { margin: 0; border-radius: 0; } }
    </style>
</head>
<body>
    <div class="no-print mx-auto flex max-w-[720px] items-center justify-between px-2 pt-4 text-sm">
        <a href="{{ route('admin.trade.customers.show', $entry->customer) }}" class="underline">← حساب {{ $entry->customer->name }}</a>
        <button id="print-button" class="btn-primary" type="button">طباعة / حفظ PDF</button>
    </div>

    <div class="receipt">
        <div class="flex items-center justify-between" style="margin-bottom: 18px;">
            <div>
                <p style="font-size: 22px; font-weight: 800;">Qreach SmartTag</p>
                <p class="label">وصل قبض</p>
            </div>
            <div style="text-align: end;">
                <p class="label">رقم الوصل</p>
                <p style="font-size: 20px; font-weight: 800;" dir="ltr">{{ $entry->receipt_number }}</p>
            </div>
        </div>

        <div class="row"><span class="label">استلمنا من</span><strong>{{ $entry->customer->name }}</strong></div>
        <div class="row"><span class="label">مبلغًا وقدره</span><span class="amount">{{ number_format($entry->amount) }} د.ع</span></div>
        <div class="row"><span class="label">التاريخ</span><strong>{{ $entry->occurred_on->format('Y-m-d') }}</strong></div>
        @if ($entry->note)
            <div class="row"><span class="label">ملاحظة</span><span>{{ $entry->note }}</span></div>
        @endif
        <div class="row">
            <span class="label">المتبقي على العميل بعد هذه الدفعة</span>
            <strong>{{ number_format(max(0, $balanceAfter)) }} د.ع</strong>
        </div>

        <div style="margin-top: 36px; display: flex; justify-content: space-between;" class="label">
            <span>توقيع المستلم: ______________</span>
            <span>توقيع العميل: ______________</span>
        </div>
    </div>
</body>
</html>
