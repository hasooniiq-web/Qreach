<x-layouts.admin :title="'الدفعة '.$batch->batch_code">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-base font-semibold text-ink-900 tabular-nums">{{ $batch->batch_code }}</h2>
            <p class="text-sm text-ink-500">{{ $batch->quantity }} رمز {{ strtoupper($batch->tag_type) }} — أُنشئت في {{ $batch->created_at->format('Y-m-d H:i') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <x-status-badge :status="$batch->status" />
            <a href="{{ route('admin.tag-batches.export', $batch) }}" class="rounded-md border border-ink-200 text-ink-700 text-sm font-medium px-3 py-1.5 hover:bg-ink-50">تصدير CSV</a>
            @if ($batch->tag_type === 'qr')
                <a href="{{ route('admin.tag-batches.print', $batch) }}" target="_blank" class="rounded-md bg-brand-yellow text-brand-black text-sm font-medium px-3 py-1.5 hover:opacity-90">طباعة الرموز</a>
            @endif
        </div>
    </div>

    <div class="rounded-lg border border-ink-200 bg-white overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-ink-50 text-ink-500 text-xs">
                <tr>
                    <th class="text-start font-medium px-4 py-3">الرقم التسلسلي</th>
                    <th class="text-start font-medium px-4 py-3">الرمز العام</th>
                    <th class="text-start font-medium px-4 py-3">الحالة</th>
                    <th class="text-start font-medium px-4 py-3">المركبة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                @foreach ($tags as $tag)
                    <tr class="hover:bg-ink-50">
                        <td class="px-4 py-3 font-medium text-ink-900 tabular-nums">{{ $tag->serial_number }}</td>
                        <td class="px-4 py-3 text-ink-400 text-xs font-mono truncate max-w-[200px]">{{ $tag->public_token }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$tag->status" /></td>
                        <td class="px-4 py-3 text-ink-600">
                            @if ($tag->vehicle)
                                <a href="{{ route('admin.vehicles.show', $tag->vehicle) }}" class="hover:underline">{{ $tag->vehicle->plate_number }}</a>
                            @else
                                <span class="text-ink-300">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $tags->links() }}</div>
</x-layouts.admin>
