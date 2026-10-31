{{-- "For directions" line under a pickup address. --}}
@php $maps = \App\Support\Shop::directionLinks(); @endphp
<span class="block text-sm text-steel-700">For directions, search “{{ \App\Support\Shop::MAPS_NAME }}” on
    <a href="{{ $maps['google'] }}" target="_blank" rel="noopener" class="font-semibold underline">Google Maps</a> or
    <a href="{{ $maps['waze'] }}" target="_blank" rel="noopener" class="font-semibold underline">Waze</a>.</span>
