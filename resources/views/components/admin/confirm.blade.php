@props(['action', 'label', 'title', 'confirmLabel', 'tone' => 'danger', 'method' => 'post', 'fields' => [], 'srLabel' => null])
@php
    // Consequential action behind a native modal <dialog>: focus is trapped,
    // Escape cancels, focus returns to the trigger, and the form only submits
    // after explicit confirmation. (The admin requires JavaScript for these.)
    $id = 'dlg-'.\Illuminate\Support\Str::random(8);
    $trigger = $tone === 'danger' ? 'border-2 border-danger-700 text-danger-700 hover:bg-danger-50' : 'border border-steel-300 bg-white text-steel-900 hover:border-steel-900';
    $confirm = $tone === 'danger' ? 'bg-danger-700 hover:bg-[#93190f]' : 'bg-steel-900 hover:bg-steel-800';
@endphp
<button type="button" data-open-dialog="{{ $id }}" {{ $attributes->merge(['class' => "inline-flex min-h-11 items-center rounded-[var(--radius-control)] px-4 text-sm font-semibold {$trigger}"]) }}>{{ $label }}@if ($srLabel)<span class="sr-only"> {{ $srLabel }}</span>@endif</button>
<dialog id="{{ $id }}" aria-labelledby="{{ $id }}-title" class="m-auto w-[min(28rem,calc(100vw-2rem))] rounded-[var(--radius-panel)] p-0 shadow-2xl">
    <form method="post" action="{{ $action }}" class="p-6">
        @csrf
        @if (strtolower($method) !== 'post') @method($method) @endif
        @foreach ($fields as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
        <h2 id="{{ $id }}-title" class="text-xl font-bold text-steel-950">{{ $title }}</h2>
        <div class="mt-2 text-steel-700">{{ $slot }}</div>
        <div class="mt-6 flex flex-wrap justify-end gap-2">
            <button type="button" data-close-dialog autofocus class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] border-2 border-steel-900 px-4 font-semibold text-steel-900 hover:bg-steel-50">Cancel</button>
            <button type="submit" class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] px-4 font-semibold text-white {{ $confirm }}">{{ $confirmLabel }}</button>
        </div>
    </form>
</dialog>
