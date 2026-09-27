{{--
    The one surface recipe: a softly bordered, softly shadowed rounded panel.
    Use it where grouping helps hierarchy — not around every block.
--}}
@props(['padding' => 'p-5'])

<div {{ $attributes->merge(['class' => "card $padding"]) }}>
    {{ $slot }}
</div>