<x-layouts.admin title="التنبيهات الطارئة">
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-kpi label="آخر 24 ساعة" :value="$stats['last_24h']" icon="warning" :tone="$stats['last_24h'] > 0 ? 'danger' : 'success'" size="lg" />
        <x-kpi label="آخر 7 أيام" :value="$stats['last_7d']" icon="warning" />
        <x-kpi label="الإجمالي" :value="$stats['total']" icon="audit" />
    </div>

    <x-filter-bar>
        <label for="from" class="text-sm text-ink-500">من</label>
        <input id="from" type="date" name="from" value="{{ request('from') }}" class="form-control w-auto !py-2">
        <label for="to" class="text-sm text-ink-500">إلى</label>
        <input id="to" type="date" name="to" value="{{ request('to') }}" class="form-control w-auto !py-2">
        <button type="submit" class="btn-primary">تصفية</button>
    </x-filter-bar>

    <div class="mb-3 flex justify-end"><x-export-buttons /></div>

    <x-data-table :headers="['الوقت', 'المركبة', 'المالك', 'وصف الزائر', 'الرمز']">
        @forelse ($alerts as $alert)
            <tr class="hover:bg-ink-50">
                <td class="whitespace-nowrap px-4 py-3 text-ink-500">{{ $alert->created_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i') }}</td>
                <td class="px-4 py-3 font-medium text-ink-900">
                    @if ($alert->vehicle)
                        <a href="{{ route('admin.vehicles.show', $alert->vehicle) }}" class="hover:underline">{{ $alert->vehicle->nickname ?? $alert->vehicle->plate_number }}</a>
                    @else — @endif
                </td>
                <td class="px-4 py-3 text-ink-600">
                    @if ($alert->vehicle?->user)
                        <a href="{{ route('admin.users.show', $alert->vehicle->user) }}" class="hover:underline" dir="ltr">{{ $alert->vehicle->user->phone }}</a>
                    @else — @endif
                </td>
                <td class="max-w-[300px] px-4 py-3 text-ink-700">{{ $alert->payload['message'] ?? 'بدون وصف' }}</td>
                <td class="px-4 py-3 tabular-nums text-ink-500" dir="ltr">{{ $alert->tag?->serial_number ?? '—' }}</td>
            </tr>
        @empty
            <x-table-empty :cols="5" icon="warning" title="لا توجد تنبيهات طارئة"
                           description="عندما يؤكد زائر حالة طارئة من صفحة الرمز يظهر التنبيه هنا فورًا." />
        @endforelse
    </x-data-table>

    <div class="mt-4">{{ $alerts->links() }}</div>
</x-layouts.admin>
