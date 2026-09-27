{{-- A tiny inline SVG line + soft area from a list of numbers. Pure markup, no JS. --}}
@props(['points'])

@php
    $pts = array_values(array_map('floatval', $points));
    $n = count($pts);
    $w = 96; $h = 40; $pad = 3;
    $max = max($pts) ?: 1; $min = min($pts);
    $span = ($max - $min) ?: 1;
    $coords = [];
    foreach ($pts as $i => $v) {
        $x = $n > 1 ? $pad + ($w - 2 * $pad) * $i / ($n - 1) : $w / 2;
        $y = $h - $pad - ($h - 2 * $pad) * (($v - $min) / $span);
        $coords[] = round($x, 1).','.round($y, 1);
    }
    $line = implode(' ', $coords);
    $area = $n > 1 ? "$pad,$h ".$line." ".($w - $pad).",$h" : '';
@endphp

<svg viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" fill="none" aria-hidden="true" {{ $attributes }}>
    @if ($n > 1)
        <polygon points="{{ $area }}" fill="currentColor" opacity="0.08" />
        <polyline points="{{ $line }}" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
    @endif
</svg>