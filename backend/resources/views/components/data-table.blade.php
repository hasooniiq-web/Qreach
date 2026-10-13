{{--
    The one table shell: a card with a quiet header row and hairline row
    separators. Scrolls horizontally on narrow screens instead of clipping.
    Pass header labels via :headers; an empty string is an unlabeled actions column.
--}}
@props(['headers'])

<x-card padding="p-0" class="data-table-shell overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table w-full text-sm">
            <thead class="bg-ink-50/70">
                <tr>
                    @foreach ($headers as $header)
                        <th scope="col">
                            @if ($header === '__select')
                                <input type="checkbox" data-bulk-all aria-label="تحديد الكل" class="size-4 rounded border-ink-300 accent-brand-yellow">
                            @elseif ($header === '')
                                <span class="sr-only">إجراءات</span>
                            @else
                                {{ $header }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </div>
</x-card>