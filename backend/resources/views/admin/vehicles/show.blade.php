<x-layouts.admin title="تفاصيل المركبة">
    <x-page-header :title="$vehicle->nickname ?? $vehicle->plate_number"
                   :subtitle="trim($vehicle->make.' '.$vehicle->model).' — '.$vehicle->plate_number"
                   :back="route('admin.vehicles.index')" backLabel="كل المركبات">
        <x-slot:actions><x-status-badge :status="$vehicle->status" /></x-slot:actions>
    </x-page-header>

    <x-card class="mb-4">
        <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
            <div><dt class="text-xs text-ink-400">النوع</dt><dd class="mt-1 text-ink-900">{{ $vehicle->vehicle_type }}</dd></div>
            <div><dt class="text-xs text-ink-400">سنة الصنع</dt><dd class="mt-1 text-ink-900">{{ $vehicle->year ?? '—' }}</dd></div>
            <div><dt class="text-xs text-ink-400">اللون</dt><dd class="mt-1 text-ink-900">{{ $vehicle->color ?? '—' }}</dd></div>
            <div><dt class="text-xs text-ink-400">رقم الهيكل (VIN)</dt><dd class="mt-1 font-mono text-ink-900" dir="ltr">{{ $vehicle->vin ?? '—' }}</dd></div>
        </dl>
    </x-card>

    <div class="mb-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-card>
            <h3 class="mb-3 text-sm font-semibold text-ink-900">المالك</h3>
            <p class="text-sm text-ink-900">{{ $vehicle->user->name ?? 'بدون اسم' }}</p>
            <p class="text-sm tabular-nums text-ink-500" dir="ltr">{{ $vehicle->user->phone }}</p>
            <a href="{{ route('admin.users.show', $vehicle->user) }}" class="mt-2 inline-block text-xs font-medium text-ink-600 hover:underline">عرض ملف المستخدم</a>
        </x-card>

        <x-card>
            <h3 class="mb-3 text-sm font-semibold text-ink-900">الرموز المرتبطة ({{ $vehicle->tags->count() }})</h3>
            @forelse ($vehicle->tags as $tag)
                <div class="flex items-center justify-between border-b border-ink-100 py-2 text-sm last:border-0">
                    <a href="{{ route('admin.tags.show', $tag) }}" class="tabular-nums text-ink-900 hover:underline">{{ $tag->serial_number }} <span class="text-xs uppercase text-ink-400">{{ $tag->type }}</span></a>
                    <x-status-badge :status="$tag->status" />
                </div>
            @empty
                <p class="text-sm text-ink-400">لم يُربط أي رمز بهذه المركبة بعد.</p>
            @endforelse
        </x-card>
    </div>

    <x-card>
        <h3 class="mb-3 text-sm font-semibold text-ink-900">أحدث طلبات التواصل</h3>
        @forelse ($vehicle->contactRequests as $request)
            <div class="border-b border-ink-100 py-2 last:border-0">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-ink-900">{{ $request->visitor_name ?? 'زائر' }}</span>
                    <x-status-badge :status="$request->status" />
                </div>
                <p class="mt-0.5 text-xs text-ink-500">{{ $request->message }}</p>
            </div>
        @empty
            <p class="text-sm text-ink-400">لم يراسل أحد مالك هذه المركبة بعد.</p>
        @endforelse
    </x-card>
</x-layouts.admin>
