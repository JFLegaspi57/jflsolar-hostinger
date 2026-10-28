@props(['pkg', 'heading' => 'h3', 'compareForm' => null, 'showImage' => false, 'admin' => false])
@php
    use App\Support\Labels;
    use App\Support\Pricing;
    use App\Support\Site;
    $headingId = 'pkg-'.$pkg->slug;
    $promo = $pkg->promo_state ?? $pkg->promoState();
    $image = $showImage ? ($pkg->images->firstWhere('is_cover', true) ?? $pkg->images->first()) : null;
@endphp
<article aria-labelledby="{{ $headingId }}"
    class="{{ $showImage ? 'row-span-7' : 'row-span-6' }} grid grid-rows-subgrid gap-y-0 overflow-hidden rounded-[var(--radius-panel)] border bg-white p-5 sm:p-6 {{ $pkg->package_type === 'installed_system' ? 'border-steel-300' : 'border-gold-200' }}">
    @if ($showImage)
        {{-- Fixed frame: the photo is positioned inside it, so every card's image area is the same height. --}}
        <div class="relative -mx-5 -mt-5 mb-5 aspect-[16/10] border-b border-steel-200 bg-white sm:-mx-6 sm:-mt-6">
            @if ($image)
                <x-media-image :asset="$image" :admin="$admin" sizes="(min-width: 1024px) 380px, (min-width: 768px) 50vw, 100vw" fit="contain" class="absolute inset-0" />
            @else
                <div class="absolute inset-0"><x-placeholder-image label="Package photo pending" /></div>
            @endif
        </div>
    @endif
    <div class="flex flex-wrap items-center gap-2">
        <x-package-type-tag :type="$pkg->package_type" />
        @if ($pkg->system_type)<span class="text-xs font-semibold tracking-wide text-steel-600 uppercase">{{ Labels::SYSTEM_TYPE[$pkg->system_type] }}</span>@endif
        @if ($pkg->is_sample && ! Site::isProduction())<x-sample-badge />@endif
    </div>
    <{{ $heading }} id="{{ $headingId }}" class="mt-3 text-xl font-bold text-steel-950">
        <a href="/packages/{{ $pkg->slug }}" class="hover:underline">{{ $pkg->name }}</a>
    </{{ $heading }}>
    <p class="mt-2 text-[0.95rem] text-steel-600">{{ $pkg->summary }}</p>
    <div class="mt-4">
        <x-spec-row :inverter="$pkg->inverter_kw" :array="$pkg->array_kwp" :battery="$pkg->battery_kwh" dense />
    </div>
    <div class="mt-5 flex-1">
        <x-price-block :price="$pkg->price($promo)" :promo="$promo" :promo-label="$pkg->promo_label" :promo-window="Pricing::promoWindow($pkg->promoStart(), $pkg->promoEnd())" />
        <p class="mt-2 flex items-center gap-1.5 text-sm text-steel-700">
            <x-icon :name="$pkg->fulfillment === 'installed' ? 'tools' : 'warehouse'" class="size-4 text-steel-500" />
            {{ Labels::FULFILLMENT[$pkg->fulfillment] }}
        </p>
    </div>
    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-steel-200 pt-4">
        <a href="/packages/{{ $pkg->slug }}" class="inline-flex min-h-11 items-center gap-1.5 font-semibold text-steel-900 hover:underline">
            View package <span class="sr-only">{{ $pkg->name }}</span>
            <x-icon name="arrow" class="size-4" />
        </a>
        @if ($compareForm)
            <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 text-sm font-semibold text-steel-700">
                <input type="checkbox" name="p" value="{{ $pkg->slug }}" form="{{ $compareForm }}" class="size-5 rounded border-steel-500 accent-steel-900" data-compare>
                Compare <span class="sr-only">{{ $pkg->name }}</span>
            </label>
        @endif
    </div>
</article>
