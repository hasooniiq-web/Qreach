@php
    $input = 'block w-full min-h-12 rounded-xl border border-ink-300 bg-white px-4 py-3 text-base text-ink-900 placeholder:text-ink-400 focus:border-brand-black focus:outline-2 focus:outline-offset-1 focus:outline-brand-yellow';
@endphp

<x-layouts.site :title="trans('site.portal.title').' — Qreach'" :description="trans('site.portal.meta')" robots="noindex,nofollow">
    <div class="bg-ink-50 py-10 sm:py-14">
        <div class="mx-auto max-w-2xl px-5">
            <h1 class="text-2xl font-extrabold sm:text-3xl">{{ trans('site.portal.title') }}</h1>

            @if (! isset($result))
                <p class="mt-3 leading-8 text-ink-600">{{ trans('site.portal.intro') }}</p>

                <form method="POST" action="{{ url('/reseller') }}?lang={{ app()->getLocale() }}" class="mt-8 space-y-5 rounded-2xl bg-white p-6 ring-1 ring-ink-200" novalidate>
                    @csrf
                    @if ($error ?? null)
                        <p role="alert" class="rounded-xl bg-status-danger/10 px-4 py-3 text-sm font-medium text-status-danger">{{ $error }}</p>
                    @endif
                    <div>
                        <label for="batch" class="mb-1.5 block text-sm font-semibold text-ink-800">{{ trans('site.portal.batch') }}</label>
                        <input id="batch" name="batch" value="{{ old('batch') }}" dir="ltr" autocomplete="off" autocapitalize="characters" spellcheck="false"
                               maxlength="40" required placeholder="{{ trans('site.portal.batch_hint') }}" class="{{ $input }} text-start">
                    </div>
                    <div>
                        <label for="code" class="mb-1.5 block text-sm font-semibold text-ink-800">{{ trans('site.portal.code') }}</label>
                        <input id="code" name="code" dir="ltr" autocomplete="off" autocapitalize="characters" spellcheck="false"
                               maxlength="16" required placeholder="{{ trans('site.portal.code_hint') }}" class="{{ $input }} text-start tracking-widest">
                    </div>
                    <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-brand-black px-6 py-3 text-base font-bold text-white transition-colors hover:bg-ink-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-yellow">
                        {{ trans('site.portal.submit') }}
                    </button>
                </form>
            @else
                @php
                    $total = max(1, $result['quantity']);
                    $percent = (int) round($result['redeemed'] / $total * 100);
                    $tiles = [
                        ['label' => trans('site.portal.total'), 'value' => $result['quantity'], 'tone' => 'text-ink-900'],
                        ['label' => trans('site.portal.activated'), 'value' => $result['redeemed'], 'tone' => 'text-status-success'],
                        ['label' => trans('site.portal.remaining'), 'value' => $result['remaining'], 'tone' => 'text-ink-900'],
                    ];
                @endphp
                <p class="mt-2 text-lg font-semibold text-ink-700" dir="ltr" style="text-align: start">{{ trans('site.portal.result_title', ['batch' => $result['batch_code']]) }}</p>

                <div class="mt-6 grid grid-cols-3 gap-3">
                    @foreach ($tiles as $tile)
                        <div class="rounded-2xl bg-white p-4 text-center ring-1 ring-ink-200">
                            <p class="text-2xl font-extrabold tabular-nums sm:text-3xl {{ $tile['tone'] }}">{{ number_format($tile['value']) }}</p>
                            <p class="mt-1 text-xs font-medium text-ink-500">{{ $tile['label'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 rounded-2xl bg-white p-5 ring-1 ring-ink-200">
                    <div class="mb-2 flex items-center justify-between text-sm">
                        <span class="font-semibold">{{ trans('site.portal.progress', ['percent' => $percent]) }}</span>
                    </div>
                    <div class="h-3 overflow-hidden rounded-full bg-ink-100" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}">
                        <div class="h-full rounded-full bg-brand-yellow" style="width: {{ $percent }}%"></div>
                    </div>
                    @if ($result['revoked'] > 0 || $result['expired'] > 0)
                        <p class="mt-3 text-xs text-ink-500">
                            @if ($result['revoked'] > 0){{ trans('site.portal.revoked') }}: {{ number_format($result['revoked']) }}@endif
                            @if ($result['revoked'] > 0 && $result['expired'] > 0) · @endif
                            @if ($result['expired'] > 0){{ trans('site.portal.expired') }}: {{ number_format($result['expired']) }}@endif
                        </p>
                    @endif
                </div>

                <dl class="mt-6 grid gap-4 rounded-2xl bg-white p-5 text-sm ring-1 ring-ink-200 sm:grid-cols-2">
                    <div><dt class="text-xs text-ink-400">{{ trans('site.portal.plan') }}</dt><dd class="mt-0.5 font-semibold">{{ $result['plan'] }}</dd></div>
                    <div><dt class="text-xs text-ink-400">{{ trans('site.portal.duration') }}</dt><dd class="mt-0.5 font-semibold">{{ trans('site.portal.days', ['days' => $result['duration_days']]) }}</dd></div>
                    <div><dt class="text-xs text-ink-400">{{ trans('site.portal.created') }}</dt><dd class="mt-0.5 font-semibold tabular-nums" dir="ltr" style="text-align: start">{{ $result['created_at'] }}</dd></div>
                    <div><dt class="text-xs text-ink-400">{{ trans('site.portal.valid_until') }}</dt><dd class="mt-0.5 font-semibold tabular-nums" dir="ltr" style="text-align: start">{{ $result['expires_at'] ?? trans('site.portal.no_expiry') }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-ink-400">{{ trans('site.portal.last') }}</dt><dd class="mt-0.5 font-semibold tabular-nums" dir="ltr" style="text-align: start">{{ $result['last_activation'] ?? trans('site.portal.none_yet') }}</dd></div>
                </dl>

                @if (count($result['daily']) > 0)
                    <section class="mt-6 rounded-2xl bg-white p-5 ring-1 ring-ink-200" aria-labelledby="daily-title">
                        <h2 id="daily-title" class="mb-3 text-sm font-bold">{{ trans('site.portal.daily') }}</h2>
                        <table class="w-full text-sm">
                            <thead><tr class="text-xs text-ink-400"><th class="pb-2 text-start font-medium">{{ trans('site.portal.date') }}</th><th class="pb-2 text-end font-medium">{{ trans('site.portal.count') }}</th></tr></thead>
                            <tbody class="divide-y divide-ink-100">
                                @foreach ($result['daily'] as $date => $count)
                                    <tr><td class="py-2 tabular-nums" dir="ltr" style="text-align: start">{{ $date }}</td><td class="py-2 text-end font-semibold tabular-nums">{{ number_format($count) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </section>
                @endif

                <p class="mt-6 text-xs leading-6 text-ink-500">{{ trans('site.portal.privacy') }}</p>
                <a href="{{ url('/reseller') }}?lang={{ app()->getLocale() }}" class="mt-4 inline-flex min-h-11 items-center text-sm font-semibold text-ink-900 underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-brand-yellow">{{ trans('site.portal.again') }}</a>
            @endif
        </div>
    </div>
</x-layouts.site>
