<x-layouts.admin :title="'الرمز '.$tag->serial_number">
    <x-page-header :title="$tag->serial_number"
                   :subtitle="$tag->typeLabel().' — أُنشئ في '.$tag->created_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i')"
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
                    @if ($tag->hasNfc()) اكتبه على الشريحة كسجلّ NDEF من نوع URL (مثلًا بتطبيق NFC Tools). @endif
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
                    <div><dt class="text-xs text-ink-400">النوع</dt><dd class="text-ink-900">{{ $tag->typeLabel() }}</dd></div>
                    <div><dt class="text-xs text-ink-400">الدفعة</dt><dd class="text-ink-900">{{ $tag->batch?->batch_code ?? '—' }}</dd></div>
                    @if ($tag->hasNfc())
                        <div>
                            <dt class="text-xs text-ink-400">حالة الشريحة</dt>
                            <dd class="text-ink-900">{{ ['unprovisioned' => 'لم تُكتب بعد', 'provisioned' => 'مكتوبة', 'locked' => 'مقفلة'][$tag->nfcTag?->provisioning_status] ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-400">معرّف الشريحة (UID)</dt>
                            <dd class="font-mono text-xs text-ink-900" dir="ltr">{{ $tag->nfcTag?->chip_uid ?? '—' }}</dd>
                        </div>
                    @endif
                    <div><dt class="text-xs text-ink-400">تاريخ الربط</dt><dd class="text-ink-900">{{ $tag->assigned_at?->copy()->timezone(config('app.display_timezone'))->format('Y-m-d') ?? '—' }}</dd></div>
                </dl>
            </x-card>

            <x-card>
                <h3 class="mb-3 text-sm font-semibold text-ink-900">المركبة والمالك</h3>
                @if ($tag->vehicle)
                    <a href="{{ route('admin.vehicles.show', $tag->vehicle) }}" class="text-sm font-medium text-ink-900 hover:underline">{{ $tag->vehicle->plate_number }}</a>
                    <p class="text-sm text-ink-500">{{ $tag->vehicle->user?->name ?? 'بدون اسم' }} — <span dir="ltr">{{ $tag->vehicle->user?->phone }}</span></p>
                    @if ($tag->vehicle->user)
                        <a href="{{ route('admin.users.show', $tag->vehicle->user) }}" class="text-xs font-medium text-ink-600 hover:underline">عرض ملف المستخدم ←</a>
                    @endif
                @else
                    <p class="text-sm text-ink-400">غير مربوط بأي مركبة.</p>
                @endif
            </x-card>

            @if ($tag->replaces)
                <x-card>
                    <h3 class="mb-2 text-sm font-semibold text-ink-900">استبدل هذا الرمز</h3>
                    <a href="{{ route('admin.tags.show', $tag->replaces) }}" class="text-sm font-medium text-ink-900 hover:underline tabular-nums">{{ $tag->replaces->serial_number }}</a>
                    <span class="text-xs text-ink-500"> — {{ $tag->replaces->revocation_reason }}</span>
                </x-card>
            @endif

            @if ($tag->status === 'assigned' && auth('admin')->user()->hasPermission('qr.replace') || (auth('admin')->user()->hasPermission('nfc.replace') && $tag->hasNfc()))
                @if ($tag->status === 'assigned')
                    <x-card>
                        <h3 class="mb-1 text-sm font-semibold text-ink-900">استبدال الرمز</h3>
                        <p class="mb-3 text-xs text-ink-500">يصبح هذا الرمز غير قابل للاستخدام نهائيًا («استُبدل») وينتقل ربط المركبة إلى الرمز الجديد فورًا.</p>
                        <form method="POST" action="{{ route('admin.tags.replace', $tag) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label for="new_tag_serial" class="mb-1 block text-xs font-medium text-ink-700">الرقم التسلسلي للرمز الجديد ({{ strtoupper($tag->type) }} غير مُعيّن)</label>
                                <input list="replacement-candidates" id="new_tag_serial" name="new_tag_serial" required
                                       value="{{ old('new_tag_serial') }}" dir="ltr" class="form-control text-start" placeholder="ST-C-20261010-000123">
                                <datalist id="replacement-candidates">
                                    @foreach ($replacementCandidates as $serial)
                                        <option value="{{ $serial }}"></option>
                                    @endforeach
                                </datalist>
                                @error('new_tag_serial')<p class="mt-1 text-xs text-status-danger" role="alert">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="replace-reason" class="mb-1 block text-xs font-medium text-ink-700">سبب الاستبدال</label>
                                <input id="replace-reason" type="text" name="reason" required maxlength="255" value="{{ old('reason') }}" class="form-control">
                            </div>
                            <button type="submit" class="btn-accent w-full"
                                    data-confirm-title="استبدال الرمز؟"
                                    data-confirm="سيصبح {{ $tag->serial_number }} غير قابل للاستخدام نهائيًا فور تأكيد الاستبدال."
                                    data-confirm-label="تأكيد الاستبدال">استبدال</button>
                        </form>
                    </x-card>
                @endif
            @endif
        </div>
    </div>

    <x-card class="mt-4" padding="p-0">
        <h3 class="border-b border-ink-100 px-5 py-4 text-sm font-semibold text-ink-900">سجلّ دورة حياة الرمز</h3>
        @forelse ($tag->lifecycleEvents as $event)
            <div class="flex items-start gap-3 border-b border-ink-100 px-5 py-3 last:border-0">
                <span class="mt-1 size-2 shrink-0 rounded-full bg-ink-300"></span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-ink-900">
                        @if ($event->previous_status)
                            <x-status-badge :status="$event->previous_status" /> ← <x-status-badge :status="$event->new_status" />
                        @else
                            <x-status-badge :status="$event->new_status" />
                        @endif
                    </p>
                    @if ($event->reason)<p class="mt-1 text-xs text-ink-500">{{ $event->reason }}</p>@endif
                    <p class="mt-1 text-xs text-ink-400">
                        {{ match($event->performed_by_type) { 'admin' => 'مسؤول', 'customer' => 'المالك', default => 'النظام' } }}
                        — {{ $event->created_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i') }}
                    </p>
                </div>
            </div>
        @empty
            <p class="px-5 py-8 text-center text-sm text-ink-400">لا يوجد سجلّ بعد.</p>
        @endforelse
    </x-card>
</x-layouts.admin>
