@props(['product', 'settings', 'admin' => false])
@php
    use App\Support\Labels;
    use App\Support\Site;
    use App\Support\Ui;
    $images = $product->images;
    $cover = $images->firstWhere('is_cover', true) ?? $images->first();
    $rest = $images->reject(fn ($m) => $cover && $m->id === $cover->id)->values();
    $quoteHref = '/contact?product='.rawurlencode($product->slug).'#quote';
    $buyable = ! $admin && \App\Support\Shop::visible() && \App\Support\Shop::sellable($product);
    $perMetre = $buyable && \App\Support\Shop::perMetre($product);
    $glyph = match ($product->category?->slug) { 'inverters' => 'inverter', 'batteries' => 'battery', 'solar-panels' => 'panel', default => 'hardware' };
    $crumbs = [['href' => '/', 'label' => 'Home'], ['href' => '/products', 'label' => 'Products']];
    if ($product->category) {
        $crumbs[] = ['href' => '/products?category='.$product->category->slug, 'label' => $product->category->name];
    }
    $crumbs[] = ['label' => $product->name];
@endphp
<article aria-labelledby="product-title">
    <x-container class="py-8 sm:py-10">
        <x-breadcrumbs :items="$crumbs" />
        <div class="mt-8 grid gap-10 lg:grid-cols-2">
            <div class="lg:col-start-2 lg:row-start-1">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($product->category)<span class="text-sm font-semibold tracking-wide text-steel-600 uppercase">{{ $product->category->name }}</span>@endif
                    @if ($product->is_sample && ! Site::isProduction())<x-sample-badge label="Sample listing" />@endif
                </div>
                <h1 id="product-title" class="mt-2 text-3xl font-extrabold text-steel-950 sm:text-4xl">{{ $product->name }}</h1>
                <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm text-steel-700">
                    @if ($product->brand)<div class="flex gap-1.5"><dt class="font-semibold">Brand:</dt><dd>{{ $product->brand }}</dd></div>@endif
                    @if ($product->model)<div class="flex gap-1.5"><dt class="font-semibold">Model:</dt><dd class="font-mono">{{ $product->model }}</dd></div>@endif
                    @if ($product->sku)<div class="flex gap-1.5"><dt class="font-semibold">SKU:</dt><dd class="font-mono">{{ $product->sku }}</dd></div>@endif
                </dl>
                @if ($product->summary)<p class="mt-4 text-lg text-steel-700">{{ $product->summary }}</p>@endif

                <div class="mt-6 rounded-[var(--radius-panel)] border border-steel-300 p-5">
                    @if ($product->pricing_mode === 'quote')
                        <p class="font-display text-2xl font-extrabold text-steel-800">Request price</p>
                    @else
                        <x-price-block :price="$product->price()" size="lg" />
                    @endif
                    <p class="mt-3"><x-badge :tone="$product->availability === 'unavailable' ? 'ended' : 'neutral'">{{ Labels::AVAILABILITY[$product->availability] }}</x-badge></p>
                    <p class="mt-2 text-xs text-steel-600">{{ $buyable ? 'We do not show live stock levels. After you order, we contact you to confirm your pickup.' : 'Availability is confirmed when we quote. We do not show live stock levels.' }}</p>
                    @if ($buyable)
                        @if (\App\Support\Shop::previewing())
                            <p class="mt-4"><x-badge tone="pending">Staff preview — customers don’t see this yet</x-badge></p>
                        @endif
                        @if (session('cart_added'))
                            <div role="status" class="mt-4 rounded-[var(--radius-control)] border border-[#b7e2c6] bg-ok-50 p-3 text-ok-700">
                                <p class="flex items-center gap-2 font-semibold"><x-icon name="check" class="size-5" /> Added to your cart — {{ session('cart_added') }} in total.</p>
                                <p class="mt-1 text-sm"><a href="/cart" class="font-semibold underline">View cart and check out</a></p>
                            </div>
                        @endif
                        @if (session('cart_error'))
                            <p role="alert" class="mt-4 rounded-[var(--radius-control)] border border-[#f3c3be] bg-danger-50 p-3 font-semibold text-danger-700">{{ session('cart_error') }}</p>
                        @endif
                        <form method="post" action="/cart/add" class="mt-5 flex flex-col gap-2 sm:flex-row sm:items-end">
                            @csrf
                            <input type="hidden" name="product" value="{{ $product->id }}">
                            <div>
                                <label for="buy-qty" class="mb-1.5 block text-sm font-semibold text-steel-950">Quantity{{ $perMetre ? ' (metres)' : '' }}</label>
                                <input id="buy-qty" name="quantity" type="number" inputmode="numeric" min="1" max="{{ config('shop.max_quantity') }}" step="1" value="1" required
                                    style="width: 8rem" class="min-h-12 rounded-[var(--radius-control)] border border-steel-500 bg-white px-3.5 text-base text-steel-950 tabular focus:border-steel-900">
                            </div>
                            <button type="submit" class="{{ Ui::button('primary', 'lg', 'flex-1') }}"><x-icon name="cart" class="size-5" /> Add to cart</button>
                        </form>
                        <p class="mt-3 text-sm text-steel-600">{{ \App\Support\Shop::howToBuy($settings) }}</p>
                    @else
                    <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                        <a href="{{ $quoteHref }}" class="{{ Ui::button('primary', 'lg', 'flex-1') }}">Request a Product Quote</a>
                        <a href="{{ Site::telHref($settings->phone_e164) }}" class="{{ Ui::button('secondary', 'lg') }}"><x-icon name="phone" class="size-4" /> Call</a>
                    </div>
                    @endif
                </div>

                @if ($product->datasheet)
                    <a href="{{ $product->datasheet->url(null, $admin) }}" download class="mt-4 inline-flex min-h-11 items-center gap-2 font-semibold text-steel-900 underline">
                        <x-icon name="file" class="size-5 text-gold-800" />
                        Download datasheet (PDF{{ $product->datasheet->bytes ? ', '.max(1, round($product->datasheet->bytes / 1024)).' KB' : '' }})
                    </a>
                @endif
                @if ($product->manual)
                    <a href="{{ $product->manual->url(null, $admin) }}" @if ($product->manual->externalUrl()) target="_blank" rel="noopener" @else download @endif class="{{ $product->datasheet ? 'mt-1' : 'mt-4' }} flex min-h-11 items-center gap-2 font-semibold text-steel-900 underline" style="width: fit-content">
                        <x-icon name="file" class="size-5 text-gold-800" />
                        Download user manual (PDF{{ $product->manual->bytes ? ', '.max(1, round($product->manual->bytes / 1024)).' KB' : '' }})
                    </a>
                @endif
            </div>

            <div class="space-y-3 lg:col-start-1 lg:row-start-1">
                <div class="{{ $cover ? 'aspect-square' : 'aspect-[16/9] sm:aspect-[4/3] lg:aspect-square' }} overflow-hidden rounded-[var(--radius-panel)] border border-steel-200 bg-white">
                    @if ($cover)
                        <x-media-image :asset="$cover" :admin="$admin" sizes="(min-width: 1024px) 560px, 100vw" priority fit="contain" />
                    @else
                        <x-placeholder-image label="Product photo pending" :icon="$glyph" />
                    @endif
                </div>
                @if ($rest->isNotEmpty())
                    <ul class="grid grid-cols-4 gap-2" aria-label="More images">
                        @foreach ($rest as $img)
                            <li class="aspect-square overflow-hidden rounded border border-steel-200 bg-white"><x-media-image :asset="$img" :admin="$admin" sizes="140px" fit="contain" /></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="mt-12 grid gap-10 lg:grid-cols-2">
            <section aria-labelledby="specs-title">
                <h2 id="specs-title" class="text-xl font-bold text-steel-950">Specifications</h2>
                @if (! empty($product->specs))
                    <table class="mt-3 w-full text-left text-[0.95rem]">
                        <caption class="sr-only">Specifications for {{ $product->name }}</caption>
                        <tbody class="divide-y divide-steel-200 border-y border-steel-200">
                            @foreach ($product->specs as $s)
                                <tr><th scope="row" class="w-1/2 py-2.5 pr-4 font-semibold text-steel-800">{{ $s['label'] ?? '' }}</th><td class="py-2.5 text-steel-900 tabular">{{ $s['value'] ?? '' }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="mt-3 text-steel-600">Specifications to be confirmed. Ask us for the datasheet.</p>
                @endif
            </section>
            <section aria-labelledby="details-title" class="space-y-6">
                @if ($product->description)
                    <div>
                        <h2 id="details-title" class="text-xl font-bold text-steel-950">Details</h2>
                        <x-paragraphs :text="$product->description" class="mt-3 text-steel-700" />
                    </div>
                @endif
                <div>
                    <h2 class="text-xl font-bold text-steel-950">Warranty</h2>
                    <p class="mt-2 text-steel-700">{{ $product->warranty_terms ?: ($settings->general_warranty ?: 'Warranty terms to be confirmed.') }}</p>
                </div>
            </section>
        </div>
    </x-container>
</article>
