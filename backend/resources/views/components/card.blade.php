{{--
    The one card recipe for the dashboard — replaces the
    `rounded-lg border border-ink-200 bg-white p-5` recipe that was
    hand-repeated in every admin view. See docs/UI_UX_DESIGN_SYSTEM_V2.md.
--}}
@props(['padding' => 'p-5'])

<div {{ $attributes->merge(['class' => "rounded-lg border border-ink-200 bg-white $padding"]) }}>
    {{ $slot }}
</div>
