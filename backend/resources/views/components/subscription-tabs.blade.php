{{-- Sub-navigation shared by the three subscription screens: a segmented control. --}}
@props(['current', 'pending' => 0])

@php
    $tabs = [
        'subscriptions' => ['المشتركون', route('admin.subscriptions.index')],
        'requests' => ['طلبات الترقية', route('admin.subscriptions.requests')],
        'plans' => ['الباقات والصلاحيات', route('admin.plans.index')],
        'referrals' => ['الدعوات', route('admin.referrals.index')],
    ];
@endphp

<nav aria-label="أقسام الاشتراكات" class="mb-6 inline-flex max-w-full gap-1 overflow-x-auto rounded-lg bg-ink-100 p-1">
    @foreach ($tabs as $key => [$label, $url])
        <a href="{{ $url }}" @if ($current === $key) aria-current="page" @endif
           class="flex items-center gap-2 whitespace-nowrap rounded-md px-3.5 py-1.5 text-sm font-medium transition-all {{ $current === $key ? 'bg-white text-ink-900 shadow-xs' : 'text-ink-500 hover:text-ink-900' }}">
            {{ $label }}
            @if ($key === 'requests' && $pending > 0)
                <span class="rounded-full bg-brand-yellow px-1.5 text-xs font-semibold text-brand-black">{{ $pending }}</span>
            @endif
        </a>
    @endforeach
</nav>