<x-layouts.admin :title="'تعديل صلاحيات '.$admin->name">
    <div class="max-w-lg rounded-lg border border-ink-200 bg-white p-6">
        <form method="POST" action="{{ route('admin.admins.update', $admin) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <p class="text-sm font-medium text-ink-900">{{ $admin->name }}</p>
                <p class="text-xs text-ink-400" dir="ltr">{{ $admin->email }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700 mb-2">الأدوار</label>
                <div class="space-y-2">
                    @php $currentRoleIds = $admin->roles->pluck('id')->all(); @endphp
                    @foreach ($roles as $role)
                        <label class="flex items-start gap-2 text-sm text-ink-700">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, old('roles', $currentRoleIds)))
                                   class="mt-0.5 rounded border-ink-300 text-brand-yellow focus:ring-brand-yellow">
                            <span>
                                <span class="block font-medium text-ink-900">{{ $role->name_ar }}</span>
                                @if ($role->description)
                                    <span class="block text-xs text-ink-400">{{ $role->description }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="rounded-md bg-brand-black text-white text-sm font-medium px-5 py-2.5 hover:bg-ink-800">حفظ التغييرات</button>
                <a href="{{ route('admin.admins.index') }}" class="text-sm text-ink-500 hover:underline">إلغاء</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
