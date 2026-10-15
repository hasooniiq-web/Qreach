<x-layouts.admin title="أرقام الدعم الفني">
    <x-page-header title="أرقام الدعم الفني" subtitle="أرقام واتساب تظهر للعميل في «حسابي ← الدعم الفني». اترك الصف فارغًا لإخفائه."
                   :back="route('admin.support.index')" backLabel="الدعم الفني" />

    <form method="POST" action="{{ route('admin.support.settings.save') }}" class="max-w-xl space-y-4">
        @csrf
        @method('PUT')
        <x-card class="space-y-4">
            @foreach ($rows as $i => $row)
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="label-{{ $i }}" class="mb-1 block text-xs text-ink-500">الاسم (مثل: الدعم، المبيعات)</label>
                        <input id="label-{{ $i }}" name="numbers[{{ $i }}][label]" value="{{ old("numbers.$i.label", $row['label']) }}" maxlength="40" class="form-control">
                    </div>
                    <div>
                        <label for="phone-{{ $i }}" class="mb-1 block text-xs text-ink-500">رقم الواتساب</label>
                        <input id="phone-{{ $i }}" name="numbers[{{ $i }}][phone]" value="{{ old("numbers.$i.phone", $row['phone']) }}" maxlength="32" dir="ltr" class="form-control text-start" placeholder="07701234567">
                        @error("numbers.$i.phone")<p class="mt-1 text-xs text-status-danger">{{ $message }}</p>@enderror
                    </div>
                </div>
            @endforeach
        </x-card>
        <button type="submit" class="btn-primary">حفظ الأرقام</button>
    </form>
</x-layouts.admin>
