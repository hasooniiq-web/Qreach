<x-layouts.public title="بلاغك ومحادثتك مع المالك">
    @push('head')
        @vite(['resources/js/public-thread.js'])
    @endpush

    @if (session('scan_status'))
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-status-success/30 bg-status-success/10 p-4" role="status">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-status-success text-white">
                <x-icon name="check" class="size-5" />
            </span>
            <p class="text-sm font-semibold text-status-success">{{ session('scan_status') }}</p>
        </div>
    @endif


    @php
        $labels = ['new' => 'أُرسل — بانتظار قراءة المالك', 'read' => 'قرأ المالك بلاغك', 'responded' => 'ردّ المالك', 'closed' => 'أُغلق البلاغ'];
        $reasons = ['damage' => 'ضرر في المركبة', 'malfunction' => 'عطل', 'safety' => 'مشكلة سلامة', 'assistance' => 'طلب مساعدة'];
    @endphp

    <div id="thread-live" data-url="{{ route('public.scan.thread.messages', [$tag->public_token, $thread->uuid]) }}">
        <section class="mb-5 rounded-xl bg-brand-black p-5 text-white">
            <p class="text-xs text-white/60">{{ $reasons[$thread->reason] ?? 'بلاغك' }}</p>
            <p id="thread-status" class="mt-1 text-base font-bold">{{ $labels[$thread->status] ?? '' }}</p>
            <p class="mt-2 text-xs text-white/60">يتحدّث هذا الردّ تلقائيًا. تُحذف المحادثة في {{ $thread->expiresAt()->format('Y-m-d') }}.</p>
        </section>

        <div id="thread-list" class="mb-5 space-y-3" aria-live="polite"
             data-signature="{{ ($thread->messages->count() + 1).':'.$thread->status }}">
            <div class="flex justify-end">
                <div class="max-w-[85%] rounded-2xl rounded-ee-sm bg-brand-yellow px-4 py-2.5 text-sm text-brand-black">
                    <p class="whitespace-pre-line">{{ $thread->message }}</p>
                    <p class="mt-1 text-[11px] opacity-60">{{ $thread->created_at->format('Y-m-d H:i') }}</p>
                </div>
            </div>
            @foreach ($thread->messages as $m)
                <div class="flex {{ $m->sender === 'visitor' ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[85%] rounded-2xl px-4 py-2.5 text-sm {{ $m->sender === 'visitor' ? 'rounded-ee-sm bg-brand-yellow text-brand-black' : 'rounded-es-sm bg-ink-100 text-ink-900' }}">
                        @if ($m->sender === 'owner')<p class="mb-0.5 text-[11px] font-semibold text-ink-500">المالك</p>@endif
                        @if ($m->body !== '')
                            <p class="whitespace-pre-line">{{ $m->body }}</p>
                        @endif
                        @if ($m->image_path)
                            <a href="{{ route('public.scan.thread.image', [$tag->public_token, $thread->uuid, $m]) }}" target="_blank" rel="noopener noreferrer">
                                <img src="{{ route('public.scan.thread.image', [$tag->public_token, $thread->uuid, $m]) }}" alt="صورة مرفقة" class="mt-1.5 max-h-56 rounded-lg object-contain">
                            </a>
                        @endif
                        <p class="mt-1 text-[11px] opacity-60">{{ $m->created_at->format('Y-m-d H:i') }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Nothing is typed on this page. To write back to the owner, the visitor scans the
         code with the Qreach app, which opens the same conversation there. --}}
    <section class="rounded-xl border border-ink-200 bg-white p-4 text-sm text-ink-700">
        <p class="mb-1 font-semibold text-ink-900">للمراسلة مع صاحب المركبة</p>
        <p class="leading-relaxed">حمّل تطبيق Qreach وامسح رمز المركبة من داخله، فتكمل المحادثة من التطبيق وتصلك ردود المالك فيه.</p>
    </section>
</x-layouts.public>
