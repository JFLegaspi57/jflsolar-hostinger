@props(['pkg', 'settings', 'admin' => false])
@php
    use App\Support\Labels;
    use App\Support\Pricing;
    use App\Support\Site;
    use App\Support\Ui;
    $askHref = '/contact?package='.rawurlencode($pkg->slug).'#quote';
    $warranty = $pkg->warranty_terms ?: ($settings->general_warranty ?: 'Warranty terms to be confirmed.');
    $promo = $pkg->promo_state ?? $pkg->promoState();
    $build = \App\Support\PackageBuilder::lines($pkg);
    $buildTotal = $build ? \App\Support\PackageBuilder::defaultTotal($build) : 0;
    $maxQty = (int) config('shop.max_quantity', 500);
@endphp
{{-- Shared by the public page and the admin draft preview ($admin = staff-only media URLs). --}}
<article aria-labelledby="pkg-title">
    <div class="border-b border-steel-200 bg-steel-50">
        <x-container class="py-8 sm:py-10">
            <x-breadcrumbs :items="[['href' => '/', 'label' => 'Home'], ['href' => '/packages', 'label' => 'Packages'], ['label' => $pkg->name]]" />
            <div class="mt-6 flex flex-wrap items-center gap-2">
                <x-package-type-tag :type="$pkg->package_type" />
                @if ($pkg->system_type)<span class="text-sm font-semibold tracking-wide text-steel-600 uppercase">{{ Labels::SYSTEM_TYPE[$pkg->system_type] }}</span>@endif
                @if ($pkg->is_sample && ! Site::isProduction())<x-sample-badge label="Sample package" />@endif
            </div>
            <h1 id="pkg-title" class="mt-3 max-w-3xl text-3xl font-extrabold text-steel-950 sm:text-4xl">{{ $pkg->name }}</h1>
            <p class="mt-3 max-w-2xl text-lg text-steel-600">{{ $pkg->summary }}</p>
            <p class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-steel-800"><x-icon name="check" class="size-4 text-gold-800" /> {{ Labels::PACKAGE_TYPE_HINT[$pkg->package_type] }}</p>
        </x-container>
    </div>

    <x-container class="grid gap-10 py-10 lg:grid-cols-[1fr_22rem] lg:py-14">
        <aside aria-label="Price and inquiry" class="lg:sticky lg:top-28 lg:col-start-2 lg:row-start-1 lg:self-start">
            <div class="rounded-[var(--radius-panel)] border border-steel-300 bg-white p-6 shadow-sm">
                <x-price-block :price="$pkg->price($promo)" :promo="$promo" :promo-label="$pkg->promo_label" :promo-window="Pricing::promoWindow($pkg->promoStart(), $pkg->promoEnd())" size="lg" />
                @if ($build)
                    <a href="#build" class="{{ Ui::button('primary', 'lg', 'mt-6 w-full') }}"><x-icon name="cart" class="size-5" /> Customize &amp; Add to Basket</a>
                    <a href="{{ $askHref }}" class="{{ Ui::button('secondary', 'lg', 'mt-3 w-full') }}">Ask About This Package</a>
                @else
                    <a href="{{ $askHref }}" class="{{ Ui::button('primary', 'lg', 'mt-6 w-full') }}">Ask About This Package</a>
                @endif
                <a href="{{ Site::telHref($settings->phone_e164) }}" class="mt-3 flex min-h-11 items-center justify-center gap-2 font-semibold text-steel-900 hover:underline"><x-icon name="phone" class="size-4" /> Call {{ $settings->phone_display }}</a>
                @if ($settings->service_area_note)<p class="mt-4 border-t border-steel-200 pt-4 text-sm text-steel-600">{{ $settings->service_area_note }}</p>@endif
            </div>
        </aside>
        <div class="min-w-0 space-y-10 lg:col-start-1 lg:row-start-1">
            <section aria-labelledby="capacity-title">
                <h2 id="capacity-title" class="mb-3 text-xl font-bold text-steel-950">System capacity</h2>
                <x-spec-row :inverter="$pkg->inverter_kw" :array="$pkg->array_kwp" :battery="$pkg->battery_kwh" />
            </section>

            @if ($build)
                <section id="build" aria-labelledby="build-title" style="scroll-margin-top: 7rem" class="rounded-[var(--radius-panel)] border border-steel-300 bg-white p-5 sm:p-6">
                    <h2 id="build-title" class="text-xl font-bold text-steel-950">Build your package</h2>
                    <p class="mt-1 text-steel-700">Each part has its own price. Choose the options you want, change the quantities (set 0 to leave a part out), then add everything to your basket.</p>
                    @if (session('pkg_added'))
                        <div role="status" class="mt-4 rounded-[var(--radius-control)] border border-[#b7e2c6] bg-ok-50 p-3 text-ok-700">
                            <p class="flex items-center gap-2 font-semibold"><x-icon name="check" class="size-5" /> Added to your basket:</p>
                            <ul class="mt-1 text-sm">@foreach ((array) session('pkg_added') as $line)<li>{{ $line }}</li>@endforeach</ul>
                            <p class="mt-1 text-sm"><a href="/cart" class="font-semibold underline">View basket and check out</a></p>
                        </div>
                    @endif
                    @if (session('pkg_error'))
                        <p role="alert" class="mt-4 rounded-[var(--radius-control)] border border-[#f3c3be] bg-danger-50 p-3 font-semibold text-danger-700">{{ session('pkg_error') }}</p>
                    @endif
                    <form method="post" action="/packages/{{ $pkg->slug }}/basket" class="mt-4" data-pkg-builder>
                        @csrf
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-[0.95rem]">
                                <caption class="sr-only">Parts of {{ $pkg->name }} with their prices</caption>
                                <thead class="border-b border-steel-300 text-xs tracking-wide text-steel-600 uppercase">
                                    <tr><th scope="col" class="py-2 pr-4 font-semibold">Part</th><th scope="col" class="py-2 pr-4 text-right font-semibold">Unit price</th><th scope="col" class="py-2 pr-4 text-right font-semibold">Qty</th><th scope="col" class="py-2 text-right font-semibold">Amount</th></tr>
                                </thead>
                                <tbody class="divide-y divide-steel-200">
                                    @foreach ($build as $line)
                                        @php $first = $line['options'][0]; $i = $line['index']; @endphp
                                        <tr data-line>
                                            <td class="py-3 pr-4 align-top" style="min-width: 14rem">
                                                <span class="block font-semibold text-steel-950">{{ $line['label'] }}</span>
                                                @if (count($line['options']) > 1)
                                                    <label for="bp-{{ $i }}" class="sr-only">Choose the {{ mb_strtolower($line['label']) }}</label>
                                                    <select id="bp-{{ $i }}" name="product[{{ $i }}]" data-product class="mt-1 min-h-11 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-2.5 text-[0.95rem] text-steel-950">
                                                        @foreach ($line['options'] as $o)
                                                            <option value="{{ $o->id }}" data-price="{{ (float) $o->price_php }}" @selected(old('product.'.$i, $first->id) === $o->id)>{{ trim(($o->brand ? $o->brand.' ' : '').$o->name) }} — {{ Pricing::formatPhp($o->price_php) }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <input type="hidden" name="product[{{ $i }}]" value="{{ $first->id }}" data-product data-price="{{ (float) $first->price_php }}">
                                                    <a href="/products/{{ $first->slug }}" class="text-steel-700 underline">{{ trim(($first->brand ? $first->brand.' ' : '').$first->name) }}</a>
                                                @endif
                                            </td>
                                            <td class="py-3 pr-4 text-right align-top whitespace-nowrap tabular" data-unit>{{ Pricing::formatPhp($first->price_php) }}@if (\App\Support\Shop::perMetre($first))<span class="text-sm text-steel-600"> /m</span>@endif</td>
                                            <td class="py-3 pr-4 text-right align-top">
                                                <label for="bq-{{ $i }}" class="sr-only">Quantity of {{ mb_strtolower($line['label']) }}</label>
                                                <input id="bq-{{ $i }}" name="quantity[{{ $i }}]" type="number" inputmode="numeric" min="0" max="{{ $maxQty }}" step="1" value="{{ old('quantity.'.$i, $line['quantity']) }}" data-qty
                                                    style="width: 5rem" class="min-h-11 rounded-[var(--radius-control)] border border-steel-500 bg-white px-2.5 text-right text-base text-steel-950 tabular">
                                            </td>
                                            <td class="py-3 text-right align-top font-semibold whitespace-nowrap text-steel-950 tabular" data-amount>{{ Pricing::formatPhp((float) $first->price_php * $line['quantity']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-t border-steel-900" style="border-top-width: 2px">
                                        <th scope="row" colspan="3" class="py-3 pr-4 text-right text-lg font-bold text-steel-950">Total</th>
                                        <td class="py-3 text-right text-lg font-extrabold whitespace-nowrap text-steel-950 tabular" data-total>{{ Pricing::formatPhp($buildTotal) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <p class="mt-2 text-sm text-steel-600">Parts are priced at our current pickup prices and added to your basket as separate items. Accessories, installation and delivery are not included.</p>
                        <button type="submit" class="{{ Ui::button('primary', 'lg', 'mt-4 w-full sm:w-auto') }}"><x-icon name="cart" class="size-5" /> Add to Basket</button>
                    </form>
                    <script>
                        (() => {
                            const form = document.querySelector('[data-pkg-builder]');
                            if (!form) return;
                            const peso = (n) => '₱' + Math.round(n).toLocaleString('en-PH');
                            const update = () => {
                                let total = 0;
                                form.querySelectorAll('[data-line]').forEach((row) => {
                                    const pick = row.querySelector('[data-product]');
                                    const price = parseFloat(pick.tagName === 'SELECT' ? pick.selectedOptions[0].dataset.price : pick.dataset.price) || 0;
                                    const qty = Math.max(0, parseInt(row.querySelector('[data-qty]').value, 10) || 0);
                                    row.querySelector('[data-unit]').firstChild.textContent = peso(price);
                                    row.querySelector('[data-amount]').textContent = peso(price * qty);
                                    row.style.opacity = qty ? '' : '.55';
                                    total += price * qty;
                                });
                                form.querySelector('[data-total]').textContent = peso(total);
                            };
                            form.addEventListener('input', update);
                            form.addEventListener('change', update);
                            update();
                        })();
                    </script>
                </section>
            @endif

            @if ($pkg->images->isNotEmpty())
                <section aria-label="Package images" class="grid gap-3 sm:grid-cols-2">
                    @foreach ($pkg->images as $i => $img)
                        <figure class="overflow-hidden rounded-[var(--radius-panel)] border border-steel-200 {{ $i === 0 ? 'sm:col-span-2' : '' }}">
                            <div class="aspect-[16/10]"><x-media-image :asset="$img" :admin="$admin" sizes="(min-width: 1024px) 700px, 100vw" :priority="$i === 0" /></div>
                            <figcaption class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 px-3 py-2 text-sm text-steel-600">
                                <span>{{ $img->caption }}</span>
                                <a href="{{ $img->url(max($img->widths ?: [640]), $admin) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center font-semibold text-steel-900 underline">Open full-size image<span class="sr-only"> (opens in a new tab)</span></a>
                            </figcaption>
                        </figure>
                    @endforeach
                </section>
            @endif

            @if (! empty($pkg->components))
                <section aria-labelledby="components-title">
                    <h2 id="components-title" class="mb-3 text-xl font-bold text-steel-950">Components</h2>
                    <div class="overflow-hidden rounded-[var(--radius-panel)] border border-steel-200">
                        <table class="w-full text-left text-[0.95rem]">
                            <caption class="sr-only">Components in {{ $pkg->name }}</caption>
                            <thead class="bg-steel-50 text-xs tracking-wide text-steel-600 uppercase">
                                <tr><th scope="col" class="px-4 py-2.5 font-semibold">Component</th><th scope="col" class="px-4 py-2.5 font-semibold">Details</th><th scope="col" class="px-4 py-2.5 text-right font-semibold">Qty</th></tr>
                            </thead>
                            <tbody class="divide-y divide-steel-200">
                                @foreach ($pkg->components as $c)
                                    <tr>
                                        <th scope="row" class="px-4 py-3 font-semibold text-steel-900">{{ $c['label'] ?? '' }}</th>
                                        <td class="px-4 py-3 text-steel-700">{{ $c['detail'] ?? '' }}</td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap text-steel-700 tabular">{{ ($c['quantity'] ?? null) ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            <div class="grid gap-6 sm:grid-cols-2">
                @foreach ([['incl', 'Included', $pkg->inclusions ?? [], 'check', 'text-ok-700'], ['excl', 'Not included', $pkg->exclusions ?? [], 'x', 'text-danger-700']] as [$id, $label, $items, $icon, $tone])
                    <section aria-labelledby="{{ $id }}-title" class="rounded-[var(--radius-panel)] border border-steel-200 p-5">
                        <h2 id="{{ $id }}-title" class="text-lg font-bold text-steel-950">{{ $label }}</h2>
                        @if ($items)
                            <ul class="mt-3 space-y-2">
                                @foreach ($items as $x)
                                    <li class="flex items-start gap-2 text-steel-800"><x-icon :name="$icon" class="mt-1 size-4 {{ $tone }}" />{{ $x }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-3 text-steel-600">Confirmed in your quotation.</p>
                        @endif
                    </section>
                @endforeach
            </div>

            @if ($pkg->description)
                <section aria-labelledby="about-title">
                    <h2 id="about-title" class="mb-2 text-xl font-bold text-steel-950">About this package</h2>
                    <x-paragraphs :text="$pkg->description" class="max-w-2xl text-steel-700" />
                </section>
            @endif

            <dl class="grid gap-x-8 gap-y-5 border-t border-steel-200 pt-8 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-bold tracking-wide text-steel-950 uppercase">Installation</dt>
                    <dd class="mt-1 text-steel-700">{{ $pkg->installation_terms ?: 'Confirmed during quotation.' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-bold tracking-wide text-steel-950 uppercase">Pickup and delivery</dt>
                    <dd class="mt-1 text-steel-700">{{ Labels::FULFILLMENT[$pkg->fulfillment] }}@if ($pkg->fulfillment_notes)<span class="block text-sm text-steel-600">{{ $pkg->fulfillment_notes }}</span>@endif</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm font-bold tracking-wide text-steel-950 uppercase">Warranty</dt>
                    <dd class="mt-1 text-steel-700">{{ $warranty }}</dd>
                </div>
            </dl>
        </div>
    </x-container>
</article>
