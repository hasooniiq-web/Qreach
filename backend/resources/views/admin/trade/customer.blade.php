@php $canManage = auth('admin')->user()->hasPermission('trade.manage'); @endphp

<x-layouts.admin :title="'حساب '.$customer->name">
    <x-page-header :title="$customer->name" :subtitle="$customer->phone ?: 'بدون رقم هاتف'"
                   :back="route('admin.trade.index')" backLabel="المخزون والحسابات">
        <x-slot:actions>
            <span class="rounded-lg px-3 py-1.5 text-sm font-semibold tabular-nums {{ $balance > 0 ? 'bg-status-danger/10 text-status-danger' : 'bg-status-success/10 text-status-success' }}">
                {{ $balance > 0 ? 'عليه' : ($balance < 0 ? 'له (زيادة)' : 'مُسدَّد') }}: {{ number_format(abs($balance)) }} د.ع
            </span>
        </x-slot:actions>
    </x-page-header>

    @if (filled($customer->notes))
        <p class="mb-4 text-sm text-ink-600">{{ $customer->notes }}</p>
    @endif

    @if ($canManage)
        <div class="mb-6 grid gap-4 lg:grid-cols-2">
            <x-card padding="p-5">
                <h3 class="mb-3 text-sm font-semibold text-ink-900">تسليم ملصقات</h3>
                @if ($batches->isEmpty())
                    <p class="text-sm text-ink-500">لا توجد دفعة فيها ملصقات متبقية. أنشئ دفعة جديدة أولًا.</p>
                @else
                    <form method="POST" action="{{ route('admin.trade.sales.store', $customer) }}" class="space-y-3">
                        @csrf
                        <x-field label="من أي دفعة" name="batch_id">
                            <select id="batch_id" name="batch_id" class="form-control" required>
                                @foreach ($batches as $row)
                                    <option value="{{ $row['batch']->id }}" @selected(old('batch_id') == $row['batch']->id)>
                                        {{ $row['batch']->batch_code }} — المتبقي {{ number_format($row['remaining']) }}
                                    </option>
                                @endforeach
                            </select>
                        </x-field>
                        <div class="grid grid-cols-2 gap-3">
                            <x-field label="الكمية" name="quantity">
                                <input id="quantity" name="quantity" value="{{ old('quantity') }}" required inputmode="numeric" data-thousands dir="ltr" class="form-control text-start">
                            </x-field>
                            <x-field label="سعر الستيكر (د.ع)" name="unit_price">
                                <input id="unit_price" name="unit_price" value="{{ old('unit_price', $lastPrice !== null ? number_format($lastPrice) : '') }}" required inputmode="numeric" data-thousands dir="ltr" class="form-control text-start">
                            </x-field>
                        </div>
                        <x-field label="التاريخ" name="occurred_on">
                            <input id="occurred_on" type="date" name="occurred_on" value="{{ old('occurred_on', now()->timezone(config('app.display_timezone'))->toDateString()) }}" class="form-control">
                        </x-field>
                        <x-field label="ملاحظة (اختياري)" name="note">
                            <input id="note" name="note" value="{{ old('note') }}" maxlength="500" class="form-control">
                        </x-field>
                        <button type="submit" class="btn-primary w-full">تسجيل التسليم</button>
                    </form>
                @endif
            </x-card>

            <x-card padding="p-5">
                <h3 class="mb-3 text-sm font-semibold text-ink-900">تسجيل دفعة مستلمة</h3>
                <form method="POST" action="{{ route('admin.trade.payments.store', $customer) }}" class="space-y-3">
                    @csrf
                    <x-field label="المبلغ المستلم (د.ع)" name="amount">
                        <input id="amount" name="amount" required inputmode="numeric" data-thousands dir="ltr" class="form-control text-start"
                               placeholder="{{ $balance > 0 ? number_format($balance) : '' }}">
                    </x-field>
                    <x-field label="التاريخ" name="pay_date">
                        <input id="pay_date" type="date" name="occurred_on" value="{{ now()->timezone(config('app.display_timezone'))->toDateString() }}" class="form-control">
                    </x-field>
                    <x-field label="ملاحظة (اختياري)" name="pay_note">
                        <input id="pay_note" name="note" maxlength="500" class="form-control">
                    </x-field>
                    <button type="submit" class="btn-accent w-full">تسجيل الدفعة وإصدار وصل قبض</button>
                </form>
            </x-card>
        </div>
    @endif

    <h2 class="mb-3 text-sm font-semibold text-ink-900">كشف الحساب</h2>
    <div class="mb-3 flex justify-end"><x-export-buttons /></div>

    <x-data-table :headers="['التاريخ', 'القيد', 'الدفعة', 'الكمية', 'سعر الستيكر', 'المبلغ', '']">
        @forelse ($entries as $entry)
            <tr class="hover:bg-ink-50 {{ $entry->isVoided() ? 'opacity-60' : '' }}">
                <td class="px-4 py-3 tabular-nums text-ink-500">{{ $entry->occurred_on->format('Y-m-d') }}</td>
                <td class="px-4 py-3 text-ink-900">
                    @if ($entry->type === 'sale')
                        تسليم ملصقات
                    @else
                        دفعة مستلمة
                        <a href="{{ route('admin.trade.receipt', $entry) }}" class="ms-1 text-xs tabular-nums text-ink-500 hover:underline" dir="ltr">{{ $entry->receipt_number }}</a>
                    @endif
                    @if ($entry->note)<p class="text-xs text-ink-500">{{ $entry->note }}</p>@endif
                    @if ($entry->isVoided())<p class="text-xs text-status-danger">مُلغى: {{ $entry->void_reason }}</p>@endif
                </td>
                <td class="px-4 py-3 tabular-nums text-ink-600">{{ $entry->batch?->batch_code ?? '—' }}</td>
                <td class="px-4 py-3 tabular-nums text-ink-600">{{ $entry->quantity !== null ? number_format($entry->quantity) : '—' }}</td>
                <td class="px-4 py-3 tabular-nums text-ink-600">{{ $entry->unit_price !== null ? number_format($entry->unit_price) : '—' }}</td>
                <td class="px-4 py-3 font-semibold tabular-nums {{ $entry->type === 'sale' ? 'text-ink-900' : 'text-status-success' }} {{ $entry->isVoided() ? 'line-through' : '' }}">
                    {{ $entry->type === 'sale' ? '+' : '−' }}{{ number_format($entry->amount) }}
                </td>
                <td class="px-4 py-3 text-end">
                    @if ($canManage && ! $entry->isVoided())
                        <form method="POST" action="{{ route('admin.trade.entries.void', $entry) }}" class="inline"
                              data-confirm-title="إلغاء هذا القيد؟"
                              data-confirm="يُخرَج القيد من حساب العميل ومن المخزون، ويبقى ظاهرًا في الكشف مع سبب الإلغاء."
                              data-confirm-label="إلغاء القيد"
                              data-reason-name="reason" data-reason-label="سبب الإلغاء">
                            @csrf
                            <input type="hidden" name="reason" value="">
                            <button type="submit" class="text-sm font-medium text-status-danger hover:underline">إلغاء</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <x-table-empty :cols="7" icon="audit" title="لا توجد قيود بعد"
                           description="سجّل أول تسليم أو دفعة من النموذجين أعلاه." />
        @endforelse
    </x-data-table>
    <div class="mt-4">{{ $entries->links() }}</div>
</x-layouts.admin>
