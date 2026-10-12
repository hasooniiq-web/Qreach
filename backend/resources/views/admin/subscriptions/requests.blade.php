@php $canManage = auth('admin')->user()->hasPermission('subscriptions.manage'); @endphp

<x-layouts.admin title="طلبات الترقية">
    <x-subscription-tabs current="requests" :pending="$pendingCount" />

    <nav aria-label="حالة الطلب" class="mb-5 inline-flex overflow-hidden rounded-md border border-ink-200 bg-white text-sm">
        @foreach (['pending' => 'قيد المراجعة', 'approved' => 'تمت الموافقة', 'rejected' => 'مرفوضة'] as $value => $label)
            <a href="{{ route('admin.subscriptions.requests', ['status' => $value]) }}" @if ($status === $value) aria-current="true" @endif
               class="border-e border-ink-200 px-3.5 py-2 font-medium last:border-e-0 {{ $status === $value ? 'bg-brand-black text-white' : 'text-ink-600 hover:bg-ink-50' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <div class="mb-3 flex justify-end"><x-export-buttons /></div>

    <x-data-table :headers="['المستخدم', 'الباقة المطلوبة', 'السعر', 'ملاحظة', 'تاريخ الطلب', '']">
        @forelse ($requests as $upgrade)
            <tr class="hover:bg-ink-50">
                <td class="px-4 py-3">
                    <a href="{{ route('admin.users.show', $upgrade->user) }}" class="font-medium text-ink-900 hover:underline">{{ $upgrade->user->display_name }}</a>
                    <p class="text-xs tabular-nums text-ink-400" dir="ltr">{{ $upgrade->user->phone }}</p>
                </td>
                <td class="px-4 py-3 text-ink-900">{{ $upgrade->plan->name_ar }}</td>
                <td class="px-4 py-3 text-ink-600"><x-plan-price :plan="$upgrade->plan" /></td>
                <td class="max-w-[220px] truncate px-4 py-3 text-ink-500">{{ $upgrade->note ?? '—' }}</td>
                <td class="px-4 py-3 text-ink-500">{{ $upgrade->created_at->diffForHumans() }}</td>
                <td class="px-4 py-3">
                    @if ($upgrade->status === 'pending' && $canManage)
                        <div class="flex items-center justify-end gap-3">
                            <form method="POST" action="{{ route('admin.subscriptions.requests.approve', $upgrade) }}"
                                  data-confirm-title="الموافقة على الترقية؟"
                                  data-confirm="سيحصل {{ $upgrade->user->name ?? 'المستخدم' }} على الباقة «{{ $upgrade->plan->name_ar }}» لمدة {{ $upgrade->plan->billing_period_days }} يومًا فورًا. تأكد من استلام قيمة الاشتراك قبل الموافقة."
                                  data-confirm-label="موافقة وتفعيل">
                                @csrf
                                <button class="btn-primary !px-3 !py-1.5">موافقة</button>
                            </form>
                            <form method="POST" action="{{ route('admin.subscriptions.requests.reject', $upgrade) }}"
                                  data-confirm-title="رفض الطلب؟" data-confirm="سيبقى المستخدم على باقته الحالية، ويمكنه تقديم طلب جديد لاحقًا." data-confirm-label="رفض الطلب">
                                @csrf
                                <button class="text-sm text-status-danger hover:underline">رفض</button>
                            </form>
                        </div>
                    @else
                        <div class="text-end"><x-status-badge :status="$upgrade->status === 'approved' ? 'resolved' : ($upgrade->status === 'rejected' ? 'failure' : 'unresolved')" /></div>
                    @endif
                </td>
            </tr>
        @empty
            <x-table-empty :cols="6" icon="bell" :title="'لا توجد طلبات '.['pending' => 'قيد المراجعة', 'approved' => 'موافَق عليها', 'rejected' => 'مرفوضة'][$status]"
                           description="عندما يطلب عميل من التطبيق الترقية إلى باقة Plus يظهر طلبه هنا." />
        @endforelse
    </x-data-table>

    <div class="mt-4">{{ $requests->links() }}</div>
</x-layouts.admin>
