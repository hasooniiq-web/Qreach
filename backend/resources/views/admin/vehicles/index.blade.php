<x-layouts.admin title="المركبات">
    <x-filter-bar>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="ابحث برقم اللوحة أو هاتف المالك..." aria-label="بحث"
               class="form-control min-w-[220px] flex-1">
        <select name="status" data-auto-submit aria-label="الحالة" class="form-control w-auto">
            <option value="">كل الحالات</option>
            <option value="active" @selected(request('status') === 'active')>نشط</option>
            <option value="inactive" @selected(request('status') === 'inactive')>موقوف (أوقفه المالك مؤقتًا)</option>
        </select>
        <button type="submit" class="btn-primary">بحث</button>
    </x-filter-bar>

    <x-data-table :headers="['المركبة', 'رقم اللوحة', 'المالك', 'الرموز', 'الحالة', '']">
        @forelse ($vehicles as $vehicle)
            <tr class="hover:bg-ink-50">
                <td class="px-4 py-3 font-medium text-ink-900">{{ $vehicle->nickname ?? '—' }} <span class="font-normal text-ink-400">{{ $vehicle->make }} {{ $vehicle->model }}</span></td>
                <td class="px-4 py-3 tabular-nums text-ink-600">{{ $vehicle->plate_number }}</td>
                <td class="px-4 py-3 tabular-nums text-ink-600" dir="ltr">{{ $vehicle->user->phone }}</td>
                <td class="px-4 py-3 text-ink-600">{{ $vehicle->tags_count }}</td>
                <td class="px-4 py-3"><x-status-badge :status="$vehicle->status" /></td>
                <td class="px-4 py-3 text-end">
                    <a href="{{ route('admin.vehicles.show', $vehicle) }}" class="text-sm font-medium text-ink-900 hover:underline">عرض</a>
                </td>
            </tr>
        @empty
            <x-table-empty :cols="6" icon="car" title="لا توجد مركبات مطابقة"
                           description="تظهر المركبات هنا عندما يضيفها العملاء من التطبيق." />
        @endforelse
    </x-data-table>

    <div class="mt-4">{{ $vehicles->links() }}</div>
</x-layouts.admin>
