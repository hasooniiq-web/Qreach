{{--
    Replaces the dashboard home's 8 visually identical KPI cards (see
    docs/UI_UX_REDESIGN_AUDIT.md finding on dashboard/index.blade.php) with
    a component that actually supports size hierarchy. No trend prop exists
    on purpose — the dashboard has no historical comparison data to show
    one honestly (spec: "never invent trends"); add it only once
    analytics_daily_stats-backed period comparison is built.
--}}
@props(['label', 'value', 'icon', 'size' => 'md', 'tone' => 'neutral'])

@php
    $valueClass = $size === 'lg' ? 'text-3xl' : 'text-2xl';
    $iconToneClass = match ($tone) {
        'danger' => 'text-status-danger',
        'success' => 'text-status-success',
        default => 'text-ink-300',
    };
@endphp

<x-card>
    <div class="flex items-center justify-between mb-3">
        <span class="text-sm text-ink-500">{{ $label }}</span>
        <x-icon :name="$icon" class="size-4 {{ $iconToneClass }}" />
    </div>
    <p class="{{ $valueClass }} font-semibold text-ink-900 tabular-nums">{{ number_format($value) }}</p>
</x-card>
