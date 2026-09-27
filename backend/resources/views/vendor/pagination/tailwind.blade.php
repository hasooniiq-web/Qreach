@if ($paginator->hasPages())
    <nav role="navigation" aria-label="التنقل بين الصفحات" class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-ink-500">
            عرض <span class="font-medium tabular-nums text-ink-900">{{ number_format($paginator->firstItem()) }}</span>
            – <span class="font-medium tabular-nums text-ink-900">{{ number_format($paginator->lastItem()) }}</span>
            من <span class="font-medium tabular-nums text-ink-900">{{ number_format($paginator->total()) }}</span> نتيجة
        </p>

        <div class="flex items-center gap-1">
            {{-- Previous (in RTL the "previous" arrow points right) --}}
            @if ($paginator->onFirstPage())
                <span class="btn-secondary !size-9 !px-0 opacity-40" aria-disabled="true" aria-label="السابق"><x-icon name="chevron-left" class="size-4" style="transform: scaleX(-1)" /></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-secondary !size-9 !px-0" aria-label="السابق"><x-icon name="chevron-left" class="size-4" style="transform: scaleX(-1)" /></a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-1.5 text-sm text-ink-400">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="btn-primary !size-9 !px-0 tabular-nums">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="btn-ghost !size-9 !px-0 tabular-nums" aria-label="الصفحة {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-secondary !size-9 !px-0" aria-label="التالي"><x-icon name="chevron-left" class="size-4" /></a>
            @else
                <span class="btn-secondary !size-9 !px-0 opacity-40" aria-disabled="true" aria-label="التالي"><x-icon name="chevron-left" class="size-4" /></span>
            @endif
        </div>
    </nav>
@endif
