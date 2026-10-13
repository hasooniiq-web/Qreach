{{--
    The bar above a table whose rows can be ticked: how many are ticked and the two steps
    of the printing path. It must sit inside <form data-bulk>; the checkboxes are
    <input type="checkbox" name="ids[]" data-bulk-item>, the header one is "__select".
--}}
@props(['noun' => 'عناصر'])

<div class="mb-3 flex flex-wrap items-center gap-2 rounded-xl border border-ink-200 bg-surface px-3 py-2" role="group" aria-label="إجراءات على المحدد">
    <span class="text-sm text-ink-500">
        المحدد: <strong class="tabular-nums text-ink-900" data-bulk-count>0</strong> {{ $noun }}
    </span>
    <span class="mx-1 hidden h-5 w-px bg-ink-200 sm:block"></span>
    <button type="submit" name="action" value="send_to_printing" class="btn-secondary !py-1.5" data-bulk-submit disabled>
        إرسال للطباعة
    </button>
    <button type="submit" name="action" value="mark_unassigned" class="btn-accent !py-1.5" data-bulk-submit disabled>
        تحديد كمطبوعة وجاهزة (غير مُعيّن)
    </button>
    <span class="text-xs text-ink-400">يُطبَّق على المحدد فقط، وما لا تسمح حالته بالخطوة يُترك كما هو.</span>
</div>
