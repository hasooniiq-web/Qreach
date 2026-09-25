<x-layouts.admin :title="'الرمز '.$tag->serial_number">
    <x-page-header :title="$tag->serial_number"
                   :subtitle="strtoupper($tag->type).' — أُنشئ في '.$tag->created_at->format('Y-m-d H:i')"
                   :back="route('admin.tags.index')" backLabel="كل الرموز">
        <x-slot:actions><x-status-badge :status="$tag->status" /></x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-card class="text-center">
            <h3 class="mb-3 text-start text-sm font-semibold text-ink-900">
                {{ $tag->type === 'nfc' ? 'رمز QR لنفس الرابط (بديل عن الشريحة)' : 'رمز QR' }}
            </h3>
            <img src="{{ route('admin.tags.image', [$tag, 'png']) }}" alt="QR {{ $tag->serial_number }}"
                 class="mx-auto size-64 rounded-lg border border-ink-100">
            <div class="mt-3 flex justify-center gap-2">
                <a href="{{ route('admin.tags.image', [$tag, 'png']) }}" download="{{ $tag->serial_number }}.png" class="btn-secondary">PNG</a>
                <a href="{{ route('admin.tags.image', [$tag, 'svg']) }}" download="{{ $tag->serial_number }}.svg" class="btn-secondary">SVG</a>
            </div>
        </x-card>

        <div class="space-y-4">
            <x-card>
                <h3 class="mb-3 text-sm font-semibold text-ink-900">الرابط المشفّر</h3>
                <input type="text" readonly value="{{ $url }}" dir="ltr" class="form-control text-start text-xs" aria-label="الرابط">
                <p class="mt-2 text-xs text-ink-500">
                    هذا هو الرابط الذي يقرؤه الهاتف عند مسح الـ QR أو تقريبه من شريحة NFC.
                    @if ($tag->type === 'nfc') اكتبه على الشريحة كسجلّ NDEF من نوع URL (مثلًا بتطبيق NFC Tools). @endif
                </p>
                @if (str_contains($url, 'localhost') || str_contains($url, '127.0.0.1'))
                    <p class="mt-2 rounded-md bg-status-warning/10 p-2 text-xs text-status-warning">
                        تنبيه: الرابط يشير إلى localhost ولن يفتح على هاتف الزائر. اضبط APP_URL على عنوان الخادم الحقيقي.
                    </p>
                @endif
            </x-card>

            <x-card>
                <h3 class="mb-3 text-sm font-semibold text-ink-900">التفاصيل</h3>
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-xs text-ink-400">النوع</dt><dd class="uppercase text-ink-900">{{ $tag->type }}</dd></div>
                    <div><dt class="text-xs text-ink-400">الدفعة</dt><dd class="text-ink-900">{{ $tag->batch?->batch_code ?? '—' }}</dd></div>
                    @if ($tag->type === 'nfc')
                        <div>
                            <dt class="text-xs text-ink-400">حالة الشريحة</dt>
                            <dd class="text-ink-900">{{ ['unprovisioned' => 'لم تُكتب بعد', 'provisioned' => 'مكتوبة', 'locked' => 'مقفلة'][$tag->nfcTag?->provisioning_status] ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-400">معرّف الشريحة (UID)</dt>
                            <dd class="font-mono text-xs text-ink-900" dir="ltr">{{ $tag->nfcTag?->chip_uid ?? '—' }}</dd>
                        </div>
                    @endif
                    <div><dt class="text-xs text-ink-400">تاريخ الربط</dt><dd class="text-ink-900">{{ $tag->assigned_at?->format('Y-m-d') ?? '—' }}</dd></div>
                </dl>
            </x-card>

            <x-card>
                <h3 class="mb-3 text-sm font-semibold text-ink-900">المركبة والمالك</h3>
                @if ($tag->vehicle)
                    <a href="{{ route('admin.vehicles.show', $tag->vehicle) }}" class="text-sm font-medium text-ink-900 hover:underline">{{ $tag->vehicle->plate_number }}</a>
                    <p class="text-sm text-ink-500">{{ $tag->vehicle->user?->name ?? 'بدون اسم' }} — <span dir="ltr">{{ $tag->vehicle->user?->phone }}</span></p>
                @else
                    <p class="text-sm text-ink-400">غير مربوط بأي مركبة.</p>
                @endif
            </x-card>
        </div>
    </div>
</x-layouts.admin>
