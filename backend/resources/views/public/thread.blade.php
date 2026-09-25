<x-layouts.public title="محادثتك مع مالك المركبة">
    @if (session('scan_status'))
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-status-success/30 bg-status-success/10 p-4" role="status">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-status-success text-white">
                <x-icon name="check" class="size-5" />
            </span>
            <p class="text-sm font-semibold text-status-success">{{ session('scan_status') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-status-danger/30 bg-status-danger/10 p-4 text-sm text-status-danger" role="alert">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <section class="mb-5 rounded-xl bg-brand-black p-5 text-white">
        <p class="text-sm font-bold">محادثتك مع مالك المركبة</p>
        <p class="mt-1 text-xs text-white/70">احتفظ بهذه الصفحة (أضفها للمفضلة) لترى ردّ المالك. تُحذف المحادثة تلقائيًا في {{ $thread->expiresAt()->format('Y-m-d') }}.</p>
    </section>

    <div class="mb-5 space-y-3" aria-live="polite">
        {{-- The first message lives on the request itself; later ones in the messages table. --}}
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
                    <p class="whitespace-pre-line">{{ $m->body }}</p>
                    <p class="mt-1 text-[11px] opacity-60">{{ $m->created_at->format('Y-m-d H:i') }}</p>
                </div>
            </div>
        @endforeach
        @if ($thread->messages->where('sender', 'owner')->isEmpty())
            <p class="rounded-xl bg-ink-50 p-3 text-center text-xs text-ink-600">لم يردّ المالك بعد. أعد فتح هذه الصفحة لاحقًا.</p>
        @endif
    </div>

    <form method="POST" action="{{ route('public.scan.thread.reply', [$tag->public_token, $thread->uuid]) }}" class="space-y-3">
        @csrf
        <label for="reply-body" class="sr-only">رسالتك</label>
        <textarea id="reply-body" name="body" rows="3" maxlength="500" required placeholder="اكتب رسالة جديدة..." class="form-control">{{ old('body') }}</textarea>
        <button type="submit" class="btn-primary w-full !py-3">إرسال</button>
    </form>

    <a href="{{ route('public.scan.thread', [$tag->public_token, $thread->uuid]) }}" class="mt-4 block text-center text-sm text-ink-600 underline">تحديث الصفحة</a>
</x-layouts.public>
