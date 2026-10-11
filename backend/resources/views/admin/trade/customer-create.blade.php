<x-layouts.admin title="عميل جديد">
    <x-page-header title="عميل جديد" subtitle="تاجر أو محل يستلم الملصقات ويسدّد لاحقًا."
                   :back="route('admin.trade.index')" backLabel="المخزون والحسابات" />

    <form method="POST" action="{{ route('admin.trade.customers.store') }}" class="max-w-xl space-y-4">
        @csrf
        <x-card padding="p-6" class="space-y-5">
            <x-field label="الاسم" name="name">
                <input id="name" name="name" value="{{ old('name') }}" required maxlength="120" class="form-control">
            </x-field>
            <x-field label="الهاتف (اختياري)" name="phone">
                <input id="phone" name="phone" value="{{ old('phone') }}" maxlength="32" dir="ltr" class="form-control text-start">
            </x-field>
            <x-field label="ملاحظات (اختياري)" name="notes">
                <textarea id="notes" name="notes" rows="2" maxlength="500" class="form-control">{{ old('notes') }}</textarea>
            </x-field>
        </x-card>
        <div class="flex items-center gap-3">
            <button type="submit" class="btn-primary">حفظ العميل</button>
            <a href="{{ route('admin.trade.index') }}" class="text-sm text-ink-500 hover:underline">إلغاء</a>
        </div>
    </form>
</x-layouts.admin>
