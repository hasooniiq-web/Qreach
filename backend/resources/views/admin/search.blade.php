<x-layouts.admin title="البحث">
    <x-page-header title="نتائج البحث" :subtitle="mb_strlen($q) >= 2 ? '«'.$q.'» — '.$total.' نتيجة' : 'اكتب حرفين على الأقل للبحث.'" />

    <form method="GET" action="{{ route('admin.search') }}" class="mb-6 max-w-xl">
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-ink-400" />
            <input type="search" name="q" value="{{ $q }}" autofocus placeholder="ابحث بالاسم أو الهاتف أو اللوحة أو الرقم التسلسلي..." class="form-control ps-9">
        </div>
    </form>

    @if (mb_strlen($q) >= 2 && $total === 0)
        <x-card>
            <div class="mx-auto flex max-w-sm flex-col items-center py-8 text-center">
                <span class="mb-4 flex size-12 items-center justify-center rounded-full bg-ink-100 text-ink-500"><x-icon name="search" class="size-6" /></span>
                <p class="text-sm font-semibold text-ink-900">لا توجد نتائج</p>
                <p class="mt-1 text-sm text-ink-500">جرّب كلمة أخرى أو جزءًا من الاسم أو الرقم.</p>
            </div>
        </x-card>
    @endif

    <div class="space-y-6">
        @if ($results['users']->isNotEmpty())
            <section>
                <h3 class="mb-2 text-sm font-semibold text-ink-900">المستخدمون</h3>
                <x-card padding="p-1">
                    @foreach ($results['users'] as $u)
                        <a href="{{ route('admin.users.show', $u) }}" class="flex items-center justify-between gap-3 rounded-lg px-4 py-3 transition-colors hover:bg-ink-50">
                            <span class="flex items-center gap-3">
                                <span class="flex size-8 items-center justify-center rounded-full bg-ink-100 text-ink-600"><x-icon name="users" class="size-4" /></span>
                                <span class="text-sm font-medium text-ink-900">{{ $u->name ?? 'بدون اسم' }}</span>
                            </span>
                            <span class="text-sm tabular-nums text-ink-500" dir="ltr">{{ $u->phone }}</span>
                        </a>
                    @endforeach
                </x-card>
            </section>
        @endif

        @if ($results['vehicles']->isNotEmpty())
            <section>
                <h3 class="mb-2 text-sm font-semibold text-ink-900">المركبات</h3>
                <x-card padding="p-1">
                    @foreach ($results['vehicles'] as $v)
                        <a href="{{ route('admin.vehicles.show', $v) }}" class="flex items-center justify-between gap-3 rounded-lg px-4 py-3 transition-colors hover:bg-ink-50">
                            <span class="flex items-center gap-3">
                                <span class="flex size-8 items-center justify-center rounded-full bg-ink-100 text-ink-600"><x-icon name="car" class="size-4" /></span>
                                <span class="text-sm font-medium text-ink-900">{{ $v->nickname ?? trim($v->make.' '.$v->model) ?: 'مركبة' }}</span>
                            </span>
                            <span class="text-sm tabular-nums text-ink-500">{{ $v->plate_number }}</span>
                        </a>
                    @endforeach
                </x-card>
            </section>
        @endif

        @if ($results['tags']->isNotEmpty())
            <section>
                <h3 class="mb-2 text-sm font-semibold text-ink-900">الرموز</h3>
                <x-card padding="p-1">
                    @foreach ($results['tags'] as $t)
                        <a href="{{ route('admin.tags.show', $t) }}" class="flex items-center justify-between gap-3 rounded-lg px-4 py-3 transition-colors hover:bg-ink-50">
                            <span class="flex items-center gap-3">
                                <span class="flex size-8 items-center justify-center rounded-full bg-ink-100 text-ink-600"><x-icon :name="$t->type === 'nfc' ? 'nfc' : 'qrcode'" class="size-4" /></span>
                                <span class="text-sm font-medium tabular-nums text-ink-900">{{ $t->serial_number }}</span>
                            </span>
                            <x-status-badge :status="$t->status" />
                        </a>
                    @endforeach
                </x-card>
            </section>
        @endif
    </div>
</x-layouts.admin>
