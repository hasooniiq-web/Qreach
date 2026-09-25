<x-layouts.admin title="دفعات الرموز">
    <div class="mb-5 flex items-center justify-end">
        <a href="{{ route('admin.tag-batches.create') }}" class="btn-accent">+ إنشاء دفعة جديدة</a>
    </div>

    <x-data-table :headers="['رمز الدفعة', 'النوع', 'الكمية', 'الحالة', 'تاريخ الإنشاء', '']">
        @forelse ($batches as $batch)
            <tr class="hover:bg-ink-50">
                <td class="px-4 py-3 font-medium tabular-nums text-ink-900">{{ $batch->batch_code }}</td>
                <td class="px-4 py-3 uppercase text-ink-600">{{ $batch->tag_type }}</td>
                <td class="px-4 py-3 tabular-nums text-ink-600">{{ $batch->quantity }}</td>
                <td class="px-4 py-3"><x-status-badge :status="$batch->status" /></td>
                <td class="px-4 py-3 text-ink-500">{{ $batch->created_at->format('Y-m-d H:i') }}</td>
                <td class="px-4 py-3 text-end">
                    <a href="{{ route('admin.tag-batches.show', $batch) }}" class="text-sm font-medium text-ink-900 hover:underline">عرض</a>
                </td>
            </tr>
        @empty
            <x-table-empty :cols="6" icon="batch" title="لا توجد دفعات بعد"
                           description="الدفعة مجموعة من الرموز تُولَّد دفعة واحدة ثم تُصدَّر للطباعة أو البرمجة.">
                <a href="{{ route('admin.tag-batches.create') }}" class="btn-accent mt-4">إنشاء أول دفعة</a>
            </x-table-empty>
        @endforelse
    </x-data-table>

    <div class="mt-4">{{ $batches->links() }}</div>
</x-layouts.admin>
