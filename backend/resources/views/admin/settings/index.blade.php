<x-layouts.admin title="إعدادات النظام">
    <p class="mb-6 text-sm text-ink-500">قيم تغيّر سلوك المنصة مباشرة. كل تغيير يُسجَّل في سجل التدقيق.</p>

    <x-card class="max-w-xl" padding="p-6">
        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <h3 class="mb-4 text-sm font-semibold text-ink-900">أمان وتحقق الهوية</h3>
                <div class="space-y-5">
                    @foreach ($settings->only(['otp_code_ttl_minutes', 'otp_resend_cooldown_seconds']) as $setting)
                        <x-field :label="$setting['label']" :name="$setting['key']" :hint="$setting['hint'] ?? null">
                            <input type="text" inputmode="numeric" data-thousands id="{{ $setting['key'] }}" name="{{ $setting['key'] }}"
                                   value="{{ number_format((int) old($setting['key'], $setting['value'])) }}" required dir="ltr" class="form-control text-start">
                        </x-field>
                    @endforeach
                </div>
            </div>

            <div class="border-t border-ink-100 pt-6">
                <h3 class="mb-4 text-sm font-semibold text-ink-900">الرموز والدفعات</h3>
                <div class="space-y-5">
                    @foreach ($settings->only(['qr_batch_max_quantity']) as $setting)
                        <x-field :label="$setting['label']" :name="$setting['key']" :hint="$setting['hint'] ?? null">
                            <input type="text" inputmode="numeric" data-thousands id="{{ $setting['key'] }}" name="{{ $setting['key'] }}"
                                   value="{{ number_format((int) old($setting['key'], $setting['value'])) }}" required dir="ltr" class="form-control text-start">
                        </x-field>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="btn-primary">حفظ الإعدادات</button>
        </form>
    </x-card>
</x-layouts.admin>
