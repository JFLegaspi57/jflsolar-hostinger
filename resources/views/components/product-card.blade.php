@props(['product', 'admin' => false])
@php
    use App\Support\Labels;
    use App\Support\Site;
    $price = $product->price();
    $image = $product->images->firstWhere('is_cover', true) ?? $product->images->first();
    $slug = $product->category?->slug;
    $glyph = match ($slug) { 'inverters' => 'inverter', 'batteries' => 'battery', 'solar-panels' => 'panel', default => 'hardware' };
@endphp
<article class="group relative flex w-full flex-col overflow-hidden rounded-[var(--radius-panel)] border border-steel-200 bg-white transition-shadow hover:shadow-md">
    <div class="relative aspect-[4/3] overflow-hidden bg-steel-100">
        @if ($image)
            <x-media-image :asset="$image" :admin="$admin" sizes="(min-width: 1024px) 280px, (min-width: 640px) 45vw, 100vw" fit="contain" class="absolute inset-0 bg-white" />
        @else
            <x-placeholder-image label="Product photo pending" :icon="$glyph" />
        @endif
    </div>
    <div class="flex flex-1 flex-col p-4">
        <p class="text-xs font-semibold tracking-wide text-steel-600 uppercase">{{ $product->category?->name }}@if ($product->brand) · {{ $product->brand }}@endif</p>
        <h3 class="mt-1 line-clamp-2 min-h-[2lh] text-base leading-snug font-bold text-steel-950">
            <a href="/products/{{ $product->slug }}" class="after:absolute after:inset-0 hover:underline">{{ $product->name }}</a>
        </h3>
        <p class="mt-0.5 min-h-[1lh] truncate font-mono text-xs text-steel-600">{{ $product->model ?? '' }}</p>
        <div class="mt-3 flex flex-1 flex-wrap items-end justify-between gap-2">
            <p class="font-semibold {{ $price['hasAmount'] ? 'text-steel-950 tabular' : 'text-steel-700' }} text-sm">{{ $price['hasAmount'] ? $price['headline'] : 'Request price' }}</p>
            <p class="text-xs text-steel-600">{{ Labels::AVAILABILITY[$product->availability] }}</p>
        </div>
        @if ($product->is_sample && ! Site::isProduction())
            <div class="mt-2"><x-sample-badge label="Sample listing" /></div>
        @endif
    </div>
</article>
