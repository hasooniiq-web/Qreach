{{-- The admin's photo, or their initial when none is set. `v` busts the browser cache after a change. --}}
@props(['admin', 'size' => 'size-8'])

@if ($admin->avatar_path)
    <img src="{{ route('admin.profile.avatar', ['v' => $admin->updated_at?->timestamp]) }}" alt=""
         {{ $attributes->merge(['class' => "$size shrink-0 rounded-full object-cover"]) }}>
@else
    <span {{ $attributes->merge(['class' => "$size flex shrink-0 items-center justify-center rounded-full bg-ink-100 text-xs font-semibold text-ink-600"]) }} aria-hidden="true">
        {{ mb_substr($admin->name, 0, 1) }}
    </span>
@endif
