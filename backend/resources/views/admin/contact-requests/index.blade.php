<x-layouts.admin title="طلبات التواصل">
    <form method="GET" class="flex flex-wrap items-center gap-3 mb-5">
        <select name="status" class="rounded-md border border-ink-200 px-3 py-2 text-sm" onchange="this.form.submit()">
            <option value="">كل الحالات</option>
            <option value="new" @selected(request('status') === 'new')>جديد</option>
            <option value="read" @selected(request('status') === 'read')>مقروء</option>
            <option value="responded" @selected(request('status') === 'responded')>تم الرد</option>
            <option value="closed" @selected(request('status') === 'closed')>مغلق</option>
        </select>
    </form>

    <x-card padding="p-0" class="overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-ink-50 text-ink-500 text-xs sticky top-0">
                <tr>
                    <th class="text-start font-medium px-4 py-3">الزائر</th>
                    <th class="text-start font-medium px-4 py-3">المركبة</th>
                    <th class="text-start font-medium px-4 py-3">الرسالة</th>
                    <th class="text-start font-medium px-4 py-3">الحالة</th>
                    <th class="text-start font-medium px-4 py-3">التاريخ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                @forelse ($requests as $request)
                    <tr class="hover:bg-ink-50 {{ $request->status === 'new' ? 'font-medium' : '' }}">
                        <td class="px-4 py-3 text-ink-900">{{ $request->visitor_name ?? 'زائر' }}</td>
                        <td class="px-4 py-3 text-ink-600">{{ $request->vehicle->plate_number }}</td>
                        <td class="px-4 py-3 text-ink-600 truncate max-w-[250px]">{{ $request->message }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$request->status" /></td>
                        <td class="px-4 py-3 text-ink-500">{{ $request->created_at->diffForHumans() }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.contact-requests.show', $request) }}" class="text-sm text-ink-900 font-medium hover:underline">عرض</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-ink-400">لا توجد طلبات تواصل.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-card>

    <div class="mt-4">{{ $requests->links() }}</div>
</x-layouts.admin>
