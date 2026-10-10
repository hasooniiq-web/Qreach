<x-layouts.admin title="سجلات التدقيق">
    <x-filter-bar>
        <input type="text" name="action" value="{{ request('action') }}" placeholder="نوع الإجراء (مثال: qr.activated)" aria-label="نوع الإجراء"
               class="form-control min-w-[220px] flex-1" dir="ltr">
        <select name="actor_type" data-auto-submit aria-label="الجهة" class="form-control w-auto">
            <option value="">كل الجهات</option>
            <option value="customer" @selected(request('actor_type') === 'customer')>عميل</option>
            <option value="admin" @selected(request('actor_type') === 'admin')>مسؤول</option>
            <option value="system" @selected(request('actor_type') === 'system')>النظام</option>
        </select>
        <select name="result" data-auto-submit aria-label="النتيجة" class="form-control w-auto">
            <option value="">كل النتائج</option>
            <option value="success" @selected(request('result') === 'success')>نجاح</option>
            <option value="failure" @selected(request('result') === 'failure')>فشل</option>
        </select>
        <input type="date" name="from" value="{{ request('from') }}" aria-label="من تاريخ" class="form-control w-auto">
        <input type="date" name="to" value="{{ request('to') }}" aria-label="إلى تاريخ" class="form-control w-auto">
        <button type="submit" class="btn-primary">تصفية</button>
    </x-filter-bar>

    <x-data-table :headers="['الإجراء', 'الجهة الفاعلة', 'المورد', 'النتيجة', 'عنوان IP', 'التاريخ']">
        @forelse ($logs as $log)
            <tr class="hover:bg-ink-50">
                <td class="px-4 py-3 font-mono text-xs font-medium text-ink-900" dir="ltr">{{ $log->action }}</td>
                <td class="px-4 py-3 text-ink-600">{{ $log->actor_type }} @if($log->actor_id) #{{ $log->actor_id }} @endif</td>
                <td class="px-4 py-3 text-ink-600">{{ $log->resource_type }} @if($log->resource_id) #{{ $log->resource_id }} @endif</td>
                <td class="px-4 py-3"><x-status-badge :status="$log->result" /></td>
                <td class="px-4 py-3 tabular-nums text-ink-500" dir="ltr">{{ $log->request_ip }}</td>
                <td class="px-4 py-3 text-ink-500">{{ $log->created_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i:s') }}</td>
            </tr>
        @empty
            <x-table-empty :cols="6" icon="audit" title="لا توجد سجلات مطابقة"
                           description="كل إجراء حساس في المنصة يُسجَّل هنا. غيّر نطاق التاريخ أو امسح التصفية." />
        @endforelse
    </x-data-table>

    <div class="mt-4">{{ $logs->links() }}</div>
</x-layouts.admin>
