<x-layouts.public title="التواصل بخصوص المركبة">
    @if (session('scan_status'))
        <div class="mb-5 rounded-md bg-status-success/10 border border-status-success/30 px-4 py-3 text-sm text-status-success" role="status">
            {{ session('scan_status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-md bg-status-danger/10 border border-status-danger/30 px-4 py-3 text-sm text-status-danger" role="alert">
            <ul class="list-disc pr-4 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Vehicle info (owner-controlled visibility, section 9/13) -->
    <div class="rounded-lg border border-ink-200 bg-white p-5 mb-6">
        <p class="text-xs font-medium text-ink-400 mb-2">هذه المركبة مسجلة على منصة Qreach</p>
        @if (count($publicAttributes) > 0)
            <dl class="grid grid-cols-2 gap-y-2 text-sm">
                @foreach ($publicAttributes as $key => $value)
                    @continue(empty($value))
                    <dt class="text-ink-400">
                        @switch($key)
                            @case('nickname') الاسم @break
                            @case('make') الشركة المصنعة @break
                            @case('model') الطراز @break
                            @case('color') اللون @break
                            @case('vehicle_type') النوع @break
                            @default {{ $key }}
                        @endswitch
                    </dt>
                    <dd class="text-ink-900 font-medium">
                        @if ($key === 'vehicle_type')
                            @switch($value)
                                @case('sedan') سيدان @break
                                @case('suv') دفع رباعي @break
                                @case('truck') شاحنة @break
                                @case('motorcycle') دراجة نارية @break
                                @case('van') فان @break
                                @default أخرى
                            @endswitch
                        @else
                            {{ $value }}
                        @endif
                    </dd>
                @endforeach
            </dl>
        @else
            <p class="text-sm text-ink-500">لم يشارك المالك أي تفاصيل عن المركبة.</p>
        @endif
    </div>

    <h2 class="text-sm font-semibold text-ink-900 mb-3">اختر الإجراء المناسب</h2>

    <div class="space-y-3">
        <!-- Vehicle block -->
        <form method="POST" action="{{ route('public.scan.vehicle-block', $tag->public_token) }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3 rounded-lg border border-ink-200 bg-white p-4 text-start hover:border-brand-yellow transition-colors">
                <x-icon name="car" class="size-5 text-ink-500 shrink-0" />
                <span class="flex-1">
                    <span class="block text-sm font-medium text-ink-900">مركبتك تعيق المرور</span>
                    <span class="block text-xs text-ink-400">إخطار المالك لنقل المركبة</span>
                </span>
            </button>
        </form>

        <!-- Lights on -->
        <form method="POST" action="{{ route('public.scan.lights-on', $tag->public_token) }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3 rounded-lg border border-ink-200 bg-white p-4 text-start hover:border-brand-yellow transition-colors">
                <x-icon name="bell" class="size-5 text-ink-500 shrink-0" />
                <span class="flex-1">
                    <span class="block text-sm font-medium text-ink-900">الأضواء مضاءة</span>
                    <span class="block text-xs text-ink-400">إخطار المالك بأن الأضواء لم تُطفأ</span>
                </span>
            </button>
        </form>

        <!-- Important message (progressive disclosure, no JS needed) -->
        <details class="rounded-lg border border-ink-200 bg-white overflow-hidden group">
            <summary class="flex items-center gap-3 p-4 cursor-pointer list-none">
                <x-icon name="contact" class="size-5 text-ink-500 shrink-0" />
                <span class="flex-1">
                    <span class="block text-sm font-medium text-ink-900">إرسال رسالة مهمة</span>
                    <span class="block text-xs text-ink-400">أرسل ملاحظة نصية لصاحب المركبة</span>
                </span>
            </summary>
            <form method="POST" action="{{ route('public.scan.message', $tag->public_token) }}" class="p-4 pt-0 space-y-3">
                @csrf
                <textarea name="message" rows="3" maxlength="300" required placeholder="اكتب رسالتك هنا..."
                          class="block w-full rounded-md border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-yellow focus:ring-2 focus:ring-brand-yellow/40 outline-none">{{ old('message') }}</textarea>
                <button type="submit" class="w-full rounded-md bg-brand-black text-white text-sm font-medium py-2.5 hover:bg-ink-800 transition-colors">إرسال الرسالة</button>
            </form>
        </details>

        <!-- Contact owner -->
        <details class="rounded-lg border border-ink-200 bg-white overflow-hidden">
            <summary class="flex items-center gap-3 p-4 cursor-pointer list-none">
                <x-icon name="users" class="size-5 text-ink-500 shrink-0" />
                <span class="flex-1">
                    <span class="block text-sm font-medium text-ink-900">التواصل مع المالك</span>
                    <span class="block text-xs text-ink-400">لن يتم مشاركة رقم هاتف المالك معك</span>
                </span>
            </summary>
            <form method="POST" action="{{ route('public.scan.contact', $tag->public_token) }}" class="p-4 pt-0 space-y-3">
                @csrf
                <input type="text" name="visitor_name" maxlength="100" placeholder="اسمك (اختياري)"
                       class="block w-full rounded-md border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-yellow focus:ring-2 focus:ring-brand-yellow/40 outline-none">
                <input type="tel" name="visitor_phone" maxlength="20" placeholder="رقم هاتفك (اختياري، للرد عليك)"
                       class="block w-full rounded-md border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-yellow focus:ring-2 focus:ring-brand-yellow/40 outline-none">
                <textarea name="message" rows="3" maxlength="500" required placeholder="اكتب رسالتك لصاحب المركبة..."
                          class="block w-full rounded-md border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-yellow focus:ring-2 focus:ring-brand-yellow/40 outline-none">{{ old('message') }}</textarea>
                <button type="submit" class="w-full rounded-md bg-brand-black text-white text-sm font-medium py-2.5 hover:bg-ink-800 transition-colors">إرسال طلب التواصل</button>
            </form>
        </details>

        <!-- Emergency (destructive-emphasis + explicit confirmation, section 13) -->
        <details class="rounded-lg border border-status-danger/30 bg-status-danger/5 overflow-hidden">
            <summary class="flex items-center gap-3 p-4 cursor-pointer list-none">
                <x-icon name="shield" class="size-5 text-status-danger shrink-0" />
                <span class="flex-1">
                    <span class="block text-sm font-medium text-status-danger">حالة طارئة</span>
                    <span class="block text-xs text-ink-500">للحالات العاجلة فقط — سيتم إشعار المالك فورًا</span>
                </span>
            </summary>
            <form method="POST" action="{{ route('public.scan.emergency', $tag->public_token) }}" class="p-4 pt-0 space-y-3">
                @csrf
                <textarea name="message" rows="2" maxlength="300" placeholder="وصف مختصر للحالة (اختياري)"
                          class="block w-full rounded-md border border-ink-200 px-3 py-2.5 text-sm focus:border-status-danger focus:ring-2 focus:ring-status-danger/30 outline-none">{{ old('message') }}</textarea>
                <label class="flex items-start gap-2 text-sm text-ink-700">
                    <input type="checkbox" name="confirmed" value="1" required
                           class="mt-0.5 rounded border-ink-300 text-status-danger focus:ring-status-danger">
                    <span>أؤكد أن هذه حالة طارئة فعلية بخصوص هذه المركبة</span>
                </label>
                <button type="submit" class="w-full rounded-md bg-status-danger text-white text-sm font-medium py-2.5 hover:opacity-90 transition-opacity">إرسال تنبيه طوارئ</button>
            </form>
        </details>
    </div>
</x-layouts.public>
