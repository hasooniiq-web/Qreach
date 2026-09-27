{{--
    Page title block: optional back link, title, description and an actions slot.
--}}
@props(['title', 'subtitle' => null, 'back' => null, 'backLabel' => 'رجوع'])

<div class="mb-6" data-page-header>
    @if ($back)
        <a href="{{ $back }}" class="mb-3 inline-flex items-center gap-1.5 text-xs font-medium text-ink-500 transition-colors hover:text-ink-900">
            <x-icon name="arrow-left" class="size-3.5" style="transform: scaleX(-1)" /> {{ $backLabel }}
        </a>
    @endif
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h2 class="text-2xl font-bold tracking-tight text-ink-900">{{ $title }}</h2>
            @if ($subtitle)
                <p class="mt-1 text-sm text-ink-500">{{ $subtitle }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
</div>