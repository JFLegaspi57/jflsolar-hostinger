@php
    use App\Support\Site;
    $field = 'min-h-11 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3 text-base text-steel-900 focus:border-steel-900';
    $chip = 'inline-flex min-h-11 shrink-0 items-center rounded-full border border-steel-300 px-4 text-sm font-semibold whitespace-nowrap text-steel-800 hover:border-steel-900 aria-[current=page]:border-steel-900 aria-[current=page]:bg-steel-900 aria-[current=page]:text-white';
    $hasFilters = $filters['q'] !== '' || $filters['category'] !== '' || $filters['brand'] !== '';
@endphp
<x-site-layout title="Solar Equipment" path="/products" :reviews-above-footer="false" description="Solar panels, inverters, batteries, mounting hardware, cables, connectors and protection devices. Search the JFL Solar catalogue and request a product quote.">
    <x-page-hero eyebrow="Products" title="Solar equipment" intro="We offer free comprehensive and unparalleled technical support, from installation, commissioning, and to troubleshooting, for all clients who purchase their supplies from us."
        image="/images/products-rooftop-1280.jpg" srcset="/images/products-rooftop-640.jpg 640w, /images/products-rooftop-960.jpg 960w, /images/products-rooftop-1280.jpg 1280w"
        imageAlt="Rows of solar panels installed on a large metal rooftop under a bright sky" focus="35% 70%">
        <p class="mt-5 text-xl font-extrabold text-gold-400">Sagot ka namin!</p>
    </x-page-hero>
    {{-- Hidden while a category is selected, so the products come into view sooner. --}}
    @if ($filters['category'] === '')
    {{-- Brand strip (logos from the owner's brand sheet). Each logo is a link to that brand's products:
         the link shows its own slice of the same image, so it lines up exactly and can lift on hover. --}}
    @php
        // [label, brand filter value, x, y, width, height] in pixels of the 2400×520 image (logo box plus padding).
        $logos = [
            ['GoodWe', 'GoodWe', 43, 80, 506, 158], ['Jinko Solar', 'Jinko Solar', 589, 80, 348, 158], ['Solis', 'Solis', 978, 80, 328, 158],
            ['Luxpower Tek', 'LuxPower', 1347, 80, 479, 158], ['Seraphim', 'Seraphim', 1868, 80, 486, 158],
            ['AESOLAR', 'AE Solar', 70, 287, 534, 145], ['Sungrow', 'Sungrow', 645, 287, 552, 145], ['Dyness', 'Dyness', 1238, 287, 437, 145],
            ['ATE', 'Alltopelec (ATE)', 1717, 287, 238, 145], ['Projoy Electric', 'Projoy', 1997, 287, 331, 145],
        ];
    @endphp
    <section aria-label="Brands we carry" class="border-b border-steel-200 bg-white">
        <x-container>
            <div class="brand-strip relative">
                <img src="/images/brand-logos-v3-2400.webp" srcset="/images/brand-logos-v3-1200.webp 1200w, /images/brand-logos-v3-2400.webp 2400w" sizes="(min-width: 1280px) 1216px, 100vw"
                    width="2400" height="520" loading="lazy" decoding="async" class="block h-auto w-full" alt="">
                <ul class="m-0 list-none p-0">
                    @foreach ($logos as [$label, $brand, $x, $y, $w, $h])
                        <li>
                            <a href="/products?brand={{ urlencode($brand) }}#results" class="brand-logo" title="{{ $label }} products"
                                @if ($filters['brand'] === $brand) aria-current="page" @endif
                                style="left: {{ round($x / 24, 3) }}%; top: {{ round($y / 5.2, 3) }}%; width: {{ round($w / 24, 3) }}%; height: {{ round($h / 5.2, 3) }}%;
                                       background-size: {{ round(2400 / $w * 100, 3) }}% auto; background-position: {{ round($x / (2400 - $w) * 100, 3) }}% {{ round($y / (520 - $h) * 100, 3) }}%;">
                                <span class="sr-only">{{ $label }} products</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </x-container>
    </section>
    <style>
        .brand-strip .brand-logo { position: absolute; display: block; border-radius: 0.75rem; background-color: #fff; background-image: url('/images/brand-logos-v3-2400.webp'); background-repeat: no-repeat;
            transition: transform 0.25s ease, box-shadow 0.25s ease; }
        .brand-strip .brand-logo:hover, .brand-strip .brand-logo:focus-visible { transform: translateY(-4px) scale(1.07); box-shadow: 0 10px 24px -8px rgba(31, 41, 55, 0.35), 0 0 0 1px rgba(31, 41, 55, 0.08); z-index: 1; }
        .brand-strip .brand-logo:focus-visible { outline: 3px solid #f5b400; outline-offset: 2px; }
        .brand-strip .brand-logo[aria-current="page"] { box-shadow: 0 0 0 2px #f5b400; }
        @media (prefers-reduced-motion: reduce) { .brand-strip .brand-logo { transition: box-shadow 0.25s ease; } .brand-strip .brand-logo:hover, .brand-strip .brand-logo:focus-visible { transform: none; } }
    </style>
    @endif
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

        <div id="results" class="mt-6 flex flex-wrap items-baseline justify-between gap-2" style="scroll-margin-top: 6rem">
            <h2 class="text-2xl font-bold text-steel-950">{{ $activeCategory?->name ?? 'All products' }}</h2>
            <p role="status" class="text-sm text-steel-600">
                {{ $results->count() }} {{ $results->count() === 1 ? 'product' : 'products' }}@if ($filters['q'] !== '') matching “{{ $filters['q'] }}”@endif @if ($filters['brand'] !== '') · {{ $filters['brand'] }}@endif
            </p>
        </div>

        @if ($results->isNotEmpty() && $filters['category'] === '')
            {{-- All products: one group per category, in the menu's category order. --}}
            @php $byCategory = $results->groupBy('category_id'); @endphp
            @foreach ($categories as $c)
                @continue(! $byCategory->has($c->id))
                @php $items = $byCategory->get($c->id); @endphp
                <section aria-labelledby="cat-{{ $c->slug }}" class="mt-8" style="scroll-margin-top: 6rem" id="group-{{ $c->slug }}">
                    <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-steel-200 pb-2">
                        <h3 id="cat-{{ $c->slug }}" class="text-xl font-bold text-steel-950">{{ $c->name }} <span class="text-base font-normal text-steel-600">({{ $items->count() }})</span></h3>
                        <a href="/products?category={{ $c->slug }}{{ $filters['brand'] !== '' ? '&brand='.rawurlencode($filters['brand']) : '' }}{{ $filters['q'] !== '' ? '&q='.rawurlencode($filters['q']) : '' }}#results" class="inline-flex min-h-11 items-center text-sm font-semibold text-steel-900 underline">Only {{ $c->name }}</a>
                    </div>
                    <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($items as $p)
                            <li class="flex"><x-product-card :product="$p" heading="h4" /></li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
            @php $other = $results->reject(fn ($p) => $categories->contains('id', $p->category_id)); @endphp
            @if ($other->isNotEmpty())
                <section aria-labelledby="cat-other" class="mt-10">
                    <h3 id="cat-other" class="border-b border-steel-200 pb-2 text-xl font-bold text-steel-950">Other products</h3>
                    <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($other as $p)<li class="flex"><x-product-card :product="$p" heading="h4" /></li>@endforeach
                    </ul>
                </section>
            @endif
        @elseif ($results->isNotEmpty())
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
