@php
    $current = \Illuminate\Support\Facades\Route::currentRouteName();
    $drawer = $drawer ?? false;
@endphp

<div class="flex h-14 shrink-0 items-center gap-2.5 border-b border-ink-100 px-4">
    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-yellow shadow-xs">
        <x-icon name="qrcode" class="size-[18px] text-brand-black" />
    </div>
    <div class="sb-brand-text min-w-0 leading-tight">
        <p class="truncate text-sm font-bold text-ink-900">Qreach</p>
        <p class="truncate text-[11px] text-ink-500">SmartTag · لوحة التحكم</p>
    </div>
    @if ($drawer)
        <button type="button" id="mobile-nav-close" aria-label="إغلاق القائمة" class="btn-ghost ms-auto !size-9 !px-0"><x-icon name="x" class="size-5" /></button>
    @endif
</div>

<nav aria-label="التنقل الرئيسي" class="scrollbar-none flex-1 space-y-5 overflow-y-auto px-3 py-4">
    @foreach ($nav as $section => $items)
        <div class="sb-group">
            <p class="sb-section mb-1.5 px-2.5 text-[11px] font-semibold tracking-wide text-ink-400">{{ $section }}</p>
            <ul class="space-y-0.5">
                @foreach ($items as $item)
                    @php
                        $isActive = $current === $item['route']
                            || str_starts_with((string) $current, preg_replace('/\.index$/', '', $item['route']).'.');
                        // The dashboard itself must not stay "active" for every admin.* route.
                        if ($item['route'] === 'admin.dashboard') { $isActive = $current === 'admin.dashboard'; }
                        // Plan management belongs to the subscriptions area.
                        if ($item['route'] === 'admin.subscriptions.index' && str_starts_with((string) $current, 'admin.plans.')) { $isActive = true; }
                        // Tag detail pages belong to the codes list.
                        if ($item['route'] === 'admin.tag-batches.index' && str_starts_with((string) $current, 'admin.tags.')) { $isActive = false; }
                    @endphp
                    <li>
                        <a href="{{ route($item['route']) }}" data-tip="{{ $item['label'] }}"
                           @if ($isActive) aria-current="page" @endif class="sb-item">
                            <x-icon :name="$item['icon']" class="sb-icon" />
                            <span class="sb-label truncate">{{ $item['label'] }}</span>
                            @isset($item['badge'])
                                <span data-badge="{{ $item['badge'] }}" hidden class="ms-auto min-w-5 rounded-full bg-brand-yellow px-1.5 text-center text-[11px] font-bold text-brand-black"></span>
                            @endisset
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>
