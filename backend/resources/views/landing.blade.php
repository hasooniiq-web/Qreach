@php
    $locale = app()->getLocale();
    $icons = [
        'lock' => 'M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z',
        'bell' => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0',
        'sliders' => 'M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75',
        'shield' => 'M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285zM12 15.75h.007v.008H12v-.008z',
        'phone' => 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
        'trash' => 'M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0',
    ];
    $featureIcons = ['lock', 'bell', 'sliders', 'shield', 'phone', 'trash'];
    $features = trans('site.features.items');
    $steps = trans('site.how.steps');
    $faq = trans('site.faq.items');
    $reasons = trans('site.mock.reasons');
    $fmt = fn ($n) => number_format((int) $n);
    $ios = config('landing.ios_url');
    $android = config('landing.android_url');
    $whatsapp = config('landing.whatsapp');
    $email = config('landing.email');
    $phones = (array) config('landing.phones', []);
    $field = 'block w-full min-h-12 rounded-xl border border-ink-300 bg-white px-4 py-3 text-base text-ink-900 placeholder:text-ink-400 focus:border-brand-black focus:outline-2 focus:outline-offset-1 focus:outline-brand-yellow';
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $contactErrors = $errors->getBag('contact');
    $btnPrimary = 'inline-flex min-h-11 items-center justify-center rounded-full bg-brand-yellow px-6 py-2.5 text-sm font-bold text-brand-black transition-colors hover:bg-amber-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-yellow';
@endphp

