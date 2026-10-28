@props(['price', 'promo' => 'none', 'promoLabel' => null, 'promoWindow' => null, 'size' => 'md'])
{{-- $price = Pricing::display(...) array; $promoWindow = Pricing::promoWindow(...) --}}
@php
    $headlineSize = $size === 'lg' ? ($price['hasAmount'] ? 'text-4xl' : 'text-2xl') : ($price['hasAmount'] ? 'text-2xl' : 'text-lg');
@endphp
<div class="space-y-1.5">
    <p class="font-display font-extrabold tabular {{ $price['hasAmount'] ? 'text-steel-950' : 'text-steel-700' }} {{ $headlineSize }}">{{ $price['headline'] }}</p>
    @if ($price['basis'])
        <p class="text-sm text-steel-600">{{ $price['basis'] }}</p>
    @endif
    @if ($price['isSample'] || $promo !== 'none')
        <div class="flex flex-wrap gap-1.5 pt-0.5">
            @if ($price['isSample'])<x-badge tone="sample">Sample price — not a confirmed offer</x-badge>@endif
            @if ($promo === 'active')<x-badge tone="promo">{{ $promoLabel ?: 'Promotion' }}{{ $promoWindow ? ' · '.$promoWindow : '' }}</x-badge>@endif
            @if ($promo === 'upcoming')<x-badge tone="promo">Starts {{ $promoWindow }}</x-badge>@endif
            @if ($promo === 'ended')<x-badge tone="ended">Ended{{ $promoWindow ? ' · '.$promoWindow : '' }}</x-badge>@endif
        </div>
    @endif
</div>
