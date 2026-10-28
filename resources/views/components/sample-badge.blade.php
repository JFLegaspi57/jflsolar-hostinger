@props(['label' => 'Sample'])
{{-- Marks demo records in the preview. Samples are never shown on the live site. --}}
<x-badge tone="sample" {{ $attributes }}><span aria-hidden="true">◇</span> {{ $label }}</x-badge>
