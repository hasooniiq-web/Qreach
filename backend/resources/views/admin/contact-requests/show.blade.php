<x-layouts.admin title="تفاصيل طلب التواصل">
    <div class="max-w-xl">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-semibold text-ink-900">طلب من {{ $contactRequest->visitor_name ?? 'زائر' }}</h2>
            <x-status-badge :status="$contactRequest->status" />
        </div>

        <div class="rounded-lg border border-ink-200 bg-white p-5 space-y-4">
            <div>
                <p class="text-xs text-ink-400 mb-1">المركبة</p>
                <a href="{{ route('admin.vehicles.show', $contactRequest->vehicle) }}" class="text-sm text-ink-900 font-medium hover:underline">
                    {{ $contactRequest->vehicle->plate_number }}
                </a>
            </div>

            @if ($contactRequest->visitor_phone)
                <div>
                    <p class="text-xs text-ink-400 mb-1">رقم هاتف الزائر</p>
                    <p class="text-sm text-ink-900 tabular-nums" dir="ltr">{{ $contactRequest->visitor_phone }}</p>
                </div>
            @endif

            <div>
                <p class="text-xs text-ink-400 mb-1">الرسالة</p>
                <p class="text-sm text-ink-900 whitespace-pre-line">{{ $contactRequest->message }}</p>
            </div>

            <div>
                <p class="text-xs text-ink-400 mb-1">تاريخ الإرسال</p>
                <p class="text-sm text-ink-600">{{ $contactRequest->created_at->format('Y-m-d H:i') }}</p>
            </div>
        </div>

        @if ($contactRequest->status !== 'closed')
            <form method="POST" action="{{ route('admin.contact-requests.close', $contactRequest) }}" class="mt-4">
                @csrf
                <button class="rounded-md border border-ink-200 text-ink-700 text-sm font-medium px-4 py-2 hover:bg-ink-50">إغلاق الطلب</button>
            </form>
        @endif
    </div>
</x-layouts.admin>
