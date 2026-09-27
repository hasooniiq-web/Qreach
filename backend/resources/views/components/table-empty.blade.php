{{-- Empty state for a data-table: one icon, a short title, a line of help, and the next action. --}}
@props(['cols', 'icon' => 'dashboard', 'title', 'description' => null])

<tr class="!border-t-0 hover:!bg-transparent">
    <td colspan="{{ $cols }}" class="px-4 py-16">
        <div class="mx-auto flex max-w-sm flex-col items-center text-center">
            <span class="mb-4 flex size-12 items-center justify-center rounded-full bg-ink-100 text-ink-500">
                <x-icon :name="$icon" class="size-6" />
            </span>
            <p class="text-sm font-semibold text-ink-900">{{ $title }}</p>
            @if ($description)
                <p class="mt-1 text-sm text-ink-500">{{ $description }}</p>
            @endif
            {{ $slot }}
        </div>
    </td>
</tr>