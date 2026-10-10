<x-layouts.admin title="إنشاء دفعة رموز جديدة">
    <x-page-header title="دفعة جديدة" subtitle="اختر نوع الرموز وكميتها، ثم يُولِّد النظام رموزًا فريدة وآمنة دفعة واحدة."
                   :back="route('admin.tag-batches.index')" backLabel="كل الدفعات" />

    <form method="POST" action="{{ route('admin.tag-batches.store') }}" class="max-w-xl space-y-4">
        @csrf

        <x-card padding="p-6">
            <fieldset>
                <legend class="mb-3 text-sm font-semibold text-ink-900">١. نوع الرمز</legend>
                <div class="grid grid-cols-2 gap-3">
                    @foreach (['qr' => ['QR', 'ملصق مطبوع يُمسح بكاميرا الهاتف', 'qrcode'], 'nfc' => ['NFC', 'شريحة تُقرأ بالتقريب من الهاتف', 'nfc']] as $value => [$title, $desc, $icon])
                        <label class="cursor-pointer">
                            <input type="radio" name="tag_type" value="{{ $value }}" class="peer sr-only" @checked(old('tag_type', 'qr') === $value)>
                            <span class="flex h-full flex-col gap-2 rounded-lg border border-ink-200 p-4 transition-colors hover:bg-ink-50 peer-checked:border-brand-black peer-checked:ring-2 peer-checked:ring-brand-yellow peer-focus-visible:ring-2 peer-focus-visible:ring-brand-yellow">
                                <x-icon :name="$icon" class="size-6 text-ink-700" />
                                <span class="text-sm font-semibold text-ink-900">{{ $title }}</span>
                                <span class="text-xs text-ink-500">{{ $desc }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('tag_type')<p class="mt-2 text-xs text-status-danger" role="alert">{{ $message }}</p>@enderror
            </fieldset>
        </x-card>

        <x-card padding="p-6" class="space-y-5">
            <h3 class="text-sm font-semibold text-ink-900">٢. الكمية والتفاصيل</h3>

            @php $maxQuantity = (int) \App\Models\SystemSetting::get('qr_batch_max_quantity', 1000); @endphp
            <x-field label="الكمية" name="quantity" :hint="'الحد الأقصى الحالي '.number_format($maxQuantity).' رمز لكل دفعة، ويمكن رفعه من إعدادات النظام حتى '.number_format(\App\Services\QrBatchService::MAX_QUANTITY).'.'">
                <input type="text" inputmode="numeric" data-thousands id="quantity" name="quantity" value="{{ number_format((int) old('quantity', 100)) }}" required dir="ltr" class="form-control text-start">
            </x-field>

            <x-field label="ملاحظات (اختياري)" name="notes" hint="للاستخدام الداخلي فقط، مثل اسم المورّد أو رقم أمر الطباعة.">
                <textarea id="notes" name="notes" rows="2" maxlength="500" class="form-control">{{ old('notes') }}</textarea>
            </x-field>
        </x-card>

        <div class="flex items-center gap-3">
            <button type="submit" class="btn-primary">إنشاء الدفعة</button>
            <a href="{{ route('admin.tag-batches.index') }}" class="text-sm text-ink-500 hover:underline">إلغاء</a>
        </div>
        <p class="text-xs text-ink-400">بعد الإنشاء يمكنك تصدير الرموز بصيغة CSV أو طباعتها (لرموز QR).</p>
    </form>
</x-layouts.admin>
