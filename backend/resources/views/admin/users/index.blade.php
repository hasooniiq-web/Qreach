<x-layouts.admin title="المستخدمون">
    <form method="GET" class="flex flex-wrap items-center gap-3 mb-5">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="ابحث بالاسم أو رقم الهاتف..."
               class="flex-1 min-w-[200px] rounded-md border border-ink-200 px-3 py-2 text-sm focus:border-brand-yellow focus:ring-2 focus:ring-brand-yellow/40 outline-none">
        <select name="status" class="rounded-md border border-ink-200 px-3 py-2 text-sm focus:border-brand-yellow outline-none" onchange="this.form.submit()">
            <option value="">كل الحالات</option>
            <option value="active" @selected(request('status') === 'active')>نشط</option>
            <option value="suspended" @selected(request('status') === 'suspended')>موقوف</option>
        </select>
        <button type="submit" class="rounded-md bg-brand-black text-white text-sm font-medium px-4 py-2">بحث</button>
    </form>

    <div class="rounded-lg border border-ink-200 bg-white overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-ink-50 text-ink-500 text-xs">
                <tr>
                    <th class="text-start font-medium px-4 py-3">المستخدم</th>
                    <th class="text-start font-medium px-4 py-3">رقم الهاتف</th>
                    <th class="text-start font-medium px-4 py-3">المركبات</th>
                    <th class="text-start font-medium px-4 py-3">الحالة</th>
                    <th class="text-start font-medium px-4 py-3">تاريخ التسجيل</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                @forelse ($users as $user)
                    <tr class="hover:bg-ink-50">
                        <td class="px-4 py-3 font-medium text-ink-900">{{ $user->name ?? 'بدون اسم' }}</td>
                        <td class="px-4 py-3 text-ink-600 tabular-nums" dir="ltr">{{ $user->phone }}</td>
                        <td class="px-4 py-3 text-ink-600">{{ $user->vehicles_count }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$user->status" /></td>
                        <td class="px-4 py-3 text-ink-500">{{ $user->created_at->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.users.show', $user) }}" class="text-sm text-ink-900 font-medium hover:underline">عرض</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-ink-400">لا يوجد مستخدمون مطابقون.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</x-layouts.admin>
