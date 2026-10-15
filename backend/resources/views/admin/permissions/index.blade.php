<x-layouts.admin title="الصلاحيات">
    <p class="mb-5 text-sm text-ink-500">كل صلاحية في النظام، والأدوار التي تملكها حاليًا. لتغيير من يملك صلاحية، عدّل الدور من <a href="{{ route('admin.roles.index') }}" class="font-medium text-ink-900 hover:underline">صفحة الأدوار</a>.</p>

    @foreach ($permissions as $group => $items)
        <h3 class="mb-2 mt-6 text-sm font-semibold text-ink-900 first:mt-0">{{ \App\Models\Permission::groupLabel($group) }}</h3>
        <div class="mb-2">
            <x-data-table :headers="['الصلاحية', 'الأدوار']">
                @foreach ($items as $permission)
                    <tr class="hover:bg-ink-50">
                        <td class="px-4 py-3 text-ink-900">{{ $permission->label }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @forelse ($permission->roles as $role)
                                    <span class="badge muted bg-ink-100 text-ink-600 ring-ink-200">{{ $role->name_ar }}</span>
                                @empty
                                    <span class="text-xs text-ink-300">لا أحد</span>
                                @endforelse
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-data-table>
        </div>
    @endforeach
</x-layouts.admin>
