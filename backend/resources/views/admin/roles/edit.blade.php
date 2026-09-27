<x-layouts.admin :title="'تعديل — '.$role->name_ar">
    <x-page-header :title="$role->name_ar" :subtitle="$role->is_system ? 'دور أساسي في النظام — الاسم ثابت، الصلاحيات قابلة للتعديل.' : $role->description"
                   :back="route('admin.roles.index')" backLabel="كل الأدوار" />

    <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="space-y-5">
        @csrf
        @method('PUT')

        @unless ($role->is_system)
            <x-card class="max-w-lg space-y-4">
                <x-field label="اسم الدور (بالعربية)" name="name_ar">
                    <input id="name_ar" name="name_ar" value="{{ old('name_ar', $role->name_ar) }}" required maxlength="100" class="form-control">
                </x-field>
                <x-field label="الوصف (اختياري)" name="description">
                    <input id="description" name="description" value="{{ old('description', $role->description) }}" maxlength="255" class="form-control">
                </x-field>
            </x-card>
        @endunless

        @include('admin.roles.partials.permission-picker', ['permissionGroups' => $permissionGroups, 'currentIds' => old('permissions', $currentIds)])

        <div class="flex items-center gap-3">
            <button type="submit" class="btn-primary">حفظ التغييرات</button>
            <a href="{{ route('admin.roles.index') }}" class="text-sm text-ink-500 hover:underline">إلغاء</a>
        </div>
    </form>
</x-layouts.admin>
