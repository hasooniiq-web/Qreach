<x-layouts.public title="التواصل بخصوص المركبة">
    @push('head')
        @vite(['resources/js/public-scan.js'])
    @endpush

    @if (session('scan_status'))
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-status-success/30 bg-status-success/10 p-4" role="status">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-status-success text-white">
                <x-icon name="check" class="size-5" />
            </span>
            <p class="text-sm font-semibold text-status-success">{{ session('scan_status') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-status-danger/30 bg-status-danger/10 p-4 text-sm text-status-danger" role="alert">
            <ul class="list-disc space-y-1 pe-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Identity. By default a visitor learns nothing about the owner: the name,
         phone, emergency contact and any other contact method appear only if the
         owner explicitly switched them on (and their plan allows it). --}}
    <section class="mb-6 rounded-xl bg-brand-black p-5 text-white">
        <div class="mb-3 flex items-center gap-2 text-xs font-medium text-brand-yellow">
            <x-icon name="shield" class="size-4" />
            مركبة محمية عبر Qreach SmartTag
        </div>
        @php
            $labels = ['nickname' => 'الاسم', 'make' => 'الشركة', 'model' => 'الطراز', 'color' => 'اللون', 'vehicle_type' => 'النوع'];
            $types = ['sedan' => 'سيدان', 'suv' => 'دفع رباعي', 'pickup' => 'بيك أب', 'truck' => 'شاحنة', 'bus' => 'حافلة', 'motorcycle' => 'دراجة نارية', 'van' => 'فان'];
            $shown = collect($publicAttributes)->filter(fn ($v) => ! empty($v));
            $hasDirect = $ownerName || $emergency || count($ownerContacts) > 0;
        @endphp
        @if ($shown->isNotEmpty())
            <p class="text-lg font-bold">
                {{ $shown->get('nickname') ?? trim(($shown->get('make') ?? '').' '.($shown->get('model') ?? '')) ?: 'مركبة' }}
            </p>
            <dl class="mt-3 flex flex-wrap gap-2 text-xs">
                @foreach ($shown->except('nickname') as $key => $value)
                    <div class="rounded-full bg-white/10 px-3 py-1">
                        <dt class="sr-only">{{ $labels[$key] ?? $key }}</dt>
                        <dd>{{ $key === 'vehicle_type' ? ($types[$value] ?? 'أخرى') : $value }}</dd>
                    </div>
                @endforeach
            </dl>
        @else
            <p class="text-sm text-white/70">اختار مالك هذه المركبة عدم عرض أي تفاصيل عنها.</p>
        @endif
        @if ($ownerName)
            <p class="mt-3 text-sm text-white/90">المالك: <span class="font-semibold">{{ $ownerName }}</span></p>
        @endif
        @unless ($hasDirect)
            <p class="mt-4 text-xs text-white/60">لا يعرض المالك أي بيانات تواصل — أرسل له رسالة وسيصله إشعار.</p>
        @endunless
    </section>

    @if ($emergency || count($ownerContacts) > 0)
        <section class="mb-6 rounded-xl border border-ink-200 bg-white p-4" aria-labelledby="direct-contact-title">
            <h2 id="direct-contact-title" class="mb-3 text-sm font-bold text-ink-900">تواصل مباشر</h2>
            <ul class="space-y-2">
                @if ($emergency)
                    <li>
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $emergency['phone']) }}"
                           class="flex items-center justify-between gap-3 rounded-lg border-2 border-brand-black px-4 py-3 text-sm">
                            <span class="font-semibold text-ink-900">جهة اتصال الطوارئ{{ $emergency['relationship'] ? ' — '.$emergency['relationship'] : '' }}</span>
                            <span class="text-ink-700" dir="ltr">{{ $emergency['name'] }} · {{ $emergency['phone'] }}</span>
                        </a>
                    </li>
                @endif
                @foreach ($ownerContacts as $contact)
                    <li>
                        @if ($contact['href'])
                            <a href="{{ $contact['href'] }}" @if (str_starts_with($contact['href'], 'https://')) target="_blank" rel="noopener noreferrer" @endif
                               class="flex items-center justify-between gap-3 rounded-lg border border-ink-200 px-4 py-3 text-sm transition-colors hover:border-brand-black">
                                <span class="font-medium text-ink-900">{{ $contact['label'] }}</span>
                                <span class="text-ink-600" dir="ltr">{{ $contact['value'] }}</span>
                            </a>
                        @else
                            <div class="flex items-center justify-between gap-3 rounded-lg bg-ink-50 px-4 py-3 text-sm">
                                <span class="font-medium text-ink-900">{{ $contact['label'] }}</span>
                                <span class="text-ink-600">{{ $contact['value'] }}</span>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Quick report: tap one predefined reason. There is no free-text "other";
         the visitor can write to the owner in the conversation afterwards. --}}
    <h2 class="mb-1 text-base font-bold text-ink-900">أبلغ مالك المركبة</h2>
    <p class="mb-4 text-sm text-ink-500">اختر السبب وأرسل — يصل إشعار للمالك ويمكنك متابعة رده والكتابة له هنا بعد الإرسال.</p>

    <form method="POST" action="{{ route('public.scan.contact', $tag->public_token) }}" class="group/f space-y-4">
        @csrf
        <fieldset>
            <legend class="sr-only">سبب البلاغ</legend>
            <div class="grid grid-cols-2 gap-2.5">
                @foreach ([
                    'blocking' => ['سيارتك تعيق الطريق', 'car'],
                    'damage' => ['ضرر في المركبة', 'car'],
                    'malfunction' => ['يوجد عطل', 'warning'],
                    'safety' => ['مشكلة سلامة', 'shield'],
                    'assistance' => ['أحتاج مساعدة', 'bell'],
                    'other' => ['سبب آخر', 'pencil'],
                ] as $value => [$label, $icon])
                    <label class="cursor-pointer">
                        <input type="radio" name="reason" value="{{ $value }}" class="peer sr-only" @checked(old('reason') === $value) required>
                        <span class="flex h-full min-h-24 flex-col justify-between gap-3 rounded-xl border border-ink-200 bg-white p-3.5 text-sm font-semibold text-ink-900 transition-colors peer-checked:border-brand-black peer-checked:bg-brand-black peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-yellow">
                            <x-icon :name="$icon" class="size-6" />
                            {{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>

            <div id="other-details-wrap" class="mt-2.5 hidden">
                <label for="other-details" class="sr-only">تفاصيل السبب</label>
                <textarea id="other-details" name="other_details" rows="3" maxlength="500"
                          placeholder="اكتب تفاصيل السبب..." class="form-control">{{ old('other_details') }}</textarea>
                @error('other_details')<p class="mt-1 text-xs text-status-danger">{{ $message }}</p>@enderror
            </div>
        </fieldset>

        <details class="rounded-xl border border-ink-200 bg-white p-3.5">
            <summary class="cursor-pointer list-none text-sm text-ink-600">إضافة اسمك ورقمك للرد (اختياري)</summary>
            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <input type="text" name="visitor_name" maxlength="100" value="{{ old('visitor_name') }}" placeholder="اسمك" aria-label="اسمك" class="form-control">
                <input type="tel" name="visitor_phone" maxlength="20" value="{{ old('visitor_phone') }}" placeholder="رقمك للرد" aria-label="رقمك للرد" dir="ltr" class="form-control text-start">
            </div>
        </details>

        <button type="submit" class="btn-primary w-full !py-3.5 text-base">إرسال البلاغ</button>
        <p class="text-center text-xs text-ink-500">تُحذف المحادثة تلقائيًا بعد 15 يومًا من آخر رسالة.</p>
    </form>
</x-layouts.public>
