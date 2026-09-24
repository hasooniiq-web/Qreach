<x-layouts.admin title="إنشاء دفعة رموز جديدة">
    <div class="max-w-lg rounded-lg border border-ink-200 bg-white p-6">
        <form method="POST" action="{{ route('admin.tag-batches.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1.5">نوع الرمز</label>
                <select name="tag_type" required class="block w-full rounded-md border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-yellow focus:ring-2 focus:ring-brand-yellow/40 outline-none">
                    <option value="qr">QR</option>
                    <option value="nfc">NFC</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1.5">الكمية</label>
                <input type="number" name="quantity" min="1" max="1000" value="{{ old('quantity', 100) }}" required
                       class="block w-full rounded-md border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-yellow focus:ring-2 focus:ring-brand-yellow/40 outline-none">
                <p class="mt-1 text-xs text-ink-400">الحد الأقصى 1000 رمز لكل دفعة.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-700 mb-1.5">ملاحظات (اختياري)</label>
                <textarea name="notes" rows="2" maxlength="500" class="block w-full rounded-md border border-ink-200 px-3 py-2.5 text-sm focus:border-brand-yellow focus:ring-2 focus:ring-brand-yellow/40 outline-none">{{ old('notes') }}</textarea>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="rounded-md bg-brand-black text-white text-sm font-medium px-5 py-2.5 hover:bg-ink-800">إنشاء الدفعة</button>
                <a href="{{ route('admin.tag-batches.index') }}" class="text-sm text-ink-500 hover:underline">إلغاء</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
