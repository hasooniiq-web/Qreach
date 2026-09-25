<x-layouts.admin :title="'تعديل صلاحيات '.$admin->name">
    <x-page-header :title="$admin->name" :subtitle="$admin->email" :back="route('admin.admins.index')" backLabel="كل المسؤولين" />

    <form method="POST" action="{{ route('admin.admins.update', $admin) }}" class="max-w-lg space-y-4">
        @csrf
        @method('PUT')

        <x-card padding="p-6">
            <fieldset>
                <legend class="mb-3 text-sm font-semibold text-ink-900">الأدوار والصلاحيات</legend>
                <div class="space-y-3">
                    @php $currentRoleIds = $admin->roles->pluck('id')->all(); @endphp
                    @foreach ($roles as $role)
                        <label class="flex items-start gap-2.5 text-sm text-ink-700">
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
            </fieldset>
        </x-card>

        <div class="flex items-center gap-3">
            <button type="submit" class="btn-primary">حفظ التغييرات</button>
            <a href="{{ route('admin.admins.index') }}" class="text-sm text-ink-500 hover:underline">إلغاء</a>
        </div>
    </form>
</x-layouts.admin>
