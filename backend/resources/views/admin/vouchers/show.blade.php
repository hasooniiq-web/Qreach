@php
    $admin = auth('admin')->user();
    $canExport = $admin->hasPermission('vouchers.export');
    $canRevoke = $admin->hasPermission('vouchers.revoke');
    $canEdit = $admin->hasPermission('vouchers.create');
    $unused = $counts['available'] + $counts['distributed'];
    $statusTone = ['available' => 'muted', 'distributed' => 'accent', 'redeemed' => 'ok', 'revoked' => 'bad', 'expired' => 'muted'];
@endphp

<x-layouts.admin :title="'الدفعة '.$batch->batch_code">
    <x-page-header :title="$batch->batch_code"
                   :subtitle="$batch->plan->name_ar.' — '.$batch->duration_days.' يوم — '.number_format($batch->quantity).' قسيمة'"
                   :back="route('admin.vouchers.index')" backLabel="قسائم التفعيل">
        @if ($canExport && $unused > 0)
            <x-slot:actions>
                <details data-menu class="relative">
                    <summary class="btn-accent cursor-pointer list-none">تصدير الأرقام ▾</summary>
                    <div class="menu-panel absolute end-0 top-full z-40 mt-2 w-56 rounded-xl border border-ink-200 bg-surface p-1.5 shadow-(--shadow-pop)">
                        <a href="{{ route('admin.vouchers.export', [$batch, 'xlsx']) }}" data-no-progress class="block rounded-lg px-2.5 py-2 text-sm text-ink-800 hover:bg-ink-100">Excel</a>
                        <a href="{{ route('admin.vouchers.export', [$batch, 'pdf']) }}" data-no-progress class="block rounded-lg px-2.5 py-2 text-sm text-ink-800 hover:bg-ink-100">PDF</a>
                        <a href="{{ route('admin.vouchers.export', [$batch, 'csv']) }}" data-no-progress class="block rounded-lg px-2.5 py-2 text-sm text-ink-800 hover:bg-ink-100">CSV (أرقام فقط)</a>
                        <a href="{{ route('admin.vouchers.export', [$batch, 'print']) }}" target="_blank" class="block rounded-lg px-2.5 py-2 text-sm text-ink-800 hover:bg-ink-100">طباعة البطاقات مع QR</a>
                    </div>
                </details>
            </x-slot:actions>
        @endif
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-kpi label="جاهزة (لم تُصدَّر)" :value="$counts['available']" icon="batch" />
        <x-kpi label="سُلّمت للجهة" :value="$counts['distributed']" icon="users" tone="accent" />
        <x-kpi label="فُعّلت" :value="$counts['redeemed']" icon="chart" tone="success" />
        <x-kpi label="ملغاة" :value="$counts['revoked']" icon="warning" />
    </div>

    <x-card padding="p-5" class="mb-6">
        <h3 class="mb-3 text-sm font-semibold text-ink-900">البيع والمستحقات</h3>
        @if ($sale)
            <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                <div><dt class="text-xs text-ink-400">سعر القسيمة</dt><dd class="tabular-nums text-ink-900">{{ number_format($sale->unit_price) }} د.ع</dd></div>
                <div><dt class="text-xs text-ink-400">إجمالي الدفعة</dt><dd class="font-semibold tabular-nums text-ink-900">{{ number_format($sale->amount) }} د.ع</dd></div>
                <div>
                    <dt class="text-xs text-ink-400">الشركة</dt>
                    <dd><a href="{{ route('admin.trade.customers.show', $sale->customer) }}" class="font-medium text-ink-900 hover:underline">{{ $sale->customer->name }}</a></dd>
                </div>
                <div>
                    <dt class="text-xs text-ink-400">المتبقي على الشركة (كل حساباتها)</dt>
                    @php $owed = $sale->customer->balance(); @endphp
                    <dd class="font-semibold tabular-nums {{ $owed > 0 ? 'text-status-danger' : 'text-status-success' }}">{{ number_format($owed) }} د.ع</dd>
                </div>
            </dl>
        @elseif (auth('admin')->user()->hasPermission('trade.manage'))
            <p class="mb-3 text-sm text-ink-500">لم يُسجَّل بيع لهذه الدفعة. اختر حساب الشركة وسعر القسيمة فيُسجَّل المبلغ على حسابها.</p>
            <form method="POST" action="{{ route('admin.vouchers.bill', $batch) }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div class="min-w-[200px]">
                    <label for="bill-customer" class="mb-1 block text-xs text-ink-500">حساب الشركة</label>
                    <select id="bill-customer" name="customer_id" class="form-control !py-2" required>
                        <option value="">اختر</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected($batch->customer_id === $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="bill-price" class="mb-1 block text-xs text-ink-500">سعر القسيمة (د.ع)</label>
                    <input id="bill-price" name="unit_price" value="{{ $batch->unit_price ? number_format($batch->unit_price) : '' }}" required inputmode="numeric" data-thousands dir="ltr" class="form-control !py-2 w-40 text-start">
                </div>
                <button class="btn-accent">تسجيل البيع</button>
            </form>
            @error('unit_price')<p class="mt-2 text-xs text-status-danger" role="alert">{{ $message }}</p>@enderror
        @else
            <p class="text-sm text-ink-400">لم يُسجَّل بيع لهذه الدفعة.</p>
        @endif
    </x-card>

    <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <x-card padding="p-5">
            <h3 class="mb-3 text-sm font-semibold text-ink-900">أين ذهبت هذه الدفعة</h3>
            <dl class="mb-4 grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-xs text-ink-400">الجهة المستلمة</dt><dd class="text-ink-900">{{ $batch->recipient ?: 'لم تُحدَّد' }}</dd></div>
                <div><dt class="text-xs text-ink-400">صالحة حتى</dt><dd class="text-ink-900">{{ $batch->expires_at ? \App\Support\Export\Labels::date($batch->expires_at) : 'بلا تاريخ انتهاء' }}</dd></div>
                <div><dt class="text-xs text-ink-400">أنشأها</dt><dd class="text-ink-900">{{ $batch->creator?->name ?? '—' }}</dd></div>
                <div><dt class="text-xs text-ink-400">تاريخ الإنشاء</dt><dd class="text-ink-900">{{ \App\Support\Export\Labels::date($batch->created_at, true) }}</dd></div>
            </dl>
            @if ($batch->notes)<p class="mb-3 text-sm text-ink-600">{{ $batch->notes }}</p>@endif

            @if ($canEdit)
                <form method="POST" action="{{ route('admin.vouchers.recipient', $batch) }}" class="flex items-end gap-2">
                    @csrf @method('PUT')
                    <div class="flex-1">
                        <label for="recipient" class="mb-1 block text-xs text-ink-500">تغيير الجهة المستلمة</label>
                        <input id="recipient" name="recipient" value="{{ old('recipient', $batch->recipient) }}" maxlength="160" class="form-control !py-2">
                    </div>
                    <button class="btn-secondary">حفظ</button>
                </form>
            @endif
        </x-card>

        <x-card padding="p-5">
            <h3 class="mb-3 text-sm font-semibold text-ink-900">سجلّ التصدير</h3>
            @forelse ($exports as $export)
                <div class="flex items-start justify-between gap-3 border-b border-ink-100 py-2 text-sm last:border-0">
                    <div>
                        <p class="text-ink-900">{{ number_format($export->count) }} قسيمة — {{ ['xlsx' => 'Excel', 'pdf' => 'PDF', 'csv' => 'CSV', 'print' => 'طباعة'][$export->format] ?? $export->format }}</p>
                        <p class="text-xs text-ink-500">
                            إلى: {{ $export->recipient ?: '—' }} — بواسطة {{ $export->admin?->name ?? '—' }}
                        </p>
                    </div>
                    <span class="shrink-0 text-xs tabular-nums text-ink-400">{{ \App\Support\Export\Labels::date($export->created_at, true) }}</span>
                </div>
            @empty
                <p class="text-sm text-ink-400">لم تُصدَّر أرقام هذه الدفعة بعد.</p>
            @endforelse
        </x-card>
    </div>

    @if ($canRevoke && $unused > 0)
        <x-card padding="p-4" class="mb-6">
            <form method="POST" action="{{ route('admin.vouchers.revoke-batch', $batch) }}"
                  data-confirm-title="إلغاء كل القسائم غير المستخدمة؟"
                  data-confirm="ستُلغى {{ number_format($unused) }} قسيمة لم تُستخدم بعد ولا تعود صالحة. القسائم المُفعَّلة لا تتأثر."
                  data-confirm-label="إلغاء القسائم"
                  data-reason-name="reason" data-reason-label="سبب الإلغاء">
                @csrf
                <input type="hidden" name="reason" value="">
                <button type="submit" class="text-sm font-medium text-status-danger hover:underline">إلغاء كل القسائم غير المستخدمة في هذه الدفعة ({{ number_format($unused) }})</button>
            </form>
        </x-card>
    @endif

    <x-filter-bar>
        <select name="status" data-auto-submit aria-label="الحالة" class="form-control w-auto">
            <option value="">كل الحالات</option>
            @foreach ($labels as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <input type="text" name="q" value="{{ request('q') }}" inputmode="numeric" maxlength="4" placeholder="آخر 4 أرقام" class="form-control !w-36" dir="ltr">
        <button class="btn-primary">بحث</button>
    </x-filter-bar>

    <x-data-table :headers="['الرقم', 'الحالة', 'سُلّمت إلى', 'المستخدم الذي فعّلها', 'تاريخ التفعيل', '']">
        @forelse ($vouchers as $voucher)
            @php $state = $voucher->effectiveStatus(); @endphp
            <tr class="hover:bg-ink-50">
                <td class="px-4 py-3 font-mono text-sm tabular-nums text-ink-900" dir="ltr">{{ $voucher->maskedCode() }}</td>
                <td class="px-4 py-3">
                    <span class="badge {{ ['ok' => 'bg-status-success/10 text-status-success ring-status-success/25', 'bad' => 'bg-status-danger/10 text-status-danger ring-status-danger/25', 'accent' => 'bg-brand-yellow/15 text-ink-900 ring-brand-yellow/50', 'muted' => 'bg-ink-100 text-ink-600 ring-ink-200'][$statusTone[$state] ?? 'muted'] }}">{{ $labels[$state] ?? $state }}</span>
                    @if ($voucher->status === 'revoked' && $voucher->revoke_reason)<p class="mt-1 text-xs text-ink-400">{{ $voucher->revoke_reason }}</p>@endif
                </td>
                <td class="px-4 py-3 text-ink-600">{{ $voucher->delivered_to ?: '—' }}</td>
                <td class="px-4 py-3 text-ink-600">
                    @if ($voucher->redeemer)
                        <a href="{{ route('admin.users.show', $voucher->redeemer) }}" class="font-medium text-ink-900 hover:underline">{{ $voucher->redeemer->display_name }}</a>
                        <p class="text-xs tabular-nums text-ink-400" dir="ltr">{{ $voucher->redeemer->phone }}</p>
                    @else — @endif
                </td>
                <td class="px-4 py-3 text-ink-500">{{ \App\Support\Export\Labels::date($voucher->redeemed_at, true) }}</td>
                <td class="px-4 py-3 text-end">
                    @if ($canRevoke && in_array($voucher->status, ['available', 'distributed'], true))
                        <form method="POST" action="{{ route('admin.vouchers.revoke', $voucher) }}" class="inline"
                              data-confirm-title="إلغاء هذه القسيمة؟"
                              data-confirm="لن تعود القسيمة التي تنتهي بـ {{ $voucher->last4 }} صالحة للاستخدام."
                              data-confirm-label="إلغاء القسيمة"
                              data-reason-name="reason" data-reason-label="سبب الإلغاء">
                            @csrf
                            <input type="hidden" name="reason" value="">
                            <button type="submit" class="text-sm font-medium text-status-danger hover:underline">إلغاء</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <x-table-empty :cols="6" icon="star" title="لا توجد قسائم مطابقة" description="غيّر الحالة أو امسح البحث." />
        @endforelse
    </x-data-table>

    <div class="mt-4">{{ $vouchers->links() }}</div>
</x-layouts.admin>
