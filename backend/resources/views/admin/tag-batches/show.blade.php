<x-layouts.admin :title="'الدفعة '.$batch->batch_code">
    <x-page-header :title="$batch->batch_code"
                   :subtitle="$batch->quantity.' رمز '.strtoupper($batch->tag_type).' — أُنشئت في '.$batch->created_at->format('Y-m-d H:i')"
                   :back="route('admin.tag-batches.index')" backLabel="كل الدفعات">
        <x-slot:actions>
            <x-status-badge :status="$batch->status" />
            <a href="{{ route('admin.tag-batches.export', $batch) }}" class="btn-secondary">تصدير CSV</a>
            @if ($batch->tag_type === 'qr')
                <a href="{{ route('admin.tag-batches.print', $batch) }}" target="_blank" class="btn-accent">طباعة الرموز</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-data-table :headers="['الرقم التسلسلي', 'الرمز العام', 'الحالة', 'المركبة']">
        @foreach ($tags as $tag)
            <tr class="hover:bg-ink-50">
                <td class="px-4 py-3 font-medium tabular-nums text-ink-900">{{ $tag->serial_number }}</td>
                <td class="max-w-[200px] truncate px-4 py-3 font-mono text-xs text-ink-400" dir="ltr">{{ $tag->public_token }}</td>
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
    </x-data-table>

    <div class="mt-4">{{ $tags->links() }}</div>
</x-layouts.admin>
