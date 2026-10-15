{{-- Every permission, grouped, each with its own checkbox — the actual "assign
     individual permissions without changing source code" control. --}}
<x-card padding="p-0">
    <h3 class="border-b border-ink-100 px-5 py-4 text-sm font-semibold text-ink-900">الصلاحيات</h3>
    <div class="grid grid-cols-1 gap-x-6 gap-y-5 p-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($permissionGroups as $group => $permissions)
            <fieldset>
                <legend class="mb-2 flex items-center gap-2 text-xs font-semibold text-ink-500">
                    {{ \App\Models\Permission::groupLabel($group) }}
                    <button type="button" data-select-group="{{ $group }}" class="text-ink-400 underline hover:text-ink-700">تحديد الكل</button>
                </legend>
                <div class="space-y-1.5">
                    @foreach ($permissions as $permission)
                        <label class="flex items-start gap-2 text-sm text-ink-700">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                   data-permission-group="{{ $group }}"
                                   @checked(in_array($permission->id, $currentIds))
                                   class="mt-0.5 size-4 rounded border-ink-300 accent-brand-yellow">
                            <span>
                                <span class="block">{{ $permission->label }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach
    </div>
</x-card>
