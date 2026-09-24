<x-layouts.admin title="الإعدادات">
    <div class="max-w-xl rounded-lg border border-ink-200 bg-white p-6">
        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            @foreach ($settings as $setting)
                <div>
                    <label class="block text-sm font-medium text-ink-700 mb-1.5">{{ $setting['label'] }}</label>
                    <input type="number" name="{{ $setting['key'] }}" min="{{ $setting['min'] }}" max="{{ $setting['max'] }}"
                           value="{{ old($setting['key'], $setting['value']) }}" required
                           class="block w-full rounded-md border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-yellow focus:ring-2 focus:ring-brand-yellow/40 outline-none">
                    @error($setting['key'])
                        <p class="mt-1 text-xs text-status-danger">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <button type="submit" class="rounded-md bg-brand-black text-white text-sm font-medium px-5 py-2.5 hover:bg-ink-800">
                حفظ الإعدادات
            </button>
        </form>
    </div>
</x-layouts.admin>
