{{--
    A statistic: small label, large number, an honest trend versus the previous
    period (only when the caller has real data for it) and an optional
    sparkline. Pass `href` to make the whole card a link.
--}}
@props(['label', 'value', 'icon', 'size' => 'md', 'tone' => 'neutral', 'trend' => null, 'spark' => null, 'href' => null, 'hint' => null])

@php
    $iconTone = match ($tone) {
        'danger' => 'bg-status-danger/10 text-status-danger',
        'success' => 'bg-status-success/10 text-status-success',
        'accent' => 'bg-brand-yellow/20 text-ink-900',
        default => 'bg-ink-100 text-ink-600',
    };
    $valueClass = $size === 'lg' ? 'text-3xl' : 'text-2xl';
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'card group block p-5 transition duration-200 '.($href ? 'hover:-translate-y-0.5 hover:shadow-(--shadow-pop)' : '')]) }}>
    <div class="mb-4 flex items-center justify-between">
        <span class="text-sm font-medium text-ink-500">{{ $label }}</span>
        <span class="flex size-8 items-center justify-center rounded-lg {{ $iconTone }}">
            <x-icon :name="$icon" class="size-4" />
        </span>
    </div>
    <div class="flex items-end justify-between gap-3">
        <div>
            <p class="{{ $valueClass }} font-bold tracking-tight text-ink-900 tabular-nums">{{ number_format($value) }}</p>
            <div class="mt-1.5 flex items-center gap-2 text-xs">
                @if ($trend !== null)
                    @php $up = $trend > 0; $flat = $trend == 0; @endphp
                    <span class="inline-flex items-center gap-0.5 font-semibold {{ $flat ? 'text-ink-500' : ($up ? 'text-status-success' : 'text-status-danger') }}">
                        @unless ($flat)<x-icon :name="$up ? 'trend-up' : 'trend-down'" class="size-3.5" />@endunless
                        <span dir="ltr">{{ $up ? '+' : '' }}{{ $trend }}%</span>
                    </span>
                @endif
                @if ($hint)<span class="text-ink-400">{{ $hint }}</span>@endif
            </div>
        </div>
        @if ($spark)
            <x-sparkline :points="$spark" class="h-10 w-24 shrink-0 text-ink-400" />
        @endif
    </div>
</{{ $tag }}>