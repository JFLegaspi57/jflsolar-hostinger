@props(['id', 'label', 'hint' => null, 'error' => null, 'required' => false])
<div>
    <label for="{{ $id }}" class="mb-1.5 block font-semibold text-steel-950">{{ $label }}@if ($required)<span class="ml-1 text-sm font-normal text-steel-600">(required)</span>@endif</label>
    @if ($hint)<p id="{{ $id }}-hint" class="-mt-1 mb-1.5 text-sm text-steel-600">{{ $hint }}</p>@endif
    {{ $slot }}
    @if ($error)
        <p id="{{ $id }}-error" class="mt-1.5 flex items-start gap-1.5 text-sm font-semibold text-danger-700"><x-icon name="x" class="mt-0.5 size-4" /> {{ $error }}</p>
    @endif
</div>
