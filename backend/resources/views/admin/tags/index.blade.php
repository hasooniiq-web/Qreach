<x-layouts.admin title="رموز QR/NFC">
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <x-filter-bar class="!mb-0">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="ابحث بالرقم التسلسلي..." aria-label="بحث"
                   class="form-control min-w-[220px] flex-1">
            <select name="type" data-auto-submit aria-label="النوع" class="form-control w-auto">
                <option value="">كل الأنواع</option>
                <option value="qr" @selected(request('type') === 'qr')>QR</option>
                <option value="nfc" @selected(request('type') === 'nfc')>NFC</option>
            </select>
            <select name="status" data-auto-submit aria-label="الحالة" class="form-control w-auto">
                <option value="">كل الحالات</option>
                @foreach (['generated' => 'أُنشئ', 'printing' => 'أُرسل للطباعة', 'unassigned' => 'غير مُعيّن', 'available' => 'غير مُعيّن (قديم)', 'assigned' => 'مُعيّن', 'revoked' => 'مُلغى', 'replaced' => 'استُبدل'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-primary">بحث</button>
        </x-filter-bar>
        <a href="{{ route('admin.tag-batches.create') }}" class="btn-accent shrink-0">+ إنشاء دفعة جديدة</a>
    </div>

    <x-data-table :headers="['الرقم التسلسلي', 'النوع', 'المركبة', 'الحالة', 'تاريخ الإنشاء', '']">
        @forelse ($tags as $tag)
            <tr class="hover:bg-ink-50">
                <td class="px-4 py-3 font-medium tabular-nums text-ink-900"><a href="{{ route('admin.tags.show', $tag) }}" class="hover:underline">{{ $tag->serial_number }}</a></td>
                <td class="px-4 py-3 uppercase text-ink-600">{{ $tag->type }}</td>
                <td class="px-4 py-3 text-ink-600">
                    @if ($tag->vehicle)
                        <a href="{{ route('admin.vehicles.show', $tag->vehicle) }}" class="hover:underline">{{ $tag->vehicle->plate_number }}</a>
                    @else
                        <span class="text-ink-300">—</span>
                    @endif
                </td>
                <td class="px-4 py-3"><x-status-badge :status="$tag->status" /></td>
                <td class="px-4 py-3 text-ink-500">{{ $tag->created_at->format('Y-m-d') }}</td>
                <td class="px-4 py-3 text-end">
                    @if (!in_array($tag->status, ['revoked', 'replaced']))
                        <details class="relative inline-block text-start">
                            <summary class="cursor-pointer list-none text-sm font-medium text-status-danger hover:underline">إلغاء</summary>
                            <form method="POST" action="{{ route('admin.tags.revoke', $tag) }}"
                                  class="absolute end-0 z-10 mt-2 w-64 space-y-2 rounded-lg border border-ink-200 bg-white p-3 shadow-lg">
                                @csrf
                                <label class="block text-xs font-medium text-ink-700">سبب الإلغاء</label>
                                <input type="text" name="reason" required maxlength="255" class="form-control !py-1.5">
                                <p class="text-xs text-ink-500">الإلغاء نهائي ولا يمكن التراجع عنه.</p>
                                <button type="submit" class="btn-danger w-full !py-1.5 text-xs">تأكيد الإلغاء</button>
                            </form>
                        </details>
                    @endif
                </td>
            </tr>
        @empty
            <x-table-empty :cols="6" icon="qrcode" title="لا توجد رموز مطابقة"
                           description="أنشئ دفعة جديدة لتوليد رموز QR أو NFC.">
                <a href="{{ route('admin.tag-batches.create') }}" class="btn-accent mt-4">إنشاء دفعة جديدة</a>
            </x-table-empty>
        @endforelse
    </x-data-table>

    <div class="mt-4">{{ $tags->links() }}</div>
</x-layouts.admin>
