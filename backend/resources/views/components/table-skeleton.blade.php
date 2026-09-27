{{-- A table-shaped loading placeholder (header + rows) for content that streams in. --}}
@props(['rows' => 5, 'cols' => 4])

<x-card padding="p-0" class="overflow-hidden" aria-busy="true">
    <div class="border-b border-ink-100 bg-ink-50/70 px-4 py-3">
        <x-skeleton class="h-3 w-40" />
    </div>
    @for ($r = 0; $r < $rows; $r++)
        <div class="flex items-center gap-4 border-t border-ink-100 px-4 py-4 first:border-0">
            @for ($c = 0; $c < $cols; $c++)
                <x-skeleton class="h-4 {{ $c === 0 ? 'w-32' : 'w-20' }} flex-1" />
            @endfor
        </div>
    @endfor
</x-card>