<x-layouts.site :title="trans('site.meta.title')" :description="trans('site.meta.description')">
    <x-slot:nav>
        <a href="#features" class="transition-colors hover:text-white">{{ trans('site.nav.features') }}</a>
        <a href="#how" class="transition-colors hover:text-white">{{ trans('site.nav.how') }}</a>
        <a href="#plans" class="transition-colors hover:text-white">{{ trans('site.nav.plans') }}</a>
        <a href="#faq" class="transition-colors hover:text-white">{{ trans('site.nav.faq') }}</a>
        <a href="#contact" class="transition-colors hover:text-white">{{ trans('site.nav.contact') }}</a>
    </x-slot:nav>
    <x-slot:cta>
        <a href="#download" class="{{ $btnPrimary }} hidden !min-h-10 !px-4 sm:inline-flex">{{ trans('site.nav.download') }}</a>
    </x-slot:cta>

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-brand-black text-white">
        <div class="pointer-events-none absolute -top-32 end-[-6rem] size-[28rem] rounded-full bg-brand-yellow/20 blur-3xl" aria-hidden="true"></div>
        <div class="relative mx-auto grid max-w-6xl items-center gap-12 px-5 py-16 lg:grid-cols-2 lg:py-24">
            <div>
                <p class="mb-4 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-brand-yellow">
                    <span class="size-1.5 rounded-full bg-brand-yellow"></span>{{ trans('site.hero.eyebrow') }}
                </p>
                <h1 class="text-3xl font-extrabold leading-[1.35] sm:text-4xl lg:text-5xl">{{ trans('site.hero.title') }}</h1>
                <p class="mt-5 max-w-xl text-base leading-8 text-white/75 sm:text-lg">{{ trans('site.hero.sub') }}</p>
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a href="#download" class="{{ $btnPrimary }}">{{ trans('site.hero.cta') }}</a>
                    <a href="#how" class="inline-flex min-h-11 items-center justify-center rounded-full border border-white/25 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-yellow">{{ trans('site.hero.cta_secondary') }}</a>
                </div>
                @if ($trialDays > 0)
                    <p class="mt-5 text-sm font-medium text-brand-yellow">{{ trans('site.hero.trial', ['days' => $trialDays]) }}</p>
                @endif
            </div>

            {{-- A faithful miniature of what a visitor sees after scanning. --}}
            <div class="mx-auto w-full max-w-sm" aria-hidden="true">
                <div class="rounded-[2rem] border border-white/15 bg-white p-3 text-ink-900 shadow-2xl">
                    <div class="rounded-[1.5rem] bg-ink-50 p-5">
                        <div class="mb-4 flex items-center gap-2.5">
                            <span class="flex size-7 items-center justify-center rounded-full bg-brand-yellow text-xs font-extrabold text-brand-black">Q</span>
                            <span class="text-sm font-semibold">Qreach <span class="font-normal text-ink-400">SmartTag</span></span>
                        </div>
                        <p class="text-base font-bold">{{ trans('site.mock.title') }}</p>
                        <p class="mb-4 mt-1 text-xs leading-6 text-ink-500">{{ trans('site.mock.sub') }}</p>
                        <ul class="space-y-2">
                            @foreach ($reasons as $i => $reason)
                                <li class="flex items-center gap-3 rounded-xl border px-3.5 py-3 text-sm {{ $i === 0 ? 'border-brand-yellow bg-brand-yellow/15 font-semibold' : 'border-ink-200 bg-white text-ink-700' }}">
                                    <span class="flex size-4 shrink-0 items-center justify-center rounded-full border {{ $i === 0 ? 'border-brand-black bg-brand-black' : 'border-ink-300' }}">
                                        @if ($i === 0)<span class="size-1.5 rounded-full bg-brand-yellow"></span>@endif
                                    </span>
                                    {{ $reason }}
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-4 rounded-xl bg-brand-black py-3 text-center text-sm font-bold text-white">{{ trans('site.mock.send') }}</div>
                        <p class="mt-3 text-center text-[11px] text-ink-400">{{ trans('site.mock.note') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="scroll-mt-20 bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-6xl px-5">
            <div class="mx-auto mb-12 max-w-2xl text-center">
                <h2 class="text-2xl font-extrabold sm:text-3xl">{{ trans('site.features.title') }}</h2>
                <p class="mt-3 leading-8 text-ink-500">{{ trans('site.features.sub') }}</p>
            </div>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($features as $i => $feature)
                    <article class="rounded-2xl border border-ink-200 bg-white p-6 transition-shadow hover:shadow-lg">
                        <span class="mb-4 flex size-11 items-center justify-center rounded-xl bg-brand-yellow/20 text-ink-900">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icons[$featureIcons[$i]] }}"/></svg>
                        </span>
                        <h3 class="text-base font-bold">{{ $feature['title'] }}</h3>
                        <p class="mt-2 text-sm leading-7 text-ink-600">{{ $feature['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section id="how" class="scroll-mt-20 bg-ink-50 py-16 sm:py-20">
        <div class="mx-auto max-w-6xl px-5">
            <h2 class="mb-12 text-center text-2xl font-extrabold sm:text-3xl">{{ trans('site.how.title') }}</h2>
            <ol class="grid gap-5 md:grid-cols-3">
                @foreach ($steps as $i => $step)
                    <li class="relative rounded-2xl bg-white p-6 ring-1 ring-ink-200">
                        <span class="mb-4 flex size-10 items-center justify-center rounded-full bg-brand-black text-base font-extrabold text-brand-yellow">{{ $i + 1 }}</span>
                        <h3 class="text-base font-bold">{{ $step['title'] }}</h3>
                        <p class="mt-2 text-sm leading-7 text-ink-600">{{ $step['text'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Plans --}}
    <section id="plans" class="scroll-mt-20 bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-4xl px-5">
            <div class="mx-auto mb-12 max-w-2xl text-center">
                <h2 class="text-2xl font-extrabold sm:text-3xl">{{ trans('site.plans.title') }}</h2>
                <p class="mt-3 leading-8 text-ink-500">{{ trans('site.plans.sub') }}</p>
            </div>
            <div class="grid gap-6 md:grid-cols-2">
                @foreach ([['plan' => $basic, 'points' => 'site.plans.basic_points', 'featured' => false], ['plan' => $plus, 'points' => 'site.plans.plus_points', 'featured' => true]] as $card)
                    @continue(! $card['plan'])
                    @php $plan = $card['plan']; @endphp
                    <article class="relative flex flex-col rounded-2xl p-7 {{ $card['featured'] ? 'bg-brand-black text-white ring-2 ring-brand-yellow' : 'bg-white ring-1 ring-ink-200' }}">
                        @if ($card['featured'])
                            <span class="absolute -top-3 start-6 rounded-full bg-brand-yellow px-3 py-1 text-xs font-bold text-brand-black">{{ trans('site.plans.recommended') }}</span>
                        @endif
                        <h3 class="text-xl font-extrabold">{{ $plan->nameIn($locale) }}</h3>
                        @if ($plan->descriptionIn($locale))
                            <p class="mt-2 text-sm leading-7 {{ $card['featured'] ? 'text-white/70' : 'text-ink-500' }}">{{ $plan->descriptionIn($locale) }}</p>
                        @endif
                        <p class="mt-5 flex flex-wrap items-baseline gap-x-2">
                            @if ($plan->isFree())
                                <span class="text-3xl font-extrabold">{{ trans('site.plans.free') }}</span>
                            @else
                                <span class="text-3xl font-extrabold tabular-nums">{{ $fmt($plan->price_cents) }}</span>
                                <span class="text-sm {{ $card['featured'] ? 'text-white/70' : 'text-ink-500' }}">{{ trans('site.plans.currency') }} · {{ trans('site.plans.per', ['days' => $plan->billing_period_days]) }}</span>
                            @endif
                        </p>
                        @if (! $plan->isFree() && $trialDays > 0)
                            <p class="mt-2 text-sm font-semibold {{ $card['featured'] ? 'text-brand-yellow' : 'text-ink-900' }}">{{ trans('site.plans.trial', ['days' => $trialDays]) }}</p>
                        @endif
                        <ul class="mt-6 space-y-3 text-sm">
                            @foreach (trans($card['points']) as $point)
                                <li class="flex items-start gap-3">
                                    <svg class="mt-0.5 size-5 shrink-0 {{ $card['featured'] ? 'text-brand-yellow' : 'text-status-success' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    <span class="{{ $card['featured'] ? 'text-white/90' : 'text-ink-700' }}">{{ $point }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section id="faq" class="scroll-mt-20 bg-ink-50 py-16 sm:py-20">
        <div class="mx-auto max-w-3xl px-5">
            <h2 class="mb-10 text-center text-2xl font-extrabold sm:text-3xl">{{ trans('site.faq.title') }}</h2>
            <div class="space-y-3">
                @foreach ($faq as $item)
                    <details class="group rounded-xl bg-white ring-1 ring-ink-200 open:ring-ink-300">
                        <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-start text-sm font-bold focus-visible:outline-2 focus-visible:outline-brand-yellow">
                            {{ $item['q'] }}
                            <svg class="size-5 shrink-0 text-ink-400 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </summary>
                        <p class="px-5 pb-5 text-sm leading-7 text-ink-600">{{ $item['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Contact us --}}
    <section id="contact" class="scroll-mt-20 bg-white py-16 sm:py-20">
        <div class="mx-auto grid max-w-6xl gap-10 px-5 lg:grid-cols-2">
            <div>
                <h2 class="text-2xl font-extrabold sm:text-3xl">{{ trans('site.contact.title') }}</h2>
                <p class="mt-3 max-w-md leading-8 text-ink-600">{{ trans('site.contact.sub') }}</p>

                <dl class="mt-8 space-y-5 text-sm">
                    @if ($email)
                        <div>
                            <dt class="text-xs font-medium text-ink-400">{{ trans('site.contact.email') }}</dt>
                            <dd class="mt-1"><a href="mailto:{{ $email }}" dir="ltr" class="inline-block min-h-8 text-base font-bold text-ink-900 underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-brand-yellow">{{ $email }}</a></dd>
                        </div>
                    @endif
                    @if (count($phones) > 0)
                        <div>
                            <dt class="text-xs font-medium text-ink-400">{{ trans('site.contact.phone') }}</dt>
                            @foreach ($phones as $phone)
                                <dd class="mt-1"><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" dir="ltr" class="inline-block min-h-8 text-base font-bold tabular-nums text-ink-900 underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-brand-yellow">{{ $phone }}</a></dd>
                            @endforeach
                        </div>
                    @endif
                    @if ($whatsapp)
                        <div>
                            <dd><a href="https://wa.me/{{ preg_replace('/\D+/', '', $whatsapp) }}" rel="noopener" class="{{ $btnPrimary }}">{{ trans('site.download.whatsapp') }}</a></dd>
                        </div>
                    @endif
                </dl>
            </div>

            <form method="POST" action="{{ url('/contact') }}?lang={{ $locale }}" class="space-y-4 rounded-2xl bg-ink-50 p-6 ring-1 ring-ink-200" novalidate>
                @csrf
                {{-- Honeypot: invisible to people, tempting to bots. --}}
                <div class="absolute -start-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
                    <label>Website<input type="text" name="company_website" tabindex="-1" autocomplete="off"></label>
                </div>

                @if (session('contact_sent'))
                    <p role="status" class="rounded-xl bg-status-success/10 px-4 py-3 text-sm font-semibold text-status-success">{{ trans('site.contact.sent') }}</p>
                @endif
                @if ($contactErrors->any())
                    <ul role="alert" class="space-y-1 rounded-xl bg-status-danger/10 px-4 py-3 text-sm font-medium text-status-danger">
                        @foreach (array_unique($contactErrors->all()) as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif

                <div>
                    <label for="c-name" class="mb-1.5 block text-sm font-semibold text-ink-800">{{ trans('site.contact.name') }}</label>
                    <input id="c-name" name="name" value="{{ old('name') }}" maxlength="100" required autocomplete="name" class="{{ $field }}">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="c-email" class="mb-1.5 block text-sm font-semibold text-ink-800">{{ trans('site.contact.your_email') }}</label>
                        <input id="c-email" type="email" name="email" value="{{ old('email') }}" maxlength="190" dir="ltr" autocomplete="email" aria-describedby="c-reach" class="{{ $field }} text-start">
                    </div>
                    <div>
                        <label for="c-phone" class="mb-1.5 block text-sm font-semibold text-ink-800">{{ trans('site.contact.your_phone') }}</label>
                        <input id="c-phone" type="tel" name="phone" value="{{ old('phone') }}" maxlength="40" dir="ltr" autocomplete="tel" aria-describedby="c-reach" class="{{ $field }} text-start">
                    </div>
                </div>
                <p id="c-reach" class="-mt-1 text-xs text-ink-500">{{ trans('site.contact.reach_hint') }}</p>
                <div>
                    <label for="c-message" class="mb-1.5 block text-sm font-semibold text-ink-800">{{ trans('site.contact.message') }}</label>
                    <textarea id="c-message" name="message" rows="5" maxlength="1500" required class="{{ $field }}">{{ old('message') }}</textarea>
                </div>
                <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-brand-black px-6 py-3 text-base font-bold text-white transition-colors hover:bg-ink-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-yellow">
                    {{ trans('site.contact.send') }}
                </button>
            </form>
        </div>
    </section>

    {{-- Get started --}}
    <section id="download" class="scroll-mt-20 bg-brand-black py-16 text-center text-white sm:py-20">
        <div class="mx-auto max-w-2xl px-5">
            <h2 class="text-2xl font-extrabold sm:text-3xl">{{ trans('site.download.title') }}</h2>
            <p class="mt-4 leading-8 text-white/75">{{ trans('site.download.text') }}</p>
            @if ($ios || $android)
                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    @if ($ios)
                        <a href="{{ $ios }}" rel="noopener" class="{{ $btnPrimary }}">{{ trans('site.download.ios') }}</a>
                    @endif
                    @if ($android)
                        <a href="{{ $android }}" rel="noopener" class="{{ $btnPrimary }}">{{ trans('site.download.android') }}</a>
                    @endif
                </div>
            @endif
            <p class="mt-6"><a href="#contact" class="text-sm font-semibold text-brand-yellow underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-brand-yellow">{{ trans('site.contact.title') }}</a></p>
        </div>
    </section>
</x-layouts.site>
