{{-- Empty state for a data-table: explains the situation and (optionally) offers the next action. --}}
@props(['cols', 'icon' => 'dashboard', 'title', 'description' => null])

<tr>
    <td colspan="{{ $cols }}" class="px-4 py-14">
        <div class="mx-auto flex max-w-sm flex-col items-center text-center">
            <x-icon :name="$icon" class="mb-3 size-8 text-ink-300" />
            <p class="text-sm font-semibold text-ink-900">{{ $title }}</p>
            @if ($description)
                <p class="mt-1 text-xs text-ink-500">{{ $description }}</p>
            @endif
            {{ $slot }}
        </div>
    </td>
</tr>
