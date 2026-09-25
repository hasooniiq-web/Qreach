{{-- Sub-navigation shared by the three subscription screens. --}}
@props(['current', 'pending' => 0])

@php
    $tabs = [
        'subscriptions' => ['المشتركون', route('admin.subscriptions.index')],
        'requests' => ['طلبات الترقية', route('admin.subscriptions.requests')],
        'plans' => ['الباقات والصلاحيات', route('admin.plans.index')],
    ];
@endphp

<nav aria-label="أقسام الاشتراكات" class="mb-6 flex gap-1 border-b border-ink-200">
    @foreach ($tabs as $key => [$label, $url])
        <a href="{{ $url }}" @if ($current === $key) aria-current="page" @endif
           class="-mb-px flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium {{ $current === $key ? 'border-brand-black text-ink-900' : 'border-transparent text-ink-500 hover:text-ink-900' }}">
            {{ $label }}
            @if ($key === 'requests' && $pending > 0)
                <span class="rounded-full bg-brand-yellow px-2 py-0.5 text-xs font-semibold text-brand-black">{{ $pending }}</span>
            @endif
        </a>
    @endforeach
</nav>
