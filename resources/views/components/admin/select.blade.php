@props(['form', 'name', 'label', 'options', 'hint' => null, 'required' => false])
{{-- $options: [value => label] --}}
@php
    $error = $form->error($name);
    $described = trim(($hint ? "f-{$name}-hint " : '').($error ? "f-{$name}-error" : ''));
    $current = $form->value($name);
@endphp
<x-admin.field-wrap :name="$name" :label="$label" :hint="$hint" :error="$error" :required="$required" {{ $attributes->only('class') }}>
    <select id="f-{{ $name }}" name="{{ $name }}" @if ($error) aria-invalid="true" @endif @if ($described) aria-describedby="{{ $described }}" @endif
        class="min-h-11 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3 text-base text-steel-950 focus:border-steel-900 aria-[invalid=true]:border-danger-700">
        @foreach ($options as $value => $optionLabel)
            <option value="{{ $value }}" @selected((string) $current === (string) $value)>{{ $optionLabel }}</option>
        @endforeach
    </select>
</x-admin.field-wrap>
