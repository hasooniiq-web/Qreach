{{--
    Shared filter row for list pages. Shows a "clear filters" link only
    when a filter is actually active, so the active state is visible
    (redesign brief §25) instead of the user having to compare each control
    against its default.
--}}
@php
    $active = collect(request()->except('page'))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
@endphp

<form method="GET" {{ $attributes->merge(['class' => 'mb-5 flex flex-wrap items-center gap-3']) }}>
    {{ $slot }}
    @if ($active)
        <a href="{{ url()->current() }}" class="text-sm font-medium text-ink-500 underline-offset-2 hover:text-ink-900 hover:underline">مسح التصفية</a>
    @endif
</form>
