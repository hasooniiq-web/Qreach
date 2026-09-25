@php
    $canManage = auth('admin')->user()->hasPermission('subscriptions.manage');
    $typeLabels = [
        'phone' => 'عرض رقم الهاتف', 'instagram' => 'عرض حساب إنستغرام', 'facebook' => 'عرض حساب فيسبوك',
        'website' => 'عرض رابط موقع', 'email' => 'عرض البريد الإلكتروني', 'other' => 'عرض معلومات إضافية',
    ];
@endphp

<x-layouts.admin title="الباقات والصلاحيات">
    <x-subscription-tabs current="plans" :pending="\App\Models\PlanUpgradeRequest::where('status', 'pending')->count()" />

    <p class="mb-5 text-sm text-ink-500">ما يستطيع كل عميل فعله يتحدد بباقته. تُطبَّق هذه الصلاحيات من الخادم مباشرة: التطبيق والصفحة العامة لا يقرّران شيئًا بأنفسهما.</p>

    {{-- Read-only comparison: the "who can do what" answer at a glance. --}}
    <x-card padding="p-0" class="mb-8 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ink-50 text-xs text-ink-500">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-start font-medium">الميزة</th>
                        @foreach ($plans as $plan)
                            <th scope="col" class="px-4 py-3 text-center font-semibold text-ink-900">{{ $plan->name_ar }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    <tr>
                        <th scope="row" class="px-4 py-3 text-start font-medium text-ink-700">السعر</th>
                        @foreach ($plans as $plan)
                            <td class="px-4 py-3 text-center font-semibold text-ink-900"><x-plan-price :plan="$plan" /></td>
                        @endforeach
                    </tr>
                    <tr>
                        <th scope="row" class="px-4 py-3 text-start font-medium text-ink-700">المشتركون الفعّالون</th>
                        @foreach ($plans as $plan)
                            <td class="px-4 py-3 text-center tabular-nums text-ink-600">{{ $plan->isFree() ? 'الافتراضية للجميع' : ($subscriberCounts[$plan->id] ?? 0) }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <th scope="row" class="px-4 py-3 text-start font-medium text-ink-700">مراسلة المالك</th>
                        @foreach ($plans as $plan)
                            <td class="px-4 py-3 text-center"><x-icon name="check" class="mx-auto size-5 text-status-success" /><span class="sr-only">متاح</span></td>
                        @endforeach
                    </tr>
                    @foreach ($typeLabels as $type => $label)
                        <tr>
                            <th scope="row" class="px-4 py-3 text-start font-medium text-ink-700">{{ $label }}</th>
                            @foreach ($plans as $plan)
                                <td class="px-4 py-3 text-center">
                                    @if ($plan->allowsPublicContact($type))
                                        <x-icon name="check" class="mx-auto size-5 text-status-success" /><span class="sr-only">متاح</span>
                                    @else
                                        <span class="text-ink-300" aria-hidden="true">—</span><span class="sr-only">غير متاح</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>

    @if ($canManage)
        <h2 class="mb-3 text-sm font-semibold text-ink-900">تعديل الباقات</h2>
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @foreach ($plans as $plan)
                <x-card padding="p-6">
                    <form method="POST" action="{{ route('admin.plans.update', $plan) }}" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <h3 class="text-base font-semibold text-ink-900">{{ $plan->name_ar }} <span class="text-xs font-normal text-ink-400" dir="ltr">({{ $plan->slug }})</span></h3>

                        <x-field label="اسم الباقة" :name="'name_ar_'.$plan->id">
                            <input type="text" id="name_ar_{{ $plan->id }}" name="name_ar" value="{{ old('name_ar', $plan->name_ar) }}" required class="form-control">
                        </x-field>
                        <x-field label="وصف مختصر" :name="'description_ar_'.$plan->id">
                            <input type="text" id="description_ar_{{ $plan->id }}" name="description_ar" value="{{ old('description_ar', $plan->description_ar) }}" maxlength="255" class="form-control">
                        </x-field>
                        <div class="grid grid-cols-2 gap-3">
                            <x-field label="السعر (د.ع)" :name="'price_cents_'.$plan->id" :hint="$plan->isFree() ? 'الباقة الأساسية مجانية دائمًا.' : null">
                                <input type="number" id="price_cents_{{ $plan->id }}" name="price_cents" min="0" value="{{ old('price_cents', $plan->price_cents) }}" required @disabled($plan->isFree()) class="form-control disabled:bg-ink-50 disabled:text-ink-400">
                                @if ($plan->isFree())<input type="hidden" name="price_cents" value="0">@endif
                            </x-field>
                            <x-field label="مدة الاشتراك (أيام)" :name="'billing_period_days_'.$plan->id">
                                <input type="number" id="billing_period_days_{{ $plan->id }}" name="billing_period_days" min="1" value="{{ old('billing_period_days', $plan->billing_period_days) }}" required class="form-control">
                            </x-field>
                        </div>

                        <fieldset>
                            <legend class="mb-2 text-xs font-medium text-ink-700">ما تتيحه هذه الباقة للمالك (فوق المراسلة)</legend>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($typeLabels as $type => $label)
                                    <label class="flex items-center gap-2 text-sm text-ink-700">
                                        <input type="checkbox" name="public_contacts[]" value="{{ $type }}" @checked($plan->allowsPublicContact($type))
                                               class="rounded border-ink-300 text-brand-yellow focus:ring-brand-yellow">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        @unless ($plan->isFree())
                            <label class="flex items-center gap-2 text-sm text-ink-700">
                                <input type="checkbox" name="is_active" value="1" @checked($plan->is_active) class="rounded border-ink-300 text-brand-yellow focus:ring-brand-yellow">
                                الباقة متاحة للاشتراك
                            </label>
                        @endunless

                        <button type="submit" class="btn-primary">حفظ {{ $plan->name_ar }}</button>
                    </form>
                </x-card>
            @endforeach
        </div>
    @endif
</x-layouts.admin>
