<x-layouts.admin title="إنشاء دفعة رموز جديدة">
    <x-page-header title="دفعة جديدة" subtitle="اختر نوع الرموز وكميتها، ثم يُولِّد النظام رموزًا فريدة وآمنة دفعة واحدة."
                   :back="route('admin.tag-batches.index')" backLabel="كل الدفعات" />

    <form method="POST" action="{{ route('admin.tag-batches.store') }}" class="max-w-xl space-y-4">
        @csrf

        <x-card padding="p-6">
            <h3 class="mb-3 text-sm font-semibold text-ink-900">١. نوع الرمز</h3>
            {{-- Every new code is unified: one printed QR and one NFC chip, both holding the same link. --}}
            <input type="hidden" name="tag_type" value="code">
            <div class="flex items-start gap-4 rounded-lg border border-brand-black p-4 ring-2 ring-brand-yellow">
                <span class="flex gap-1 text-ink-700"><x-icon name="qrcode" class="size-6" /><x-icon name="nfc" class="size-6" /></span>
                <span>
                    <span class="block text-sm font-semibold text-ink-900">رمز موحّد: QR + NFC</span>
                    <span class="mt-1 block text-xs leading-relaxed text-ink-500">
                        كل رمز يحمل ملصق QR مطبوعًا وشريحة NFC تحويان الرابط نفسه. تعتمد الخدمة اليوم على QR، ويمكن كتابة الشريحة لاحقًا دون تغيير الرمز.
                    </span>
                </span>
            </div>
            @error('tag_type')<p class="mt-2 text-xs text-status-danger" role="alert">{{ $message }}</p>@enderror
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
