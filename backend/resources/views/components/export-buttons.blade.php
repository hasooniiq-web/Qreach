{{--
    Two buttons that download the page's current list (with its filters) as Excel or
    PDF. The controller recognises ?export=xlsx|pdf. data-no-progress keeps the page
    interactive: a download never replaces the page.
--}}
@php
    $query = request()->query();
    unset($query['page'], $query['export']);
    $base = request()->url();
@endphp
<div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    <a href="{{ $base.'?'.http_build_query($query + ['export' => 'xlsx']) }}" data-no-progress download class="btn-secondary">
        <x-icon name="audit" class="size-4" /> Excel
    </a>
    <a href="{{ $base.'?'.http_build_query($query + ['export' => 'pdf']) }}" data-no-progress download class="btn-secondary">
        <x-icon name="audit" class="size-4" /> PDF
    </a>
</div>
