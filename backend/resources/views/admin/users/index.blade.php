<x-layouts.admin title="المستخدمون">
    <x-filter-bar>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="ابحث بالاسم أو رقم الهاتف..." aria-label="بحث"
               class="form-control min-w-[220px] flex-1">
        <select name="status" data-auto-submit aria-label="الحالة" class="form-control w-auto">
            <option value="">كل الحالات</option>
            <option value="active" @selected(request('status') === 'active')>نشط</option>
            <option value="suspended" @selected(request('status') === 'suspended')>موقوف</option>
        </select>
        <button type="submit" class="btn-primary">بحث</button>
    </x-filter-bar>

    <x-data-table :headers="['المستخدم', 'رقم الهاتف', 'المركبات', 'الحالة', 'تاريخ التسجيل', '']">
        @forelse ($users as $user)
            <tr class="hover:bg-ink-50">
                <td class="px-4 py-3 font-medium text-ink-900">{{ $user->name ?? 'بدون اسم' }}</td>
                <td class="px-4 py-3 tabular-nums text-ink-600" dir="ltr">{{ $user->phone }}</td>
                <td class="px-4 py-3 text-ink-600">{{ $user->vehicles_count }}</td>
                <td class="px-4 py-3"><x-status-badge :status="$user->status" /></td>
                <td class="px-4 py-3 text-ink-500">{{ $user->created_at->format('Y-m-d') }}</td>
                <td class="px-4 py-3 text-end">
                    <a href="{{ route('admin.users.show', $user) }}" class="text-sm font-medium text-ink-900 hover:underline">عرض</a>
                </td>
            </tr>
        @empty
            <x-table-empty :cols="6" icon="users" title="لا يوجد مستخدمون مطابقون"
                           description="جرّب تغيير البحث أو مسح التصفية. المستخدمون يظهرون هنا فور تسجيلهم عبر التطبيق." />
        @endforelse
    </x-data-table>

    <div class="mt-4">{{ $users->links() }}</div>
</x-layouts.admin>
