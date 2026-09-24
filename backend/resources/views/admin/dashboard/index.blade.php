<x-layouts.admin title="لوحة التحكم">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @php
            $cards = [
                ['label' => 'إجمالي المستخدمين', 'value' => $stats['users_total'], 'icon' => 'users'],
                ['label' => 'المستخدمون النشطون', 'value' => $stats['users_active'], 'icon' => 'users'],
                ['label' => 'إجمالي المركبات', 'value' => $stats['vehicles_total'], 'icon' => 'car'],
                ['label' => 'المركبات النشطة', 'value' => $stats['vehicles_active'], 'icon' => 'car'],
                ['label' => 'إجمالي رموز QR/NFC', 'value' => $stats['tags_total'], 'icon' => 'qrcode'],
                ['label' => 'الرموز المتاحة', 'value' => $stats['tags_available'], 'icon' => 'qrcode'],
                ['label' => 'الرموز المربوطة', 'value' => $stats['tags_assigned'], 'icon' => 'qrcode'],
                ['label' => 'الرموز الملغاة', 'value' => $stats['tags_revoked'], 'icon' => 'qrcode'],
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="rounded-lg border border-ink-200 bg-white p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm text-ink-500">{{ $card['label'] }}</span>
                    <x-icon :name="$card['icon']" class="size-4 text-ink-300" />
                </div>
                <p class="text-2xl font-semibold text-ink-900 tabular-nums">{{ number_format($card['value']) }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-lg border border-ink-200 bg-white p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-ink-900">طلبات التواصل الجديدة</h2>
                <span class="inline-flex items-center rounded-full bg-brand-yellow/20 text-ink-900 text-xs font-medium px-2.5 py-1">
                    {{ $stats['contact_requests_new'] }}
                </span>
            </div>
            @if ($stats['contact_requests_new'] === 0)
                <p class="text-sm text-ink-400">لا توجد طلبات تواصل جديدة حاليًا.</p>
            @else
                <p class="text-sm text-ink-600">يوجد {{ $stats['contact_requests_new'] }} طلب تواصل بانتظار المراجعة.</p>
            @endif
        </div>

        <div class="rounded-lg border border-ink-200 bg-white p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-ink-900">أحداث أمنية غير محلولة</h2>
                <span class="inline-flex items-center rounded-full {{ $stats['security_events_unresolved'] > 0 ? 'bg-status-danger/10 text-status-danger' : 'bg-status-success/10 text-status-success' }} text-xs font-medium px-2.5 py-1">
                    {{ $stats['security_events_unresolved'] }}
                </span>
            </div>
            @if ($stats['security_events_unresolved'] === 0)
                <p class="text-sm text-ink-400">لا توجد أحداث أمنية غير محلولة.</p>
            @else
                <p class="text-sm text-ink-600">يتطلب {{ $stats['security_events_unresolved'] }} حدثًا أمنيًا المراجعة.</p>
            @endif
        </div>
    </div>
</x-layouts.admin>
