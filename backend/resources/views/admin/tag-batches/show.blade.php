<x-layouts.admin :title="'الدفعة '.$batch->batch_code">
    @php $admin = auth('admin')->user(); @endphp
    <x-page-header :title="$batch->batch_code"
                   :subtitle="$batch->quantity.' رمز '.(\App\Models\Tag::typeLabels()[$batch->tag_type] ?? strtoupper($batch->tag_type)).' — أُنشئت في '.$batch->created_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i')"
                   :back="route('admin.tag-batches.index')" backLabel="كل الدفعات">
        <x-slot:actions>
            <x-status-badge :status="$batch->status" />

            @if ($admin->hasPermission('printing.export') || $admin->hasPermission('qr.export'))
                <details data-menu class="relative">
                    <summary class="btn-secondary cursor-pointer list-none">تصدير ▾</summary>
                    <div class="menu-panel absolute end-0 top-full z-40 mt-2 w-48 rounded-xl border border-ink-200 bg-surface p-1.5 shadow-(--shadow-pop)">
                        <a href="{{ route('admin.tag-batches.export', $batch) }}" data-no-progress class="block rounded-lg px-2.5 py-2 text-sm text-ink-800 hover:bg-ink-100">CSV (كامل)</a>
                        <a href="{{ route('admin.tag-batches.export.codes', $batch) }}" data-no-progress class="block rounded-lg px-2.5 py-2 text-sm text-ink-800 hover:bg-ink-100">رموز فقط (JSON)</a>
                        <a href="{{ route('admin.tag-batches.export.text', $batch) }}" data-no-progress class="block rounded-lg px-2.5 py-2 text-sm text-ink-800 hover:bg-ink-100">نص (الرقم التسلسلي + الرابط الفريد)</a>
                    </div>
                </details>
            @endif
            @if (in_array($batch->tag_type, ['qr', 'code'], true))
                <a href="{{ route('admin.tag-batches.print', $batch) }}" target="_blank" class="btn-accent">طباعة الرموز</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if (filled($batch->notes))
        <x-card class="mb-5">
            <h3 class="mb-1 text-sm font-semibold text-ink-900">ملاحظات الدفعة</h3>
            <p class="whitespace-pre-line text-sm text-ink-700">{{ $batch->notes }}</p>
        </x-card>
    @endif

    @if ($admin->hasPermission('printing.create'))
        <x-card class="mb-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-ink-900">مسار الطباعة</h3>
                    <p class="text-sm text-ink-500">أُنشئ ← أُرسل للطباعة ← غير مُعيّن (جاهز للربط)</p>
                </div>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('admin.tag-batches.send-to-printing', $batch) }}"
                          data-confirm-title="إرسال الدفعة للطباعة؟"
                          data-confirm="تتحوّل كل الرموز التي ما زالت بحالة «أُنشئ» إلى «أُرسل للطباعة». يُسجَّل هذا في سجلّ كل رمز."
                          data-confirm-label="إرسال للطباعة">
                        @csrf
                        <button class="btn-secondary" @disabled($batch->tags()->where('status', 'generated')->doesntExist())>تحديد كمُرسلة للطباعة</button>
                    </form>
                    <form method="POST" action="{{ route('admin.tag-batches.mark-unassigned', $batch) }}"
                          data-confirm-title="تحديد الرموز كجاهزة؟"
                          data-confirm="تتحوّل الرموز بحالة «أُرسل للطباعة» إلى «غير مُعيّن» — جاهزة ليربطها المستخدمون."
                          data-confirm-label="تحديد كجاهزة">
                        @csrf
                        <button class="btn-accent" @disabled($batch->tags()->where('status', 'printing')->doesntExist())>تحديد كجاهزة (غير مُعيّن)</button>
                    </form>
                </div>
            </div>
        </x-card>
    @endif

    @if ($admin->hasPermission('printing.export') || $admin->hasPermission('qr.export'))
    <div class="mb-3 flex justify-end"><x-export-buttons /></div>
    @endif

    <x-data-table :headers="['الرقم التسلسلي', 'الرمز العام', 'الحالة', 'المركبة']">
        @forelse ($tags as $tag)
            <tr class="hover:bg-ink-50">
                <td class="px-4 py-3 font-medium tabular-nums text-ink-900"><a href="{{ route('admin.tags.show', $tag) }}" class="hover:underline">{{ $tag->serial_number }}</a></td>
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
        @empty
            <x-table-empty :cols="4" icon="qrcode" title="لا توجد رموز في هذه الدفعة" />
        @endforelse
    </x-data-table>

    <div class="mt-4">{{ $tags->links() }}</div>
</x-layouts.admin>
