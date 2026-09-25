<x-layouts.public title="التواصل بخصوص المركبة">
    {{-- Result of the last action: a prominent panel, not a thin banner a visitor can miss. --}}
    @if (session('scan_status'))
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-status-success/30 bg-status-success/10 p-4" role="status">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-status-success text-white">
                <x-icon name="check" class="size-5" />
            </span>
            <div>
                <p class="text-sm font-semibold text-status-success">{{ session('scan_status') }}</p>
                <p class="mt-0.5 text-xs text-ink-600">وصل إشعارك إلى المالك. شكرًا لك.</p>
            </div>
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

    {{-- Identity: this is the first thing a stranger sees, so it says what SmartTag is
         and shows only the details the owner chose to make public. --}}
    <section class="mb-6 rounded-xl bg-brand-black p-5 text-white">
        <div class="mb-3 flex items-center gap-2 text-xs font-medium text-brand-yellow">
            <x-icon name="shield" class="size-4" />
            مركبة محمية عبر Qreach SmartTag
        </div>
        @php
            $labels = ['nickname' => 'الاسم', 'make' => 'الشركة', 'model' => 'الطراز', 'color' => 'اللون', 'vehicle_type' => 'النوع'];
            $types = ['sedan' => 'سيدان', 'suv' => 'دفع رباعي', 'truck' => 'شاحنة', 'motorcycle' => 'دراجة نارية', 'van' => 'فان'];
            $shown = collect($publicAttributes)->filter(fn ($v) => ! empty($v));
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
        <p class="mt-4 text-xs text-white/60">رقم هاتف المالك لا يُعرض لك أبدًا — رسالتك تصله عبر التطبيق.</p>
    </section>

    <h2 class="mb-3 text-base font-bold text-ink-900">كيف تريد المساعدة؟</h2>

    {{-- Two one-tap actions: no form to fill, just a large, clear button each. --}}
    <div class="mb-3 grid grid-cols-2 gap-3">
        <form method="POST" action="{{ route('public.scan.vehicle-block', $tag->public_token) }}">
            @csrf
            <button type="submit" class="flex h-full min-h-32 w-full flex-col items-start justify-between gap-4 rounded-xl border border-ink-200 bg-white p-4 text-start transition-colors hover:border-brand-black active:bg-ink-50">
                <x-icon name="car" class="size-7 text-ink-900" />
                <span>
                    <span class="block text-sm font-semibold text-ink-900">تعيق الطريق</span>
                    <span class="mt-0.5 block text-xs text-ink-500">اطلب من المالك نقلها</span>
                </span>
            </button>
        </form>
        <form method="POST" action="{{ route('public.scan.lights-on', $tag->public_token) }}">
            @csrf
            <button type="submit" class="flex h-full min-h-32 w-full flex-col items-start justify-between gap-4 rounded-xl border border-ink-200 bg-white p-4 text-start transition-colors hover:border-brand-black active:bg-ink-50">
                <x-icon name="bulb" class="size-7 text-ink-900" />
                <span>
                    <span class="block text-sm font-semibold text-ink-900">الأضواء مضاءة</span>
                    <span class="mt-0.5 block text-xs text-ink-500">نبّه المالك لإطفائها</span>
                </span>
            </button>
        </form>
    </div>

    {{-- Contact owner: pick a reason -> optional message -> send. The phone is optional and
         only used so the owner can reply; it's never shown back to anyone. --}}
    <details class="group mb-3 overflow-hidden rounded-xl border border-ink-200 bg-white" @if ($errors->has('message') || $errors->has('visitor_phone') || old('reason')) open @endif>
        <summary class="flex cursor-pointer list-none items-center gap-3 p-4">
            <x-icon name="contact" class="size-6 shrink-0 text-ink-900" />
            <span class="flex-1">
                <span class="block text-sm font-semibold text-ink-900">مراسلة المالك</span>
                <span class="block text-xs text-ink-500">أرسل رسالة قصيرة — بدون كشف رقمه</span>
            </span>
            <span class="text-ink-400 transition-transform group-open:rotate-180" aria-hidden="true">⌄</span>
        </summary>
        <form method="POST" action="{{ route('public.scan.contact', $tag->public_token) }}" class="space-y-4 border-t border-ink-100 p-4">
            @csrf
            <fieldset>
                <legend class="mb-2 text-xs font-medium text-ink-700">١. ما سبب مراسلتك؟</legend>
                <div class="flex flex-wrap gap-2">
                    @foreach (['damage' => 'لاحظت ضررًا', 'open' => 'باب/نافذة مفتوحة', 'question' => 'لدي استفسار', 'other' => 'سبب آخر'] as $value => $label)
                        <label class="cursor-pointer">
                            <input type="radio" name="reason" value="{{ $value }}" class="peer sr-only" @checked(old('reason') === $value)>
                            <span class="inline-block rounded-full border border-ink-200 px-3.5 py-2 text-sm text-ink-700 peer-checked:border-brand-black peer-checked:bg-brand-black peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-yellow">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <div>
                <label for="contact-message" class="mb-1.5 block text-xs font-medium text-ink-700">٢. رسالة (اختياري إن اخترت سببًا)</label>
                <textarea id="contact-message" name="message" rows="3" maxlength="500" placeholder="اكتب رسالتك لصاحب المركبة..." class="form-control">{{ old('message') }}</textarea>
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <input type="text" name="visitor_name" maxlength="100" value="{{ old('visitor_name') }}" placeholder="اسمك (اختياري)" aria-label="اسمك (اختياري)" class="form-control">
                <input type="tel" name="visitor_phone" maxlength="20" value="{{ old('visitor_phone') }}" placeholder="رقمك للرد (اختياري)" aria-label="رقمك للرد (اختياري)" dir="ltr" class="form-control text-start">
            </div>
            <button type="submit" class="btn-primary w-full !py-3">٣. إرسال إلى المالك</button>
        </form>
    </details>

    <details class="group mb-3 overflow-hidden rounded-xl border border-ink-200 bg-white">
        <summary class="flex cursor-pointer list-none items-center gap-3 p-4">
            <x-icon name="bell" class="size-6 shrink-0 text-ink-900" />
            <span class="flex-1">
                <span class="block text-sm font-semibold text-ink-900">رسالة مهمة</span>
                <span class="block text-xs text-ink-500">ملاحظة نصية سريعة للمالك</span>
            </span>
            <span class="text-ink-400 transition-transform group-open:rotate-180" aria-hidden="true">⌄</span>
        </summary>
        <form method="POST" action="{{ route('public.scan.message', $tag->public_token) }}" class="space-y-3 border-t border-ink-100 p-4">
            @csrf
            <label for="important-message" class="sr-only">رسالتك</label>
            <textarea id="important-message" name="message" rows="3" maxlength="300" required placeholder="اكتب رسالتك هنا..." class="form-control">{{ old('message') }}</textarea>
            <button type="submit" class="btn-primary w-full !py-3">إرسال الرسالة</button>
        </form>
    </details>

    {{-- Emergency: clearly distinct (dark outline + warning icon) but not a red wall —
         the confirmation step and one plain sentence do the work, not alarm colors. --}}
    <details class="group overflow-hidden rounded-xl border-2 border-brand-black bg-white">
        <summary class="flex cursor-pointer list-none items-center gap-3 p-4">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-yellow">
                <x-icon name="warning" class="size-5 text-brand-black" />
            </span>
            <span class="flex-1">
                <span class="block text-sm font-semibold text-ink-900">حالة طارئة</span>
                <span class="block text-xs text-ink-500">حريق، حادث، أو خطر فعلي فقط</span>
            </span>
            <span class="text-ink-400 transition-transform group-open:rotate-180" aria-hidden="true">⌄</span>
        </summary>
        <form method="POST" action="{{ route('public.scan.emergency', $tag->public_token) }}" class="space-y-3 border-t border-ink-100 p-4">
            @csrf
            <p class="text-xs text-ink-600">سيصل تنبيه فوري إلى المالك، ويُسجَّل الحدث. لا تستخدم هذا الخيار للمراسلة العادية.</p>
            <label for="emergency-message" class="sr-only">وصف مختصر للحالة</label>
            <textarea id="emergency-message" name="message" rows="2" maxlength="300" placeholder="وصف مختصر للحالة (اختياري)" class="form-control">{{ old('message') }}</textarea>
            <label class="flex items-start gap-2 text-sm text-ink-700">
                <input type="checkbox" name="confirmed" value="1" required class="mt-0.5 rounded border-ink-300 text-brand-black focus:ring-brand-yellow">
                <span>أؤكد أن هذه حالة طارئة فعلية بخصوص هذه المركبة</span>
            </label>
            <button type="submit" class="w-full rounded-md bg-brand-black py-3 text-sm font-medium text-white transition-colors hover:bg-ink-800">إرسال تنبيه الطوارئ</button>
        </form>
    </details>
</x-layouts.public>
