{{--
    The one table shell: a card with a quiet header row and hairline row
    separators. Scrolls horizontally on narrow screens instead of clipping.
    Pass header labels via :headers; an empty string is an unlabeled actions column.
--}}
@props(['headers'])

<x-card padding="p-0" class="overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table w-full text-sm">
            <thead class="bg-ink-50/70">
                <tr>
                    @foreach ($headers as $header)
                        <th scope="col">
                            @if ($header === '')
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