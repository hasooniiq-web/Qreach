<x-layouts.admin title="تفاصيل المركبة">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-base font-semibold text-ink-900">{{ $vehicle->nickname ?? $vehicle->plate_number }}</h2>
            <p class="text-sm text-ink-500">{{ $vehicle->make }} {{ $vehicle->model }} — {{ $vehicle->plate_number }}</p>
        </div>
        <x-status-badge :status="$vehicle->status" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        <div class="rounded-lg border border-ink-200 bg-white p-5">
            <h3 class="text-sm font-semibold text-ink-900 mb-3">المالك</h3>
            <p class="text-sm text-ink-900">{{ $vehicle->user->name ?? 'بدون اسم' }}</p>
            <p class="text-sm text-ink-500 tabular-nums" dir="ltr">{{ $vehicle->user->phone }}</p>
            <a href="{{ route('admin.users.show', $vehicle->user) }}" class="inline-block mt-2 text-xs text-ink-600 hover:underline">عرض ملف المستخدم</a>
        </div>

        <div class="rounded-lg border border-ink-200 bg-white p-5">
            <h3 class="text-sm font-semibold text-ink-900 mb-3">الرموز المرتبطة ({{ $vehicle->tags->count() }})</h3>
            @forelse ($vehicle->tags as $tag)
                <div class="flex items-center justify-between py-2 border-b border-ink-100 last:border-0 text-sm">
                    <span class="text-ink-900 tabular-nums">{{ $tag->serial_number }}</span>
                    <x-status-badge :status="$tag->status" />
                </div>
            @empty
                <p class="text-sm text-ink-400">لا توجد رموز مرتبطة بهذه المركبة بعد.</p>
            @endforelse
        </div>
    </div>

    <div class="rounded-lg border border-ink-200 bg-white p-5">
        <h3 class="text-sm font-semibold text-ink-900 mb-3">أحدث طلبات التواصل</h3>
        @forelse ($vehicle->contactRequests as $request)
            <div class="py-2 border-b border-ink-100 last:border-0">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-ink-900">{{ $request->visitor_name ?? 'زائر' }}</span>
                    <x-status-badge :status="$request->status" />
                </div>
                <p class="text-xs text-ink-500 mt-0.5">{{ $request->message }}</p>
            </div>
        @empty
            <p class="text-sm text-ink-400">لا توجد طلبات تواصل بعد.</p>
        @endforelse
    </div>
</x-layouts.admin>
