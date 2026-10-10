<x-layouts.admin title="تفاصيل طلب التواصل">
    <div class="max-w-xl">
        <x-page-header :title="'طلب من '.($contactRequest->visitor_name ?? 'زائر')"
                       :back="route('admin.contact-requests.index')" backLabel="كل الطلبات">
            <x-slot:actions><x-status-badge :status="$contactRequest->status" /></x-slot:actions>
        </x-page-header>

        <x-card class="space-y-4">
            <div>
                <p class="mb-1 text-xs text-ink-400">المركبة</p>
                @if ($contactRequest->vehicle && ! $contactRequest->vehicle->trashed())
                    <a href="{{ route('admin.vehicles.show', $contactRequest->vehicle) }}" class="text-sm font-medium text-ink-900 hover:underline">{{ $contactRequest->vehicle->plate_number }}</a>
                @else
                    <span class="text-sm text-ink-600">{{ $contactRequest->vehicle?->plate_number ?? '—' }} <span class="text-xs text-ink-400">(مركبة محذوفة)</span></span>
                @endif
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
                <p class="text-sm text-ink-600">{{ $contactRequest->created_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i') }}</p>
            </div>
        </x-card>

        @if ($contactRequest->messages->isNotEmpty())
            <x-card class="mt-4">
                <h3 class="mb-3 text-sm font-semibold text-ink-900">المحادثة</h3>
                <ul class="space-y-2 text-sm">
                    @foreach ($contactRequest->messages as $m)
                        <li class="rounded-lg px-3 py-2 {{ $m->sender === 'owner' ? 'bg-ink-100' : 'bg-brand-yellow/20' }}">
                            <span class="text-xs font-semibold text-ink-500">{{ $m->sender === 'owner' ? 'المالك' : 'الزائر' }} — {{ $m->created_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i') }}</span>
                            @if ($m->body !== '')
                                <p class="whitespace-pre-line text-ink-900">{{ $m->body }}</p>
                            @endif
                            @if ($m->image_path)
                                <a href="{{ route('admin.contact-requests.message-image', [$contactRequest, $m]) }}" target="_blank" rel="noopener noreferrer">
                                    <img src="{{ route('admin.contact-requests.message-image', [$contactRequest, $m]) }}" alt="صورة مرفقة" class="mt-2 max-h-64 rounded-lg border border-ink-200 object-contain">
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif

        <p class="mt-3 text-xs text-ink-500">تُحذف هذه المحادثة تلقائيًا في {{ $contactRequest->expiresAt()->copy()->timezone(config('app.display_timezone'))->format('Y-m-d') }}.</p>

        @if ($contactRequest->status !== 'closed')
            <form method="POST" action="{{ route('admin.contact-requests.close', $contactRequest) }}" class="mt-4">
                @csrf
                <button class="btn-secondary">إغلاق الطلب</button>
            </form>
        @endif
    </div>
</x-layouts.admin>
