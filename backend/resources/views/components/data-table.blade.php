{{--
    The one table shell for the dashboard: card surface, horizontal scroll
    on narrow screens (previously `overflow-hidden`, which clipped columns
    on tablets/phones), and a sticky header. Pass header labels via
    :headers; an empty string makes an unlabeled actions column.
--}}
@props(['headers'])

<x-card padding="p-0" class="overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="sticky top-0 bg-ink-50 text-xs text-ink-500">
                <tr>
                    @foreach ($headers as $header)
                        <th scope="col" class="px-4 py-3 text-start font-medium">
                            @if ($header === '')
                                <span class="sr-only">إجراءات</span>
                            @else
                                {{ $header }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</x-card>
