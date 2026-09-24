<x-layouts.admin title="تفاصيل المستخدم">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-base font-semibold text-ink-900">{{ $user->name ?? 'بدون اسم' }}</h2>
            <p class="text-sm text-ink-500 tabular-nums" dir="ltr">{{ $user->phone }}</p>
        </div>
        <div class="flex items-center gap-2">
            <x-status-badge :status="$user->status" />
            @if ($user->status === 'active')
                <form method="POST" action="{{ route('admin.users.suspend', $user) }}" onsubmit="return confirm('هل أنت متأكد من إيقاف هذا الحساب؟')">
                    @csrf
                    <button class="rounded-md border border-status-danger text-status-danger text-sm font-medium px-3 py-1.5 hover:bg-status-danger/5">إيقاف الحساب</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.users.activate', $user) }}">
                    @csrf
                    <button class="rounded-md border border-status-success text-status-success text-sm font-medium px-3 py-1.5 hover:bg-status-success/5">تفعيل الحساب</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-lg border border-ink-200 bg-white p-5">
            <h3 class="text-sm font-semibold text-ink-900 mb-3">المركبات ({{ $user->vehicles->count() }})</h3>
            @forelse ($user->vehicles as $vehicle)
                <div class="flex items-center justify-between py-2 border-b border-ink-100 last:border-0">
                    <div>
                        <p class="text-sm font-medium text-ink-900">{{ $vehicle->nickname ?? $vehicle->plate_number }}</p>
                        <p class="text-xs text-ink-400">{{ $vehicle->make }} {{ $vehicle->model }} — {{ $vehicle->tags->count() }} رمز</p>
                    </div>
                    <a href="{{ route('admin.vehicles.show', $vehicle) }}" class="text-xs text-ink-600 hover:underline">عرض</a>
                </div>
            @empty
                <p class="text-sm text-ink-400">لا توجد مركبات مسجلة.</p>
            @endforelse
        </div>

        <div class="rounded-lg border border-ink-200 bg-white p-5">
            <h3 class="text-sm font-semibold text-ink-900 mb-3">الأجهزة ({{ $user->devices->count() }})</h3>
            @forelse ($user->devices as $device)
                <div class="flex items-center justify-between py-2 border-b border-ink-100 last:border-0 text-sm">
                    <span class="text-ink-900">{{ $device->device_name ?? $device->platform }}</span>
                    <span class="text-xs {{ $device->is_active ? 'text-status-success' : 'text-ink-400' }}">
                        {{ $device->is_active ? 'نشط' : 'غير نشط' }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-ink-400">لا توجد أجهزة مسجلة.</p>
            @endforelse
        </div>
    </div>
</x-layouts.admin>
