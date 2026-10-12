@php $canManage = auth('admin')->user()->hasPermission('subscriptions.manage'); @endphp

<x-layouts.admin title="الاشتراكات">
    <x-subscription-tabs current="subscriptions" :pending="$stats['pending_requests']" />

    <div class="mb-3 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-kpi label="مشتركو باقة Plus" :value="$stats['pro_active']" icon="users" size="lg" tone="success" />
        <x-kpi label="مستخدمو الباقة الأساسية" :value="$stats['basic']" icon="users" size="lg" />
        <x-card>
            <div class="mb-3 flex items-center justify-between">
                <span class="text-sm text-ink-500">القيمة المتوقعة للاشتراكات الفعّالة</span>
                <x-icon name="chart" class="size-4 text-ink-300" />
            </div>
            <p class="text-3xl font-semibold tabular-nums text-ink-900">{{ number_format($stats['active_value']) }} <span class="text-base font-normal text-ink-400">{{ $stats['currency'] === 'IQD' ? 'د.ع' : $stats['currency'] }}</span></p>
            <p class="mt-1 text-xs text-ink-400">حسب أسعار الباقات المعلنة — وليس مبالغ مستلمة.</p>
        </x-card>
    </div>
    <div class="mb-8 grid grid-cols-2 gap-4">
        <x-kpi label="اشتراكات تنتهي خلال 30 يومًا" :value="$stats['expiring_soon']" icon="audit" />
        <x-kpi label="طلبات ترقية بانتظار المراجعة" :value="$stats['pending_requests']" icon="bell" />
    </div>

    <x-filter-bar>
        <select name="plan" data-auto-submit aria-label="الباقة" class="form-control w-auto">
            <option value="">كل الباقات</option>
            @foreach ($plans as $plan)
                <option value="{{ $plan->slug }}" @selected(request('plan') === $plan->slug)>{{ $plan->name_ar }}</option>
            @endforeach
        </select>
        <select name="status" data-auto-submit aria-label="الحالة" class="form-control w-auto">
            <option value="">كل الحالات</option>
            <option value="active" @selected(request('status') === 'active')>فعّال</option>
            <option value="expired" @selected(request('status') === 'expired')>منتهي</option>
            <option value="canceled" @selected(request('status') === 'canceled')>ملغى</option>
        </select>
    </x-filter-bar>

    <div class="mb-3 flex justify-end"><x-export-buttons /></div>

    <x-data-table :headers="['المشترك', 'الباقة', 'السعر', 'الحالة', 'بداية الاشتراك', 'ينتهي في', '']">
        @forelse ($subscriptions as $subscription)
            @php $effective = $subscription->effectiveStatus(); @endphp
            <tr class="hover:bg-ink-50">
                <td class="px-4 py-3">
                    <a href="{{ route('admin.users.show', $subscription->user) }}" class="font-medium text-ink-900 hover:underline">{{ $subscription->user->display_name }}</a>
                    <p class="text-xs tabular-nums text-ink-400" dir="ltr">{{ $subscription->user->phone }}</p>
                </td>
                <td class="px-4 py-3 text-ink-900">{{ $subscription->plan->name_ar }}</td>
                <td class="px-4 py-3 text-ink-600"><x-plan-price :plan="$subscription->plan" /></td>
                <td class="px-4 py-3"><x-status-badge :status="$effective" /></td>
                <td class="px-4 py-3 text-ink-500">{{ $subscription->current_period_starts_at?->copy()->timezone(config('app.display_timezone'))->format('Y-m-d') ?? '—' }}</td>
                <td class="px-4 py-3 text-ink-500">{{ $subscription->current_period_ends_at?->copy()->timezone(config('app.display_timezone'))->format('Y-m-d') ?? 'بلا تاريخ انتهاء' }}</td>
                <td class="px-4 py-3 text-end">
                    @if ($canManage && $effective === 'active')
                        <form method="POST" action="{{ route('admin.subscriptions.cancel', $subscription) }}"
                              data-confirm-title="إلغاء هذا الاشتراك؟"
                              data-confirm="سيعود {{ $subscription->user->name ?? 'المستخدم' }} إلى الباقة الأساسية فورًا، وتُخفى بيانات التواصل المعروضة للزوار. يمكنك منحه الباقة من جديد لاحقًا."
                              data-confirm-label="إلغاء الاشتراك">
                            @csrf
                            <button class="text-sm text-status-danger hover:underline">إلغاء</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <x-table-empty :cols="7" icon="users" title="لا توجد اشتراكات مطابقة"
                           description="المستخدمون بلا اشتراك مسجَّل هم على الباقة الأساسية المجانية. تظهر هنا اشتراكات الباقات المدفوعة عند منحها أو الموافقة على طلب ترقية." />
        @endforelse
    </x-data-table>

    <div class="mt-4">{{ $subscriptions->links() }}</div>
</x-layouts.admin>
