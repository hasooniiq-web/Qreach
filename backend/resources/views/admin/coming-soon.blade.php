<x-layouts.admin :title="$title">
    <x-card padding="p-0">
        <div class="flex flex-col items-center justify-center px-6 py-20 text-center">
            <x-icon name="bell" class="mb-4 size-10 text-ink-300" />
            <h2 class="mb-1 text-base font-semibold text-ink-900">{{ $title }}</h2>
            <p class="max-w-sm text-sm text-ink-500">هذا القسم غير جاهز بعد. سيظهر هنا فور اكتماله، ولا توجد بيانات معروضة حاليًا بدل عرض أرقام غير حقيقية.</p>
        </div>
    </x-card>
</x-layouts.admin>
