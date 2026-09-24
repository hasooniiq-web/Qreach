<x-layouts.admin title="سجلات التدقيق">
    <form method="GET" class="flex flex-wrap items-center gap-3 mb-5">
        <input type="text" name="action" value="{{ request('action') }}" placeholder="ابحث بنوع الإجراء (مثال: qr.activated)..."
               class="min-w-[220px] rounded-md border border-ink-200 px-3 py-2 text-sm focus:border-brand-yellow focus:ring-2 focus:ring-brand-yellow/40 outline-none">
        <select name="actor_type" class="rounded-md border border-ink-200 px-3 py-2 text-sm" onchange="this.form.submit()">
            <option value="">كل الجهات</option>
            <option value="customer" @selected(request('actor_type') === 'customer')>عميل</option>
            <option value="admin" @selected(request('actor_type') === 'admin')>مسؤول</option>
            <option value="system" @selected(request('actor_type') === 'system')>النظام</option>
        </select>
        <select name="result" class="rounded-md border border-ink-200 px-3 py-2 text-sm" onchange="this.form.submit()">
            <option value="">كل النتائج</option>
            <option value="success" @selected(request('result') === 'success')>نجاح</option>
            <option value="failure" @selected(request('result') === 'failure')>فشل</option>
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="rounded-md border border-ink-200 px-3 py-2 text-sm">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded-md border border-ink-200 px-3 py-2 text-sm">
        <button type="submit" class="rounded-md bg-brand-black text-white text-sm font-medium px-4 py-2">تصفية</button>
    </form>

    <div class="rounded-lg border border-ink-200 bg-white overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-ink-50 text-ink-500 text-xs">
                <tr>
                    <th class="text-start font-medium px-4 py-3">الإجراء</th>
                    <th class="text-start font-medium px-4 py-3">الجهة الفاعلة</th>
                    <th class="text-start font-medium px-4 py-3">المورد</th>
                    <th class="text-start font-medium px-4 py-3">النتيجة</th>
                    <th class="text-start font-medium px-4 py-3">عنوان IP</th>
                    <th class="text-start font-medium px-4 py-3">التاريخ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                @forelse ($logs as $log)
                    <tr class="hover:bg-ink-50">
                        <td class="px-4 py-3 font-medium text-ink-900 font-mono text-xs">{{ $log->action }}</td>
                        <td class="px-4 py-3 text-ink-600">{{ $log->actor_type }} @if($log->actor_id) #{{ $log->actor_id }} @endif</td>
                        <td class="px-4 py-3 text-ink-600">{{ $log->resource_type }} @if($log->resource_id) #{{ $log->resource_id }} @endif</td>
                        <td class="px-4 py-3">
                            <span class="text-xs {{ $log->result === 'success' ? 'text-status-success' : 'text-status-danger' }}">
                                {{ $log->result === 'success' ? 'نجاح' : 'فشل' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-ink-500 tabular-nums" dir="ltr">{{ $log->request_ip }}</td>
                        <td class="px-4 py-3 text-ink-500">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-ink-400">لا توجد سجلات مطابقة.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
</x-layouts.admin>
