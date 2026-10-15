@php $canRespond = auth('admin')->user()->hasPermission('contact_requests.respond'); @endphp

<x-layouts.admin :title="'تذكرة: '.$ticket->subject">
    <div class="max-w-2xl">
        <x-page-header :title="$ticket->subject" :subtitle="($ticket->user?->display_name ?? 'حساب محذوف').' — '.($categoryLabels[$ticket->category] ?? '').' — '.($statusLabels[$ticket->status] ?? '')"
                       :back="route('admin.support.index')" backLabel="كل التذاكر" />

        @if (session('status'))
            <p class="mb-4 rounded-lg bg-status-success/10 px-4 py-3 text-sm font-medium text-status-success" role="status">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-status-danger/10 px-4 py-3 text-sm text-status-danger" role="alert">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <x-card class="mb-5 space-y-3">
            @foreach ($ticket->messages as $m)
                <div class="flex {{ $m->sender === 'staff' ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[85%] rounded-2xl px-4 py-2.5 text-sm {{ $m->sender === 'staff' ? 'bg-brand-yellow text-brand-black' : 'bg-ink-100 text-ink-900' }}">
                        <p class="mb-0.5 text-[11px] font-semibold opacity-70">{{ $m->sender === 'staff' ? 'الدعم' : ($ticket->user?->display_name ?? 'العميل') }}</p>
                        @if ($m->body !== '')<p class="whitespace-pre-line">{{ $m->body }}</p>@endif
                        @if ($m->image_path)
                            <a href="{{ route('admin.support.image', [$ticket, $m]) }}" target="_blank" rel="noopener noreferrer">
                                <img src="{{ route('admin.support.image', [$ticket, $m]) }}" alt="صورة مرفقة" class="mt-1.5 max-h-60 rounded-lg object-contain">
                            </a>
                        @endif
                        <p class="mt-1 text-[11px] opacity-60">{{ \App\Support\Export\Labels::date($m->created_at, true) }}</p>
                    </div>
                </div>
            @endforeach
        </x-card>

        @if ($canRespond)
            @if ($ticket->status === 'closed')
                <form method="POST" action="{{ route('admin.support.reopen', $ticket) }}">
                    @csrf
                    <button class="btn-secondary">إعادة فتح التذكرة</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.support.reply', $ticket) }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <textarea name="body" rows="4" maxlength="2000" placeholder="اكتب الرد للعميل..." class="form-control">{{ old('body') }}</textarea>
                    <div class="flex flex-wrap items-center gap-3">
                        <input type="file" name="image" accept="image/*" class="text-sm text-ink-600">
                        <button type="submit" class="btn-primary ms-auto">إرسال الرد</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('admin.support.close', $ticket) }}" class="mt-4">
                    @csrf
                    <button class="text-sm text-ink-500 hover:underline">إغلاق التذكرة</button>
                </form>
            @endif
        @endif
    </div>
</x-layouts.admin>
