{{-- Consistent price display: "مجاني" for free plans, else amount + currency + billing period. --}}
@props(['plan'])

@php
    $period = match (true) {
        $plan->billing_period_days === 365 => 'سنويًا',
        $plan->billing_period_days === 30 => 'شهريًا',
        default => 'كل '.$plan->billing_period_days.' يومًا',
    };
    $currency = $plan->currency === 'IQD' ? 'د.ع' : $plan->currency;
@endphp

@if ($plan->price_cents === 0)
    <span {{ $attributes }}>مجاني</span>
@else
    <span {{ $attributes }}><span class="tabular-nums">{{ number_format($plan->price_cents) }}</span> {{ $currency }} <span class="text-xs font-normal text-ink-400">{{ $period }}</span></span>
@endif
