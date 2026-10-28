@props(['name', 'label', 'hint' => null, 'error' => null, 'required' => false])
<div {{ $attributes }}>
    <label for="f-{{ $name }}" class="mb-1 block font-semibold text-steel-950">{{ $label }}@if ($required)<span class="ml-1 text-sm font-normal text-steel-600">(required)</span>@endif</label>
    @if ($hint)<p id="f-{{ $name }}-hint" class="-mt-0.5 mb-1.5 text-sm text-steel-600">{{ $hint }}</p>@endif
    {{ $slot }}
    @if ($error)<p id="f-{{ $name }}-error" class="mt-1 text-sm font-semibold text-danger-700">{{ $error }}</p>@endif
</div>
