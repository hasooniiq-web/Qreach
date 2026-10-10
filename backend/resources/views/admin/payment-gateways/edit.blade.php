@php $canManage = auth('admin')->user()->hasPermission('payment_gateways.manage'); @endphp

<x-layouts.admin :title="$driver->label()">
    <x-page-header :title="$driver->label()" :back="route('admin.payment-gateways.index')" backLabel="كل البوابات" />

    @unless ($allowed)
        <p class="mb-5 rounded-md bg-status-warning/15 px-4 py-3 text-sm text-ink-900">هذه البوابة غير مسموح بها في بيئة الإنتاج ولا يمكن تفعيلها.</p>
    @endunless

    <form method="POST" action="{{ route('admin.payment-gateways.update', $driver->driver()) }}" class="max-w-xl space-y-5" autocomplete="off">
        @csrf
        @method('PUT')

        <x-card padding="p-6" class="space-y-5">
            <h3 class="text-sm font-semibold text-ink-900">التفعيل</h3>
            <label class="flex items-center gap-2.5 text-sm text-ink-700">
                <input type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', $gateway->is_enabled)) @disabled(! $canManage || ! $allowed)
                       class="rounded border-ink-300 text-brand-yellow focus:ring-brand-yellow">
                <span>تفعيل هذه البوابة كوسيلة دفع في التطبيق</span>
            </label>
            @error('is_enabled')<p class="text-xs text-status-danger" role="alert">{{ $message }}</p>@enderror

            <label class="flex items-center gap-2.5 text-sm text-ink-700">
                <input type="checkbox" name="is_test_mode" value="1" @checked(old('is_test_mode', $gateway->is_test_mode)) @disabled(! $canManage)
                       class="rounded border-ink-300 text-brand-yellow focus:ring-brand-yellow">
                <span>وضع الاختبار (يظهر للعميل أن العملية تجريبية)</span>
            </label>
        </x-card>

        <x-card padding="p-6" class="space-y-5">
            <h3 class="text-sm font-semibold text-ink-900">بيانات الاعتماد</h3>
            @foreach ($driver->credentialFields() as $key => $field)
                <x-field :label="$field['label'].($field['required'] ? ' *' : '')" :name="'credentials.'.$key"
                         :hint="($field['hint'] ?? '').($set[$key] ? ' — محفوظ. اتركه فارغًا للإبقاء على القيمة الحالية.' : ' — غير مُدخل بعد.')">
                    <input type="{{ $field['secret'] ? 'password' : 'text' }}" id="credentials.{{ $key }}" name="credentials[{{ $key }}]"
                           value="{{ $field['secret'] ? '' : old('credentials.'.$key) }}" dir="ltr" autocomplete="new-password"
                           placeholder="{{ $set[$key] ? '••••••••••••' : '' }}" @disabled(! $canManage) class="form-control">
                </x-field>
            @endforeach
        </x-card>

        <x-card padding="p-6">
            <h3 class="mb-2 text-sm font-semibold text-ink-900">رابط الإشعارات (Webhook)</h3>
            <p class="mb-2 text-xs text-ink-500">أدخل هذا الرابط في لوحة مزوّد الدفع ليخبر الخادم بنتيجة كل عملية.</p>
            <code class="block break-all rounded-md bg-ink-50 px-3 py-2 text-xs text-ink-900" dir="ltr">{{ $webhookUrl }}</code>
        </x-card>

        @if ($gateway->updated_by)
            <p class="text-xs text-ink-400">آخر تعديل: {{ $gateway->updatedBy?->name }} — {{ $gateway->updated_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i') }}</p>
        @endif

        @if ($canManage)
            <div class="flex items-center gap-3">
                <button type="submit" class="btn-primary">حفظ</button>
                <a href="{{ route('admin.payment-gateways.index') }}" class="text-sm text-ink-500 hover:underline">إلغاء</a>
            </div>
        @endif
    </form>
</x-layouts.admin>
