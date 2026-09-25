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
            <div><dt class="text-xs text-ink-400">تاريخ التسجيل</dt><dd class="mt-1 text-ink-900">{{ $user->created_at->format('Y-m-d') }}</dd></div>
            <div><dt class="text-xs text-ink-400">عدد المركبات</dt><dd class="mt-1 text-ink-900">{{ $user->vehicles->count() }}</dd></div>
            <div><dt class="text-xs text-ink-400">عدد الأجهزة</dt><dd class="mt-1 text-ink-900">{{ $user->devices->count() }}</dd></div>
        </dl>
    </x-card>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-card>
            <h3 class="mb-3 text-sm font-semibold text-ink-900">المركبات ({{ $user->vehicles->count() }})</h3>
            @forelse ($user->vehicles as $vehicle)
                <div class="flex items-center justify-between border-b border-ink-100 py-2 last:border-0">
                    <div>
                        <p class="text-sm font-medium text-ink-900">{{ $vehicle->nickname ?? $vehicle->plate_number }}</p>
                        <p class="text-xs text-ink-400">{{ $vehicle->make }} {{ $vehicle->model }} — {{ $vehicle->tags->count() }} رمز</p>
                    </div>
                    <a href="{{ route('admin.vehicles.show', $vehicle) }}" class="text-xs font-medium text-ink-600 hover:underline">عرض</a>
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
</x-layouts.admin>
