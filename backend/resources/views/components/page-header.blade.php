{{--
    Consistent page title block for detail/list pages: optional back link,
    title, subtitle, and an `actions` slot. Replaces the ad-hoc
    "flex justify-between mb-5" headers each detail view wrote by hand.
--}}
@props(['title', 'subtitle' => null, 'back' => null, 'backLabel' => 'رجوع'])

<div class="mb-6">
    @if ($back)
        <a href="{{ $back }}" class="mb-3 inline-flex items-center gap-1 text-xs font-medium text-ink-500 hover:text-ink-900">
            <span aria-hidden="true">→</span> {{ $backLabel }}
        </a>
    @endif
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h2 class="text-xl font-bold text-ink-900">{{ $title }}</h2>
            @if ($subtitle)
                <p class="mt-1 text-sm text-ink-500">{{ $subtitle }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
</div>
