@php
    $current = \Illuminate\Support\Facades\Route::currentRouteName();
@endphp

@foreach ($nav as $section => $items)
    <div>
        <p class="px-3 mb-2 text-xs font-medium text-white/40">{{ $section }}</p>
        <ul class="space-y-0.5">
            @foreach ($items as $item)
                @php
                    $isActive = $current === $item['route']
                        && (!isset($item['arg']) || request()->route('section') === $item['arg'])
                        && (!isset($item['query']) || request()->query() == $item['query']);
                    $href = isset($item['arg'])
                        ? route($item['route'], $item['arg'])
                        : route($item['route'], $item['query'] ?? []);
                @endphp
                <li>
                    <a href="{{ $href }}"
                       @if($isActive) aria-current="page" @endif
                       class="flex items-center gap-3 rounded-md px-3 py-2.5 lg:py-2 transition-colors {{ $isActive ? 'bg-brand-yellow text-brand-black font-medium' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                        <x-icon :name="$item['icon']" class="size-5 shrink-0" />
                        <span>{{ $item['label'] }}</span>
                        @isset($item['badge'])
                            <span data-badge="{{ $item['badge'] }}" hidden class="ms-auto min-w-5 rounded-full bg-brand-yellow px-1.5 text-center text-xs font-bold text-brand-black"></span>
                        @endisset
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endforeach
