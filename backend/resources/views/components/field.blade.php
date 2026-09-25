{{-- Labeled form field with hint + inline validation error, linked for screen readers. --}}
@props(['label', 'name', 'hint' => null])

<div>
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-ink-700">{{ $label }}</label>
    {{ $slot }}
    @if ($hint)
        <p id="{{ $name }}-hint" class="mt-1 text-xs text-ink-400">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $name }}-error" class="mt-1 text-xs text-status-danger" role="alert">{{ $message }}</p>
    @enderror
</div>
