<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'تسجيل الدخول' }} — Qreach</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-dvh bg-ink-900 flex items-center justify-center p-6">
    <div class="w-full max-w-sm">
        <div class="flex flex-col items-center gap-2 mb-8">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-yellow">
                <span class="text-lg font-bold text-brand-black">Q</span>
            </div>
            <p class="text-white font-semibold tracking-wide">Qreach <span class="text-ink-400 font-normal">SmartTag</span></p>
        </div>

        <div class="bg-white rounded-lg border border-ink-200 p-8 shadow-sm">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-status-success/10 border border-status-success/30 px-4 py-3 text-sm text-status-success">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-md bg-status-danger/10 border border-status-danger/30 px-4 py-3 text-sm text-status-danger" role="alert">
                    <ul class="list-disc pr-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{ $slot }}
        </div>

        <p class="mt-6 text-center text-xs text-ink-400">© {{ date('Y') }} Qreach — منصة SmartTag لاتصال المركبات</p>
    </div>
</body>
</html>
