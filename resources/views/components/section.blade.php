@props(['tone' => 'white', 'labelledby' => null])
@php
    $tones = [
        'white' => 'bg-white',
        'muted' => 'bg-steel-50 border-y border-steel-200',
        'dark' => 'bg-steel-950 text-white on-dark',
    ];
@endphp
<section @if ($labelledby) aria-labelledby="{{ $labelledby }}" @endif {{ $attributes->merge(['class' => 'py-16 sm:py-20 lg:py-24 '.$tones[$tone]]) }}>
    <x-container>{{ $slot }}</x-container>
</section>
