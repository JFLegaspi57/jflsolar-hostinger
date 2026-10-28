@props(['form', 'name', 'label', 'hint' => null, 'required' => false, 'maxlength' => null, 'rows' => 4, 'mono' => false])
@php
    $error = $form->error($name);
    $described = trim(($hint ? "f-{$name}-hint " : '').($error ? "f-{$name}-error" : ''));
@endphp
<x-admin.field-wrap :name="$name" :label="$label" :hint="$hint" :error="$error" :required="$required" {{ $attributes->only('class') }}>
    <textarea id="f-{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" @if ($maxlength) maxlength="{{ $maxlength }}" @endif
        @if ($error) aria-invalid="true" @endif @if ($described) aria-describedby="{{ $described }}" @endif
        class="w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3 py-2.5 text-base text-steel-950 focus:border-steel-900 aria-[invalid=true]:border-danger-700 {{ $mono ? 'font-mono text-sm' : '' }}">{{ $form->value($name) }}</textarea>
</x-admin.field-wrap>
