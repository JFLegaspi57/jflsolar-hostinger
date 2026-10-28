@props(['status'])
@php
    $styles = [
        'published' => 'bg-ok-50 text-ok-700 border-[#b7e2c6]',
        'draft' => 'bg-steel-100 text-steel-800 border-steel-300',
        'archived' => 'bg-white text-steel-600 border-steel-300 border-dashed',
    ];
@endphp
<span class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-bold capitalize {{ $styles[$status] ?? $styles['draft'] }}">{{ $status }}</span>
