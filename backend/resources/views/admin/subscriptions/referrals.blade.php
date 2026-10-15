<x-layouts.admin title="الدعوات">
    <x-subscription-tabs current="referrals" :pending="\App\Models\PlanUpgradeRequest::where('status', 'pending')->count()" />

    <p class="mb-5 text-sm text-ink-500">
        @if ($rewardDays > 0)
            كل عميل لديه رمز دعوة. عندما يستخدم رمزه <strong class="text-ink-900">{{ $invitesRequired }}</strong> من الأصدقاء يحصل على
            <strong class="text-ink-900">{{ $rewardDays }}</strong> يومًا من بلس مجانًا، مرة واحدة لكل حساب.
            تُضبط القيمتان من «الإعدادات».
        @else
            الدعوات متوقفة حاليًا (أيام المكافأة = 0). فعّلها من «الإعدادات».
        @endif
    </p>

    <div class="mb-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
        <x-kpi label="إجمالي الدعوات" :value="$stats['total']" icon="users" />
        <x-kpi label="بانتظار اشتراك الصديق" :value="$stats['pending']" icon="bell" />
        <x-kpi label="مكافآت مدفوعة" :value="$stats['rewarded']" icon="chart" tone="success" />
        <x-kpi label="أيام مجانية ممنوحة" :value="$stats['days']" icon="audit" />
    </div>

    <x-filter-bar>
        <select name="status" data-auto-submit aria-label="الحالة" class="form-control w-auto">
            <option value="">كل الحالات</option>
            @foreach (\App\Http\Controllers\Admin\ReferralController::STATUS_LABELS as $key => $label)
                <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </x-filter-bar>

    <div class="mb-3 flex justify-end"><x-export-buttons /></div>

    <x-data-table :headers="['الداعي', 'المدعو', 'الحالة', 'الأيام الممنوحة', 'تاريخ الدعوة', 'تاريخ المكافأة']">
        @forelse ($referrals as $referral)
            <tr class="hover:bg-ink-50">
                <td class="px-4 py-3">
                    @if ($referral->referrer)
                        <a href="{{ route('admin.users.show', $referral->referrer) }}" class="font-medium text-ink-900 hover:underline">{{ $referral->referrer->display_name }}</a>
                        <p class="text-xs tabular-nums text-ink-400" dir="ltr">{{ $referral->referrer->phone }}</p>
                    @else — @endif
                </td>
                <td class="px-4 py-3">
                    @if ($referral->referred)
                        <a href="{{ route('admin.users.show', $referral->referred) }}" class="font-medium text-ink-900 hover:underline">{{ $referral->referred->display_name }}</a>
                        <p class="text-xs tabular-nums text-ink-400" dir="ltr">{{ $referral->referred->phone }}</p>
                    @else — @endif
                </td>
                <td class="px-4 py-3">
                    @php $tone = ['rewarded' => 'bg-status-success/10 text-status-success ring-status-success/25', 'capped' => 'bg-status-danger/10 text-status-danger ring-status-danger/25'][$referral->status] ?? 'bg-ink-100 text-ink-600 ring-ink-200'; @endphp
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $tone }}">
                        {{ \App\Http\Controllers\Admin\ReferralController::STATUS_LABELS[$referral->status] ?? $referral->status }}
                    </span>
                </td>
                <td class="px-4 py-3 tabular-nums text-ink-700">{{ $referral->reward_days ?? '—' }}</td>
                <td class="px-4 py-3 text-ink-500">{{ \App\Support\Export\Labels::date($referral->created_at) }}</td>
                <td class="px-4 py-3 text-ink-500">{{ \App\Support\Export\Labels::date($referral->rewarded_at) }}</td>
            </tr>
        @empty
            <x-table-empty :cols="6" icon="users" title="لا توجد دعوات بعد"
                           description="تظهر هنا الدعوات عندما يُدخل عميل رمز دعوة صديقه في التطبيق." />
        @endforelse
    </x-data-table>

    <div class="mt-4">{{ $referrals->links() }}</div>
</x-layouts.admin>
