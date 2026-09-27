<x-layouts.admin title="الأدوار والصلاحيات">
    <div class="mb-5 flex items-center justify-between gap-3">
        <p class="text-sm text-ink-500">أدوار النظام (<x-icon name="shield" class="inline size-3.5" /> أساسي) لا يمكن حذفها، لكن يمكن تعديل صلاحياتها. أنشئ أدوارًا مخصصة بحرية.</p>
        @if (auth('admin')->user()->hasPermission('roles.create'))
            <a href="{{ route('admin.roles.create') }}" class="btn-accent shrink-0">+ دور جديد</a>
        @endif
    </div>

    <x-data-table :headers="['الدور', 'الوصف', 'الصلاحيات', 'المسؤولون', '']">
        @foreach ($roles as $role)
            <tr class="hover:bg-ink-50">
                <td class="px-4 py-3 font-medium text-ink-900">
                    {{ $role->name_ar }}
                    @if ($role->is_system)<span class="badge muted ms-1 bg-ink-100 text-ink-500 ring-ink-200">أساسي</span>@endif
                </td>
                <td class="max-w-xs truncate px-4 py-3 text-ink-500">{{ $role->description ?? '—' }}</td>
                <td class="px-4 py-3 tabular-nums text-ink-600">{{ $role->permissions_count }}</td>
                <td class="px-4 py-3 tabular-nums text-ink-600">{{ $role->admins_count }}</td>
                <td class="px-4 py-3 text-end">
                    <div class="flex items-center justify-end gap-4">
                        <a href="{{ route('admin.roles.edit', $role) }}" class="text-sm font-medium text-ink-900 hover:underline">تعديل الصلاحيات</a>
                        @if (! $role->is_system && $role->admins_count === 0)
                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                                  data-confirm-title="حذف الدور؟" data-confirm="هذا إجراء نهائي." data-confirm-label="حذف">
                                @csrf @method('DELETE')
                                <button class="text-sm text-status-danger hover:underline">حذف</button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    </x-data-table>
</x-layouts.admin>
