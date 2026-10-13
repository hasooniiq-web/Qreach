@php $canBulk = auth('admin')->user()->hasPermission('printing.create'); @endphp

<x-layouts.admin title="دفعات الرموز">
    <div class="mb-5 flex items-center justify-end">
        <a href="{{ route('admin.tag-batches.create') }}" class="btn-accent">+ إنشاء دفعة جديدة</a>
    </div>

    <div class="mb-3 flex justify-end"><x-export-buttons /></div>

    <form method="POST" action="{{ route('admin.tag-batches.bulk') }}" @if ($canBulk) data-bulk @endif>
    @if ($canBulk)
        @csrf
        <x-bulk-bar noun="دفعة" />
        @error('ids')<p class="mb-3 text-sm text-status-danger">{{ $message }}</p>@enderror
    @endif

    <x-data-table :headers="array_merge($canBulk ? ['__select'] : [], ['رمز الدفعة', 'النوع', 'الكمية', 'ملاحظات', 'الحالة', 'تاريخ الإنشاء', ''])">
        @forelse ($batches as $batch)
            <tr class="hover:bg-ink-50">
                @if ($canBulk)
                    <td class="px-4 py-3"><input type="checkbox" name="ids[]" value="{{ $batch->id }}" data-bulk-item aria-label="تحديد الدفعة {{ $batch->batch_code }}" class="size-4 rounded border-ink-300 accent-brand-yellow"></td>
                @endif
                <td class="px-4 py-3 font-medium tabular-nums text-ink-900">{{ $batch->batch_code }}</td>
                <td class="px-4 py-3 uppercase text-ink-600">{{ \App\Models\Tag::typeLabels()[$batch->tag_type] ?? $batch->tag_type }}</td>
                <td class="px-4 py-3 tabular-nums text-ink-600">{{ $batch->quantity }}</td>
                <td class="max-w-[260px] truncate px-4 py-3 text-ink-500" title="{{ $batch->notes }}">{{ $batch->notes ?: '—' }}</td>
                <td class="px-4 py-3"><x-status-badge :status="$batch->status" /></td>
                <td class="px-4 py-3 text-ink-500">{{ $batch->created_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i') }}</td>
                <td class="px-4 py-3 text-end">
                    <a href="{{ route('admin.tag-batches.show', $batch) }}" class="text-sm font-medium text-ink-900 hover:underline">عرض</a>
                </td>
            </tr>
        @empty
            <x-table-empty :cols="$canBulk ? 8 : 7" icon="batch" title="لا توجد دفعات بعد"
                           description="الدفعة مجموعة من الرموز تُولَّد دفعة واحدة ثم تُصدَّر للطباعة أو البرمجة.">
                <a href="{{ route('admin.tag-batches.create') }}" class="btn-accent mt-4">إنشاء أول دفعة</a>
            </x-table-empty>
        @endforelse
    </x-data-table>
    </form>

    <div class="mt-4">{{ $batches->links() }}</div>
</x-layouts.admin>
