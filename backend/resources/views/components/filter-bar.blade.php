{{--
    Shared filter row for list pages. A "clear" link appears only while a
    filter is active, so the active state is always visible.
--}}
@php
    $active = collect(request()->except('page'))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
@endphp

<form method="GET" {{ $attributes->merge(['class' => 'mb-5 flex flex-wrap items-center gap-2']) }}>
    {{ $slot }}
    @if ($active)
        <a href="{{ url()->current() }}" class="btn-ghost !px-3">
            <x-icon name="x" class="size-4" /> مسح التصفية
        </a>
    @endif
</form>