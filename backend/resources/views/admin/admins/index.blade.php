<x-layouts.admin title="المسؤولون والصلاحيات">
    <div class="mb-5 flex items-center justify-end">
        <a href="{{ route('admin.admins.create') }}" class="btn-accent">+ إضافة مسؤول جديد</a>
    </div>

    <x-data-table :headers="['الاسم', 'البريد الإلكتروني', 'الأدوار', 'الحالة', '']">
        @foreach ($admins as $admin)
            <tr class="hover:bg-ink-50">
                <td class="px-4 py-3 font-medium text-ink-900">{{ $admin->name }}</td>
                <td class="px-4 py-3 text-ink-600" dir="ltr">{{ $admin->email }}</td>
                <td class="px-4 py-3 text-ink-600">
                    @foreach ($admin->roles as $role)
                        <span class="me-1 inline-flex items-center rounded-full bg-ink-100 px-2 py-0.5 text-xs text-ink-700">{{ $role->name_ar }}</span>
                    @endforeach
                </td>
                <td class="px-4 py-3"><x-status-badge :status="$admin->status" /></td>
                <td class="px-4 py-3">
                    <div class="flex items-center justify-end gap-4">
                        <a href="{{ route('admin.admins.edit', $admin) }}" class="text-sm font-medium text-ink-900 hover:underline">تعديل الصلاحيات</a>
                        @if ($admin->id !== auth('admin')->id())
                            @if ($admin->status === 'active')
                                <form method="POST" action="{{ route('admin.admins.suspend', $admin) }}"
                                      data-confirm="سيفقد {{ $admin->name }} القدرة على الدخول إلى لوحة التحكم فورًا. يمكنك إعادة التفعيل لاحقًا."
                                      data-confirm-title="إيقاف المسؤول؟" data-confirm-label="إيقاف">
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
                    </div>
                </td>
            </tr>
        @endforeach
    </x-data-table>

    <div class="mt-4">{{ $admins->links() }}</div>
</x-layouts.admin>
