@props(['status'])

@php
    [$tone, $label] = match ($status) {
        'succeeded' => ['bg-status-success/10 text-status-success ring-status-success/25', 'ناجحة'],
        'failed' => ['bg-status-danger/10 text-status-danger ring-status-danger/25', 'فاشلة'],
        'refunded' => ['bg-ink-100 text-ink-600 ring-ink-200', 'مستردّة'],
        'pending' => ['bg-brand-yellow/15 text-ink-900 ring-brand-yellow/50', 'بانتظار الدفع'],
        default => ['bg-ink-100 text-ink-600 ring-ink-200', $status],
    };
@endphp

{{-- Same look as <x-status-badge>, with payment wording. --}}
<span class="badge {{ $tone }}">{{ $label }}</span>
