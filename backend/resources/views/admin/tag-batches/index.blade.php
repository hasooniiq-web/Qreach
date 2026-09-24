<x-layouts.admin title="دفعات الرموز">
    <div class="flex items-center justify-end mb-5">
        <a href="{{ route('admin.tag-batches.create') }}" class="rounded-md bg-brand-yellow text-brand-black text-sm font-medium px-4 py-2 hover:opacity-90">
            + إنشاء دفعة جديدة
        </a>
    </div>

    <div class="rounded-lg border border-ink-200 bg-white overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-ink-50 text-ink-500 text-xs">
                <tr>
                    <th class="text-start font-medium px-4 py-3">رمز الدفعة</th>
                    <th class="text-start font-medium px-4 py-3">النوع</th>
                    <th class="text-start font-medium px-4 py-3">الكمية</th>
                    <th class="text-start font-medium px-4 py-3">الحالة</th>
                    <th class="text-start font-medium px-4 py-3">تاريخ الإنشاء</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                @forelse ($batches as $batch)
                    <tr class="hover:bg-ink-50">
                        <td class="px-4 py-3 font-medium text-ink-900 tabular-nums">{{ $batch->batch_code }}</td>
                        <td class="px-4 py-3 text-ink-600 uppercase">{{ $batch->tag_type }}</td>
                        <td class="px-4 py-3 text-ink-600 tabular-nums">{{ $batch->quantity }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$batch->status" /></td>
                        <td class="px-4 py-3 text-ink-500">{{ $batch->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.tag-batches.show', $batch) }}" class="text-sm text-ink-900 font-medium hover:underline">عرض</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-ink-400">لا توجد دفعات بعد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $batches->links() }}</div>
</x-layouts.admin>
