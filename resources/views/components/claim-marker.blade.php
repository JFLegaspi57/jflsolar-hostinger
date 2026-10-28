@props(['block'])
{{-- "Awaiting confirmation" marker on unconfirmed copy (preview only). --}}
@if (! \App\Support\Site::isProduction() && $block->claim_status !== 'confirmed')
    <span {{ $attributes->merge(['class' => 'block']) }}><x-pending-badge /></span>
@endif
