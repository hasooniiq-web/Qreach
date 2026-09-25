<x-layouts.admin title="طلبات التواصل">
    <x-filter-bar>
        <select name="status" data-auto-submit aria-label="الحالة" class="form-control w-auto">
            <option value="">كل الحالات</option>
            <option value="new" @selected(request('status') === 'new')>جديد</option>
            <option value="read" @selected(request('status') === 'read')>مقروء</option>
            <option value="responded" @selected(request('status') === 'responded')>تم الرد</option>
            <option value="closed" @selected(request('status') === 'closed')>مغلق</option>
        </select>
    </x-filter-bar>

    <x-data-table :headers="['الزائر', 'المركبة', 'الرسالة', 'الحالة', 'التاريخ', '']">
        @forelse ($requests as $request)
            <tr class="hover:bg-ink-50 {{ $request->status === 'new' ? 'font-medium' : '' }}">
                <td class="px-4 py-3 text-ink-900">{{ $request->visitor_name ?? 'زائر' }}</td>
                <td class="px-4 py-3 text-ink-600">{{ $request->vehicle->plate_number }}</td>
                <td class="max-w-[250px] truncate px-4 py-3 text-ink-600">{{ $request->message }}</td>
                <td class="px-4 py-3"><x-status-badge :status="$request->status" /></td>
                <td class="px-4 py-3 text-ink-500">{{ $request->created_at->diffForHumans() }}</td>
                <td class="px-4 py-3 text-end">
                    <a href="{{ route('admin.contact-requests.show', $request) }}" class="text-sm font-medium text-ink-900 hover:underline">عرض</a>
                </td>
            </tr>
        @empty
            <x-table-empty :cols="6" icon="contact" title="لا توجد طلبات تواصل"
                           description="عندما يراسل زائر مالك مركبة عبر صفحة الرمز، يظهر الطلب هنا." />
        @endforelse
    </x-data-table>

    <div class="mt-4">{{ $requests->links() }}</div>
</x-layouts.admin>
