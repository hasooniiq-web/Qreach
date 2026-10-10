<x-layouts.admin title="تفاصيل المستخدم">
    <x-page-header :title="$user->name ?? 'بدون اسم'" :back="route('admin.users.index')" backLabel="كل المستخدمين">
        <x-slot:actions>
            <x-status-badge :status="$user->status" />
            @if ($user->status === 'active')
                <form method="POST" action="{{ route('admin.users.suspend', $user) }}"
                      data-confirm-title="إيقاف هذا الحساب؟"
                      data-confirm="لن يتمكن المستخدم من تسجيل الدخول أو استخدام التطبيق حتى تعيد تفعيل حسابه. مركباته ورموزه لا تُحذف."
                      data-confirm-label="إيقاف الحساب">
                    @csrf
                    <button class="btn-danger">إيقاف الحساب</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.users.activate', $user) }}">
                    @csrf
                    <button class="btn-secondary">تفعيل الحساب</button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-card class="mb-4">
        <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
            <div><dt class="text-xs text-ink-400">رقم الهاتف</dt><dd class="mt-1 tabular-nums text-ink-900" dir="ltr">{{ $user->phone }}</dd></div>
            <div><dt class="text-xs text-ink-400">تاريخ التسجيل</dt><dd class="mt-1 text-ink-900">{{ $user->created_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d') }}</dd></div>
            <div><dt class="text-xs text-ink-400">آخر دخول</dt><dd class="mt-1 text-ink-900">{{ $user->last_login_at?->diffForHumans() ?? '—' }}</dd></div>
            <div><dt class="text-xs text-ink-400">عدد المركبات</dt><dd class="mt-1 text-ink-900">{{ $user->vehicles->count() }}</dd></div>
            <div><dt class="text-xs text-ink-400">عدد الأجهزة</dt><dd class="mt-1 text-ink-900">{{ $user->devices->count() }}</dd></div>
        </dl>
    </x-card>

    @php $canManageSubs = auth('admin')->user()->hasPermission('subscriptions.manage'); @endphp
    <x-card class="mb-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h3 class="mb-1 text-sm font-semibold text-ink-900">الاشتراك</h3>
                <p class="flex flex-wrap items-center gap-2 text-sm text-ink-900">
                    <span class="font-semibold">{{ $plan->name_ar }}</span>
                    <x-plan-price :plan="$plan" class="text-ink-600" />
                    @if ($activeSubscription)
                        <x-status-badge :status="$activeSubscription->effectiveStatus()" />
                    @else
                        <span class="text-xs text-ink-400">الباقة الافتراضية</span>
                    @endif
                </p>
                <p class="mt-1 text-xs text-ink-500">
                    @if ($activeSubscription?->current_period_ends_at)
                        ينتهي في {{ $activeSubscription->current_period_ends_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d') }}
                    @elseif ($plan->publicContactTypes() === [])
                        المراسلة فقط — لا تُعرض بيانات تواصل مباشرة للزوار.
                    @endif
                </p>
                @if ($plan->publicContactTypes() !== [])
                    <p class="mt-1 text-xs text-ink-500">يمكنه عرض رقم هاتف وإنستغرام ومعلومات إضافية للزوار.</p>
                @endif
            </div>

            @if ($canManageSubs)
                <div class="flex flex-wrap items-end gap-3">
                    <form method="POST" action="{{ route('admin.users.subscription.grant', $user) }}" class="flex items-end gap-2">
                        @csrf
                        <div>
                            <label for="grant-plan" class="mb-1 block text-xs text-ink-500">الباقة</label>
                            <select id="grant-plan" name="plan" class="form-control !py-2">
                                @foreach ($paidPlans as $paidPlan)
                                    <option value="{{ $paidPlan->slug }}">{{ $paidPlan->name_ar }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="grant-days" class="mb-1 block text-xs text-ink-500">المدة (أيام)</label>
                            <input id="grant-days" type="number" name="days" min="1" max="3650" placeholder="افتراضي" class="form-control w-24 !py-2">
                        </div>
                        <button class="btn-accent">{{ $activeSubscription ? 'تمديد / منح' : 'منح الباقة' }}</button>
                    </form>
                    @if ($activeSubscription)
                        <form method="POST" action="{{ route('admin.subscriptions.cancel', $activeSubscription) }}"
                              data-confirm-title="إلغاء الاشتراك؟"
                              data-confirm="سيعود المستخدم إلى الباقة الأساسية فورًا وتُخفى بيانات التواصل المعروضة للزوار."
                              data-confirm-label="إلغاء الاشتراك">
                            @csrf
                            <button class="btn-danger">إلغاء الاشتراك</button>
                        </form>
                    @endif
                </div>
                @error('plan')<p class="w-full text-xs text-status-danger" role="alert">{{ $message }}</p>@enderror
            @endif
        </div>
    </x-card>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-card>
            <h3 class="mb-3 text-sm font-semibold text-ink-900">المركبات ({{ $user->vehicles->count() }})</h3>
            @forelse ($user->vehicles as $vehicle)
                <div class="border-b border-ink-100 py-3 last:border-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-ink-900">{{ $vehicle->nickname ?? $vehicle->plate_number }}</p>
                            <p class="text-xs text-ink-400">
                                {{ $vehicle->make }} {{ $vehicle->model }} {{ $vehicle->year }} · {{ $vehicle->color }}
                                @if ($vehicle->vin)· VIN: <span dir="ltr">{{ $vehicle->vin }}</span>@endif
                            </p>
                        </div>
                        <a href="{{ route('admin.vehicles.show', $vehicle) }}" class="text-xs font-medium text-ink-600 hover:underline">عرض</a>
                    </div>
                    @if ($vehicle->tags->isNotEmpty())
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($vehicle->tags as $tag)
                                <a href="{{ route('admin.tags.show', $tag) }}" class="inline-flex items-center gap-1.5 rounded-full border border-ink-200 px-2 py-0.5 text-xs text-ink-600 hover:border-ink-400">
                                    <span>{{ $tag->typeLabel() }}</span>
                                    <span class="font-mono tabular-nums">{{ $tag->serial_number }}</span>
                                    <x-status-badge :status="$tag->status" />
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-1 text-xs text-ink-400">لا يوجد رمز مربوط بهذه المركبة.</p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-ink-400">لم يضف هذا المستخدم أي مركبة بعد.</p>
            @endforelse
        </x-card>

        <x-card>
            <h3 class="mb-3 text-sm font-semibold text-ink-900">الأجهزة ({{ $user->devices->count() }})</h3>
            @forelse ($user->devices as $device)
                <div class="flex items-center justify-between border-b border-ink-100 py-2 text-sm last:border-0">
                    <span class="text-ink-900">{{ $device->device_name ?? $device->platform }}</span>
                    <x-status-badge :status="$device->is_active ? 'active' : 'inactive'" />
                </div>
            @empty
                <p class="text-sm text-ink-400">لا توجد أجهزة مسجلة بعد.</p>
            @endforelse
        </x-card>
    </div>

    <x-card class="mt-4" padding="p-0">
        <h3 class="border-b border-ink-100 px-5 py-4 text-sm font-semibold text-ink-900">السجل الزمني للحساب</h3>
        <div class="max-h-96 overflow-y-auto">
            @forelse ($timeline as $item)
                <div class="flex items-start gap-3 border-b border-ink-100 px-5 py-3 last:border-0">
                    <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-ink-300"></span>
                    <p class="flex-1 text-sm text-ink-800">{{ $item['text'] }}</p>
                    <time class="shrink-0 text-xs text-ink-400">{{ $item['at']->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i') }}</time>
                </div>
            @empty
                <p class="px-5 py-8 text-center text-sm text-ink-400">لا يوجد نشاط مسجَّل بعد.</p>
            @endforelse
        </div>
    </x-card>
</x-layouts.admin>
