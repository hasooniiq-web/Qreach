<x-layouts.admin title="رموز QR/NFC">
    <div class="flex items-center justify-between mb-5">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="ابحث بالرقم التسلسلي..."
                   class="flex-1 min-w-[200px] rounded-md border border-ink-200 px-3 py-2 text-sm focus:border-brand-yellow focus:ring-2 focus:ring-brand-yellow/40 outline-none">
            <select name="type" class="rounded-md border border-ink-200 px-3 py-2 text-sm" onchange="this.form.submit()">
                <option value="">كل الأنواع</option>
                <option value="qr" @selected(request('type') === 'qr')>QR</option>
                <option value="nfc" @selected(request('type') === 'nfc')>NFC</option>
            </select>
            <select name="status" class="rounded-md border border-ink-200 px-3 py-2 text-sm" onchange="this.form.submit()">
                <option value="">كل الحالات</option>
                @foreach (['generated','available','assigned','revoked','replaced'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-md bg-brand-black text-white text-sm font-medium px-4 py-2">بحث</button>
        </form>
        <a href="{{ route('admin.tag-batches.create') }}" class="shrink-0 rounded-md bg-brand-yellow text-brand-black text-sm font-medium px-4 py-2 hover:opacity-90">
            + إنشاء دفعة جديدة
        </a>
    </div>

    <div class="rounded-lg border border-ink-200 bg-white overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-ink-50 text-ink-500 text-xs">
                <tr>
                    <th class="text-start font-medium px-4 py-3">الرقم التسلسلي</th>
                    <th class="text-start font-medium px-4 py-3">النوع</th>
                    <th class="text-start font-medium px-4 py-3">المركبة</th>
                    <th class="text-start font-medium px-4 py-3">الحالة</th>
                    <th class="text-start font-medium px-4 py-3">تاريخ الإنشاء</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                @forelse ($tags as $tag)
                    <tr class="hover:bg-ink-50">
                        <td class="px-4 py-3 font-medium text-ink-900 tabular-nums">{{ $tag->serial_number }}</td>
                        <td class="px-4 py-3 text-ink-600 uppercase">{{ $tag->type }}</td>
                        <td class="px-4 py-3 text-ink-600">
                            @if ($tag->vehicle)
                                <a href="{{ route('admin.vehicles.show', $tag->vehicle) }}" class="hover:underline">{{ $tag->vehicle->plate_number }}</a>
                            @else
                                <span class="text-ink-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3"><x-status-badge :status="$tag->status" /></td>
                        <td class="px-4 py-3 text-ink-500">{{ $tag->created_at->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">
                            @if (!in_array($tag->status, ['revoked', 'replaced']))
                                <details class="relative">
                                    <summary class="text-sm text-status-danger font-medium hover:underline cursor-pointer list-none">إلغاء</summary>
                                    <form method="POST" action="{{ route('admin.tags.revoke', $tag) }}"
                                          class="absolute z-10 end-0 mt-2 w-64 rounded-lg border border-ink-200 bg-white p-3 shadow-lg space-y-2">
                                        @csrf
                                        <label class="block text-xs font-medium text-ink-700">سبب الإلغاء</label>
                                        <input type="text" name="reason" required maxlength="255"
                                               class="block w-full rounded-md border border-ink-200 px-2.5 py-1.5 text-sm focus:border-status-danger focus:ring-1 focus:ring-status-danger outline-none">
                                        <button type="submit" class="w-full rounded-md bg-status-danger text-white text-xs font-medium py-1.5">تأكيد الإلغاء</button>
                                    </form>
                                </details>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-ink-400">لا توجد رموز مطابقة.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $tags->links() }}</div>
</x-layouts.admin>
