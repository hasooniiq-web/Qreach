<x-layouts.admin title="دور جديد">
    <x-page-header title="دور جديد" :back="route('admin.roles.index')" backLabel="كل الأدوار" />

    <form method="POST" action="{{ route('admin.roles.store') }}" class="space-y-5">
        @csrf
        <x-card class="max-w-lg space-y-4">
            <x-field label="اسم الدور (بالعربية)" name="name_ar">
                <input id="name_ar" name="name_ar" value="{{ old('name_ar') }}" required maxlength="100" class="form-control">
            </x-field>
            <x-field label="الوصف (اختياري)" name="description">
                <input id="description" name="description" value="{{ old('description') }}" maxlength="255" class="form-control">
            </x-field>
        </x-card>

        @include('admin.roles.partials.permission-picker', ['permissionGroups' => $permissionGroups, 'currentIds' => old('permissions', [])])

        <div class="flex items-center gap-3">
            <button type="submit" class="btn-primary">إنشاء الدور</button>
            <a href="{{ route('admin.roles.index') }}" class="text-sm text-ink-500 hover:underline">إلغاء</a>
        </div>
    </form>
</x-layouts.admin>
