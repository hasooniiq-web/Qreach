@php $canManage = auth('admin')->user()->hasPermission('payment_gateways.manage'); @endphp

<x-layouts.admin title="بوابات الدفع">
    <x-page-header title="بوابات الدفع" subtitle="اختر مزوّد الدفع الإلكتروني وأدخل بيانات اعتماده. لا تُعرض الأسرار بعد حفظها." />

    <x-data-table :headers="['البوابة', 'الحالة', 'الوضع', 'بيانات الاعتماد', '']">
        @foreach ($rows as $row)
            <tr class="hover:bg-ink-50">
                <td class="px-4 py-3 font-medium text-ink-900">{{ $row['driver']->label() }}</td>
                <td class="px-4 py-3">
                    @if (! $row['allowed'])
                        <span class="badge bg-ink-100 text-ink-600 ring-ink-200">غير متاحة في الإنتاج</span>
                    @elseif ($row['gateway']->is_enabled)
                        <span class="badge bg-status-success/10 text-status-success ring-status-success/25">مفعّلة</span>
                    @else
                        <span class="badge bg-ink-100 text-ink-600 ring-ink-200">معطّلة</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-ink-600">{{ $row['gateway']->is_test_mode ? 'اختبار' : 'حقيقي' }}</td>
                <td class="px-4 py-3 text-ink-600">{{ $row['configured'] ? 'مكتملة' : 'ناقصة' }}</td>
                <td class="px-4 py-3 text-end">
                    <a href="{{ route('admin.payment-gateways.edit', $row['driver']->driver()) }}" class="text-sm font-medium text-ink-900 hover:underline">{{ $canManage ? 'إعداد' : 'عرض' }}</a>
                </td>
            </tr>
        @endforeach
    </x-data-table>

    <x-card class="mt-6">
        <h3 class="mb-2 text-sm font-semibold text-ink-900">إضافة مزوّد دفع حقيقي</h3>
        <p class="text-sm leading-relaxed text-ink-500">
            لا يُفعَّل أي مزوّد حقيقي من هنا قبل ربطه برمجيًا. يحتاج كل مزوّد (مثل زين كاش أو البنك العراقي الأول أو كي كارد) حساب تاجر ووثائق التكامل الخاصة به،
            ثم تُضاف له وحدة صغيرة في الكود (وحدة ربط خاصة بالبوابة) فيظهر هنا تلقائيًا. البوابة التجريبية للاختبار فقط ولا تعمل في الإنتاج.
        </p>
    </x-card>
</x-layouts.admin>
