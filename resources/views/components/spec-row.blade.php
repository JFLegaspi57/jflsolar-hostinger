@props(['inverter' => null, 'array' => null, 'battery' => null, 'dense' => false])
@php
    use App\Support\Pricing;
    $items = [
        ['label' => 'Inverter', 'value' => Pricing::formatCapacity($inverter, 'kW')],
        ['label' => 'Solar array', 'value' => Pricing::formatCapacity($array, 'kWp')],
        ['label' => 'Battery', 'value' => Pricing::formatCapacity($battery, 'kWh')],
    ];
    // All empty = system is sized after a site assessment (e.g. C&I packages).
    $allEmpty = collect($items)->every(fn ($i) => ! $i['value']);
@endphp
{{-- Inverter kW · Array kWp · Battery kWh, as a compact definition list. --}}
<dl class="grid grid-cols-3 divide-x divide-steel-200 rounded-[var(--radius-control)] border border-steel-200 bg-steel-50 {{ $dense ? 'text-sm' : '' }}">
    @foreach ($items as $i)
        <div class="min-w-0 px-2 py-2 sm:px-3">
            <dt class="truncate text-[0.68rem] font-semibold text-steel-600 uppercase sm:tracking-wide">{{ $i['label'] }}</dt>
            <dd class="font-display font-bold tabular {{ $i['value'] ? 'text-steel-950' : 'text-steel-600' }} {{ $dense ? 'text-[0.95rem] sm:text-base' : 'text-base sm:text-lg' }}">
                @if ($i['value']){{ $i['value'] }}@else<span class="text-sm font-semibold">{{ $allEmpty ? 'After assessment' : 'Not included' }}</span>@endif
            </dd>
        </div>
    @endforeach
</dl>
