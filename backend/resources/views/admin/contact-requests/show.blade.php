<x-layouts.admin title="تفاصيل طلب التواصل">
    <div class="max-w-xl">
        <x-page-header :title="'طلب من '.($contactRequest->visitor_name ?? 'زائر')"
                       :back="route('admin.contact-requests.index')" backLabel="كل الطلبات">
            <x-slot:actions><x-status-badge :status="$contactRequest->status" /></x-slot:actions>
        </x-page-header>

        <x-card class="space-y-4">
            <div>
                <p class="mb-1 text-xs text-ink-400">المركبة</p>
                <a href="{{ route('admin.vehicles.show', $contactRequest->vehicle) }}" class="text-sm font-medium text-ink-900 hover:underline">
                    {{ $contactRequest->vehicle->plate_number }}
                </a>
            </div>

            @if ($contactRequest->visitor_phone)
                <div>
                    <p class="mb-1 text-xs text-ink-400">رقم هاتف الزائر</p>
                    <p class="text-sm tabular-nums text-ink-900" dir="ltr">{{ $contactRequest->visitor_phone }}</p>
                </div>
            @endif

            <div>
                <p class="mb-1 text-xs text-ink-400">الرسالة</p>
                <p class="whitespace-pre-line text-sm text-ink-900">{{ $contactRequest->message }}</p>
            </div>

            <div>
                <p class="mb-1 text-xs text-ink-400">تاريخ الإرسال</p>
                <p class="text-sm text-ink-600">{{ $contactRequest->created_at->format('Y-m-d H:i') }}</p>
            </div>
        </x-card>

        @if ($contactRequest->status !== 'closed')
            <form method="POST" action="{{ route('admin.contact-requests.close', $contactRequest) }}" class="mt-4">
                @csrf
                <button class="btn-secondary">إغلاق الطلب</button>
            </form>
        @endif
    </div>
</x-layouts.admin>
