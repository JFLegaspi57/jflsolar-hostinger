@php
    use App\Support\Site;
    $field = 'min-h-11 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3 text-base text-steel-900 focus:border-steel-900';
    $chip = 'inline-flex min-h-11 shrink-0 items-center rounded-full border border-steel-300 px-4 text-sm font-semibold whitespace-nowrap text-steel-800 hover:border-steel-900 aria-[current=page]:border-steel-900 aria-[current=page]:bg-steel-900 aria-[current=page]:text-white';
    $hasFilters = $filters['q'] !== '' || $filters['category'] !== '' || $filters['brand'] !== '';
@endphp
<x-site-layout title="Solar Equipment" path="/products" description="Solar panels, inverters, batteries, mounting hardware, cables, connectors and protection devices. Search the JFL Solar catalogue and request a product quote.">
    <x-page-hero eyebrow="Products" title="Solar equipment" intro="We offer free comprehensive and unparalleled technical support, from installation, commissioning, and to troubleshooting, for all clients who purchase their supplies from us."
        image="/images/products-rooftop-1280.jpg" srcset="/images/products-rooftop-640.jpg 640w, /images/products-rooftop-960.jpg 960w, /images/products-rooftop-1280.jpg 1280w"
        imageAlt="Rows of solar panels installed on a large metal rooftop under a bright sky" focus="35% 70%">
        <p class="mt-5 text-xl font-extrabold text-gold-400">Sagot ka namin!</p>
    </x-page-hero>
    {{-- Brand strip (logos from the owner's brand sheet). --}}
    <section aria-label="Brands we carry" class="border-b border-steel-200 bg-white">
        <x-container>
            <img src="/images/brand-logos-v2-2400.webp" srcset="/images/brand-logos-v2-1200.webp 1200w, /images/brand-logos-v2-2400.webp 2400w" sizes="(min-width: 1280px) 1216px, 100vw"
                width="2400" height="520" loading="lazy" decoding="async" class="block h-auto w-full"
                alt="Brands we carry: GoodWe, Jinko Solar, Solis, Luxpower Tek, Seraphim, AESOLAR, Dyness, ATE and Projoy Electric">
            <p class="pb-4 text-center text-sm text-steel-600">For DIY buyers and professional installers. Prices are confirmed on request; listing a brand does not imply an authorised distributorship.</p>
        </x-container>
    </section>
    <x-container class="py-10">
        <form action="/products" method="get" role="search" aria-label="Search products" class="grid gap-3 rounded-[var(--radius-panel)] border border-steel-200 bg-steel-50 p-4 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr_auto] lg:items-end">
            <div>
                <label for="q" class="mb-1 block text-sm font-semibold text-steel-900">Search</label>
                <input id="q" name="q" type="search" value="{{ $filters['q'] }}" placeholder="Model, brand or keyword" class="{{ $field }}">
            </div>
            <div>
                <label for="category" class="mb-1 block text-sm font-semibold text-steel-900">Category</label>
                <select id="category" name="category" class="{{ $field }}">
                    <option value="">All categories</option>
                    @foreach ($categories as $c)<option value="{{ $c->slug }}" @selected($filters['category'] === $c->slug)>{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="brand" class="mb-1 block text-sm font-semibold text-steel-900">Brand</label>
                <select id="brand" name="brand" class="{{ $field }}">
                    <option value="">All brands</option>
                    @foreach ($brands as $b)<option value="{{ $b }}" @selected($filters['brand'] === $b)>{{ $b }}</option>@endforeach
                </select>
            </div>
            <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                <button type="submit" class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-[var(--radius-control)] bg-steel-900 px-5 font-semibold text-white hover:bg-steel-800 lg:flex-none"><x-icon name="search" class="size-4" /> Search</button>
                @if ($hasFilters)<a href="/products" class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] border border-steel-400 px-4 font-semibold text-steel-900 hover:bg-white">Clear</a>@endif
            </div>
        </form>

        <nav aria-label="Categories" class="mt-6 flex flex-wrap gap-2">
            <a href="/products" @if ($filters['category'] === '') aria-current="page" @endif class="{{ $chip }}">All</a>
            @foreach ($categories as $c)
                <a href="/products?category={{ $c->slug }}" @if ($filters['category'] === $c->slug) aria-current="page" @endif class="{{ $chip }}">{{ $c->name }}</a>
            @endforeach
        </nav>

        <div class="mt-6 flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="text-2xl font-bold text-steel-950">{{ $activeCategory?->name ?? 'All products' }}</h2>
            <p role="status" class="text-sm text-steel-600">
                {{ $results->count() }} {{ $results->count() === 1 ? 'product' : 'products' }}@if ($filters['q'] !== '') matching “{{ $filters['q'] }}”@endif @if ($filters['brand'] !== '') · {{ $filters['brand'] }}@endif
            </p>
        </div>

        @if ($results->isNotEmpty())
            <ul class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($results as $p)
                    <li class="flex"><x-product-card :product="$p" /></li>
                @endforeach
            </ul>
        @else
            <div class="mt-6 rounded-[var(--radius-panel)] border border-dashed border-steel-300 p-8 text-center">
                <p class="text-lg font-semibold text-steel-900">No products match your search.</p>
                <p class="mx-auto mt-2 max-w-md text-steel-600">Our catalogue online is not exhaustive. Tell us what you need and we will check availability.</p>
                <div class="mt-5 flex flex-wrap justify-center gap-3">
                    <a href="/products" class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] border border-steel-400 px-4 font-semibold hover:bg-steel-50">Clear filters</a>
                    <a href="{{ Site::QUOTE_HREF }}" class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] bg-gold-500 px-4 font-semibold text-steel-950 hover:bg-gold-400">Request a product quote</a>
                </div>
            </div>
        @endif
    </x-container>
</x-site-layout>
