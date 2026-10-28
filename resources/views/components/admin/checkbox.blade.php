@props(['form', 'name', 'label', 'hint' => null])
@php
    $error = $form->error($name);
    $described = trim(($hint ? "f-{$name}-hint " : '').($error ? "f-{$name}-error" : ''));
@endphp
<div {{ $attributes->only('class') }}>
    <label for="f-{{ $name }}" class="flex min-h-11 cursor-pointer items-start gap-3">
        <input id="f-{{ $name }}" name="{{ $name }}" type="checkbox" value="on" @checked($form->checked($name)) @if ($described) aria-describedby="{{ $described }}" @endif class="mt-1 size-5 shrink-0 accent-steel-900">
        <span>
            <span class="font-semibold text-steel-950">{{ $label }}</span>
            @if ($hint)<span id="f-{{ $name }}-hint" class="block text-sm text-steel-600">{{ $hint }}</span>@endif
        </span>
    </label>
    @if ($error)<p id="f-{{ $name }}-error" class="mt-1 text-sm font-semibold text-danger-700">{{ $error }}</p>@endif
</div>
