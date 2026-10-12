<x-layouts.admin title="دفعة قسائم جديدة">
    <x-page-header title="دفعة قسائم جديدة" subtitle="يُولِّد النظام أرقامًا عشوائية فريدة من 16 خانة. لا يمكن تعديل الرقم بعد إنشائه."
                   :back="route('admin.vouchers.index')" backLabel="قسائم التفعيل" />

    <form method="POST" action="{{ route('admin.vouchers.store') }}" class="max-w-xl space-y-4">
        @csrf

        <x-card padding="p-6" class="space-y-5">
            <x-field label="الباقة التي تُفعّلها القسيمة" name="plan">
                <select id="plan" name="plan" class="form-control" required>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->slug }}" @selected(old('plan') === $plan->slug)>{{ $plan->name_ar }}</option>
                    @endforeach
                </select>
            </x-field>

            <div class="grid grid-cols-2 gap-4">
                <x-field label="الكمية" name="quantity" :hint="'حتى '.number_format($max).' قسيمة في الدفعة'">
                    <input id="quantity" name="quantity" value="{{ old('quantity', '1,000') }}" required inputmode="numeric" data-thousands dir="ltr" class="form-control text-start">
                </x-field>
                <x-field label="مدة التفعيل (يوم)" name="duration_days" hint="365 = سنة واحدة">
                    <input id="duration_days" name="duration_days" value="{{ old('duration_days', 365) }}" required inputmode="numeric" dir="ltr" class="form-control text-start">
                </x-field>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <x-field label="سعر بيع القسيمة (د.ع)" name="unit_price" hint="للقسيمة الواحدة. اتركه فارغًا إن لم تُحدَّد.">
                    <input id="unit_price" name="unit_price" value="{{ old('unit_price') }}" inputmode="numeric" data-thousands dir="ltr" class="form-control text-start">
                </x-field>
                @if (auth('admin')->user()->hasPermission('trade.manage'))
                    <x-field label="حساب الشركة (المخزون والحسابات)" name="customer_id" hint="عند اختياره مع السعر يُسجَّل المبلغ تلقائيًا على حسابه.">
                        <select id="customer_id" name="customer_id" class="form-control">
                            <option value="">— بدون —</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </x-field>
                @endif
            </div>

            <x-field label="الجهة المستلمة (اختياري)" name="recipient" hint="الشركة أو الموزّع الذي ستُسلَّم له هذه الدفعة. يمكن تعديلها لاحقًا.">
                <input id="recipient" name="recipient" value="{{ old('recipient') }}" maxlength="160" class="form-control">
            </x-field>

            <x-field label="صالحة للاستخدام حتى (اختياري)" name="expires_at" hint="بعد هذا التاريخ لا تعمل القسائم غير المستخدمة. اتركه فارغًا لقسائم بلا تاريخ انتهاء.">
                <input id="expires_at" type="date" name="expires_at" value="{{ old('expires_at') }}" class="form-control">
            </x-field>

            <x-field label="ملاحظات (اختياري)" name="notes">
                <textarea id="notes" name="notes" rows="2" maxlength="500" class="form-control">{{ old('notes') }}</textarea>
            </x-field>
        </x-card>

        <div class="flex items-center gap-3">
            <button type="submit" class="btn-primary">إنشاء الدفعة</button>
            <a href="{{ route('admin.vouchers.index') }}" class="text-sm text-ink-500 hover:underline">إلغاء</a>
        </div>
    </form>
</x-layouts.admin>
