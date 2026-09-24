<x-layouts.admin title="المسؤولون والصلاحيات">
    <div class="flex items-center justify-end mb-5">
        <a href="{{ route('admin.admins.create') }}" class="rounded-md bg-brand-yellow text-brand-black text-sm font-medium px-4 py-2 hover:opacity-90">
            + إضافة مسؤول جديد
        </a>
    </div>

    <div class="rounded-lg border border-ink-200 bg-white overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-ink-50 text-ink-500 text-xs">
                <tr>
                    <th class="text-start font-medium px-4 py-3">الاسم</th>
                    <th class="text-start font-medium px-4 py-3">البريد الإلكتروني</th>
                    <th class="text-start font-medium px-4 py-3">الأدوار</th>
                    <th class="text-start font-medium px-4 py-3">الحالة</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                @foreach ($admins as $admin)
                    <tr class="hover:bg-ink-50">
                        <td class="px-4 py-3 font-medium text-ink-900">{{ $admin->name }}</td>
                        <td class="px-4 py-3 text-ink-600" dir="ltr">{{ $admin->email }}</td>
                        <td class="px-4 py-3 text-ink-600">
                            @foreach ($admin->roles as $role)
                                <span class="inline-flex items-center rounded-full bg-ink-100 text-ink-700 px-2 py-0.5 text-xs me-1">{{ $role->name_ar }}</span>
                            @endforeach
                        </td>
                        <td class="px-4 py-3"><x-status-badge :status="$admin->status" /></td>
                        <td class="px-4 py-3 flex items-center gap-3">
                            <a href="{{ route('admin.admins.edit', $admin) }}" class="text-sm text-ink-900 font-medium hover:underline">تعديل الصلاحيات</a>
                            @if ($admin->id !== auth('admin')->id())
                                @if ($admin->status === 'active')
                                    <form method="POST" action="{{ route('admin.admins.suspend', $admin) }}">
                                        @csrf
                                        <button class="text-sm text-status-danger hover:underline">إيقاف</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.admins.activate', $admin) }}">
                                        @csrf
                                        <button class="text-sm text-status-success hover:underline">تفعيل</button>
                                    </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $admins->links() }}</div>
</x-layouts.admin>
