@props(['title', 'description' => null, 'robots' => 'index,follow'])

@php
    $locale = app()->getLocale();
    $dir = trans('site.dir');
    $languages = ['ar' => 'العربية', 'en' => 'English', 'ckb' => 'کوردی'];
    $path = request()->url();
    $ogLocales = ['ar' => 'ar_IQ', 'en' => 'en_US', 'ckb' => 'ckb_IQ'];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
        <meta property="og:description" content="{{ $description }}">
    @endif
    <meta name="robots" content="{{ $robots }}">
    <meta name="theme-color" content="#09090B">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Qreach SmartTag">
    <meta property="og:locale" content="{{ $ogLocales[$locale] ?? 'ar_IQ' }}">
    <link rel="canonical" href="{{ $path }}?lang={{ $locale }}">
    @foreach ($languages as $code => $label)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ $path }}?lang={{ $code }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $path }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Arabic:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        body { font-family: {{ $locale === 'en' ? "'Inter', 'Noto Sans Arabic'" : "'Noto Sans Arabic', 'Inter'" }}, ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="min-h-dvh bg-white text-ink-900 antialiased">
    <a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:start-3 focus:top-3 focus:z-50 focus:rounded-lg focus:bg-brand-yellow focus:px-4 focus:py-2 focus:font-semibold focus:text-brand-black">{{ trans('site.skip') }}</a>

    <header class="sticky top-0 z-40 border-b border-white/10 bg-brand-black/95 text-white backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-4 px-5">
            <a href="{{ url('/') }}?lang={{ $locale }}" class="flex items-center gap-2.5" aria-label="Qreach SmartTag">
                <span class="flex size-8 items-center justify-center rounded-full bg-brand-yellow text-sm font-extrabold text-brand-black">Q</span>
                <span class="text-sm font-semibold">Qreach <span class="font-normal text-white/60">SmartTag</span></span>
            </a>

            @isset($nav)
                <nav aria-label="Main" class="ms-6 hidden items-center gap-6 text-sm text-white/75 md:flex">{{ $nav }}</nav>
            @endisset

            <div class="ms-auto flex items-center gap-3">
                <nav aria-label="{{ trans('site.language') }}" class="flex items-center rounded-full bg-white/10 p-0.5 text-xs">
                    @foreach ($languages as $code => $label)
                        <a href="{{ $path }}?lang={{ $code }}" lang="{{ $code }}" hreflang="{{ $code }}"
                           @if ($code === $locale) aria-current="true" @endif
                           class="rounded-full px-2.5 py-1.5 font-medium transition-colors focus-visible:outline-2 focus-visible:outline-brand-yellow {{ $code === $locale ? 'bg-brand-yellow text-brand-black' : 'text-white/80 hover:text-white' }}">{{ $label }}</a>
                    @endforeach
                </nav>
                @isset($cta){{ $cta }}@endisset
            </div>
        </div>
    </header>

    <main id="content">
        {{ $slot }}
    </main>

    <footer class="border-t border-ink-200 bg-ink-50">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-5 py-6 text-center text-xs text-ink-500 sm:flex-row sm:text-start">
            <p>{{ trans('site.footer.rights', ['year' => now()->year]) }}</p>
            <p>{{ trans('site.footer.note') }}</p>
        </div>
    </footer>
</body>
</html>
