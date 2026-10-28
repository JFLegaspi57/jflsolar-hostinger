@props(['form', 'name', 'label', 'hint' => null, 'required' => false, 'maxlength' => null, 'type' => 'text', 'inputmode' => null, 'placeholder' => null])
@php
    $error = $form->error($name);
    $described = trim(($hint ? "f-{$name}-hint " : '').($error ? "f-{$name}-error" : ''));
@endphp
<x-admin.field-wrap :name="$name" :label="$label" :hint="$hint" :error="$error" :required="$required" {{ $attributes->only('class') }}>
    <input id="f-{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $form->value($name) }}"
        @if ($inputmode) inputmode="{{ $inputmode }}" @endif @if ($maxlength) maxlength="{{ $maxlength }}" @endif @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($error) aria-invalid="true" @endif @if ($described) aria-describedby="{{ $described }}" @endif
        class="min-h-11 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3 text-base text-steel-950 focus:border-steel-900 aria-[invalid=true]:border-danger-700">
</x-admin.field-wrap>
