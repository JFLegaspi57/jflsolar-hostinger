@php
    use App\Support\DeliveryRates;
    use App\Support\Site;
    use App\Support\Ui;
    $peso = fn (int $n) => DeliveryRates::peso($n);
    $from = $settings->warehouse_address ?: 'our warehouse in Dasmariñas, Cavite';
@endphp
<x-site-layout title="Delivery Rates" path="/delivery-rates" :reviews-above-footer="false" description="Estimated delivery fees for solar panels, inverters and batteries from the JFL Solar warehouse in Dasmariñas, Cavite to Cavite, Laguna, Batangas and Metro Manila, within about 100 km.">
    <x-slot:head>
        <style>
            .dr-finder { border: 1px solid var(--color-steel-200); border-radius: var(--radius-panel); background: var(--color-steel-50); padding: 1.25rem; }
            .dr-finder label { display: block; font-weight: 700; color: var(--color-steel-950); margin-bottom: .5rem; }
            .dr-finder select { width: 100%; max-width: 26rem; min-height: 2.9rem; border: 1px solid var(--color-steel-300); border-radius: var(--radius-control); background: #fff; padding: .5rem .75rem; font-size: 1rem; color: var(--color-steel-950); }
            .dr-finder select:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 2px; }
            .dr-result { margin-top: 1rem; display: grid; gap: .75rem; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); }
            .dr-result[hidden] { display: none; }
            .dr-fee { background: #fff; border: 1px solid var(--color-steel-200); border-radius: var(--radius-control); padding: .9rem 1rem; }
            .dr-fee .k { font-size: .85rem; color: var(--color-steel-600); }
            .dr-fee .v { font-size: 1.6rem; font-weight: 800; color: var(--color-steel-950); font-variant-numeric: tabular-nums; }
            .dr-fee .s { font-size: .85rem; color: var(--color-steel-700); }
            .dr-where { grid-column: 1 / -1; font-weight: 600; color: var(--color-steel-800); }
            .dr-veh { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr)); }
            .dr-veh > div { border: 1px solid var(--color-steel-200); border-radius: var(--radius-panel); padding: 1.1rem 1.2rem; }
            .dr-veh h3 { font-weight: 700; color: var(--color-steel-950); font-size: 1.05rem; }
            .dr-veh .tag { display: inline-block; font-size: .75rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--color-gold-800); margin-bottom: .25rem; }
            .dr-veh p { margin-top: .35rem; color: var(--color-steel-700); font-size: .95rem; }
            .dr-jump { display: flex; flex-wrap: wrap; gap: .5rem; }
            .dr-jump a { display: inline-flex; align-items: center; min-height: 2.5rem; padding: 0 .9rem; border: 1px solid var(--color-steel-300); border-radius: 999px; font-weight: 600; font-size: .9rem; color: var(--color-steel-900); text-decoration: none; }
            .dr-jump a:hover, .dr-jump a:focus-visible { border-color: var(--color-gold-500); background: var(--color-gold-50); }
            .dr-prov { scroll-margin-top: 6rem; }
            .dr-prov h2 { font-size: 1.4rem; font-weight: 800; color: var(--color-steel-950); }
            .dr-wrap { position: relative; overflow-x: auto; margin-top: .75rem; border: 1px solid var(--color-steel-200); border-radius: var(--radius-panel); }
            .dr-table { width: 100%; border-collapse: collapse; font-size: .95rem; }
            .dr-table th, .dr-table td { padding: .6rem .8rem; text-align: left; border-bottom: 1px solid var(--color-steel-100); }
            .dr-table thead th { background: var(--color-steel-50); font-size: .8rem; text-transform: uppercase; letter-spacing: .03em; color: var(--color-steel-600); vertical-align: bottom; white-space: normal; }
            .dr-table tbody tr:last-child td, .dr-table tbody tr:last-child th { border-bottom: 0; }
            .dr-table tbody th { font-weight: 600; color: var(--color-steel-950); }
            .dr-table .n { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
            .dr-table tr.is-picked { background: var(--color-gold-50); }
            .dr-notes li { position: relative; padding-left: 1.1rem; margin-top: .4rem; }
            .dr-notes li::before { content: ""; position: absolute; left: 0; top: .6em; width: .4rem; height: .4rem; border-radius: 50%; background: var(--color-gold-500); }
            @media (max-width: 479.98px) { .dr-table th, .dr-table td { padding: .5rem .45rem; font-size: .85rem; } }
        </style>
    </x-slot:head>
    <x-page-header eyebrow="Delivery" title="Delivery rates" intro="Estimated delivery fees from our warehouse in Dasmariñas, Cavite to towns and cities in Cavite, Laguna, Batangas and Metro Manila, within about {{ DeliveryRates::MAX_KM }} km." />
    <x-container class="py-10 sm:py-14">
        <div class="max-w-3xl space-y-10 text-steel-800" style="max-width: 56rem">
            <section class="dr-finder" aria-labelledby="dr-find">
                <label id="dr-find" for="dr-town">Where are we delivering?</label>
                <select id="dr-town">
                    <option value="">Choose your town or city</option>
                    @foreach ($areas as $province => $towns)
                        <optgroup label="{{ $province }}">
                            @foreach (collect($towns)->sortBy('town') as $t)
                                <option value="{{ $t['id'] }}" data-town="{{ $t['town'] }}, {{ $province }}" data-km="{{ $t['km'] }}" data-van="{{ $peso($t['van']) }}" data-long="{{ $peso($t['long_van']) }}">{{ $t['town'] }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <div class="dr-result" id="dr-result" aria-live="polite" hidden>
                    <p class="dr-where" id="dr-where"></p>
                    <div class="dr-fee"><div class="k">Inverter, battery and parts (no panels)</div><div class="v" id="dr-van"></div><div class="s">Small van, up to 1,000 kg</div></div>
                    <div class="dr-fee"><div class="k">Order with solar panels</div><div class="v" id="dr-long"></div><div class="s">Long van, up to 2,000 kg</div></div>
                </div>
                <p class="mt-3 text-sm text-steel-600">Estimated one-way fee. Expressway tolls are not included. We confirm the final fee in your quotation before we dispatch.</p>
            </section>

            <section aria-labelledby="dr-vehicles">
                <h2 id="dr-vehicles" class="text-2xl font-bold text-steel-950">Which rate applies to my order?</h2>
                <div class="dr-veh mt-4">
                    <div>
                        <span class="tag">Small van · up to 1,000 kg</span>
                        <h3>Inverter, battery and parts only</h3>
                        <p>For orders without solar panels: inverters, batteries, cables, breakers, mounting rails and other parts. The cargo space is about 2.1 m long.</p>
                        <p class="font-semibold text-steel-900">{{ $peso($range['van'][0]) }} to {{ $peso($range['van'][1]) }}</p>
                    </div>
                    <div>
                        <span class="tag">Long van · up to 2,000 kg</span>
                        <h3>Any order with solar panels</h3>
                        <p>Most of today’s solar panels are about 2.3 to 2.4 m long, too long for the small van, so they travel lying flat in a long van with a 3 m cargo space. It also fits a complete home solar PV system in one trip.</p>
                        <p class="font-semibold text-steel-900">{{ $peso($range['long_van'][0]) }} to {{ $peso($range['long_van'][1]) }}</p>
                    </div>
                </div>
            </section>

            <section aria-labelledby="dr-list" class="space-y-8">
                <div>
                    <h2 id="dr-list" class="text-2xl font-bold text-steel-950">Rates by town and city</h2>
                    <p class="mt-2">Distances are approximate road distances from our warehouse, nearest first.</p>
                    <nav aria-label="Provinces" class="dr-jump mt-4">
                        @foreach (array_keys($areas) as $province)
                            <a href="#{{ \Illuminate\Support\Str::slug($province) }}">{{ $province }}</a>
                        @endforeach
                    </nav>
                </div>
                @foreach ($areas as $province => $towns)
                    <div class="dr-prov" id="{{ \Illuminate\Support\Str::slug($province) }}">
                        <h2>{{ $province }}</h2>
                        <div class="dr-wrap">
                            <table class="dr-table">
                                <thead>
                                    <tr><th scope="col">Town or city</th><th scope="col" class="n">Distance</th><th scope="col" class="n">No panels <span class="sr-only">(small van)</span></th><th scope="col" class="n">With panels <span class="sr-only">(long van)</span></th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($towns as $t)
                                        <tr id="{{ $t['id'] }}"><th scope="row">{{ $t['town'] }}</th><td class="n">{{ $t['km'] }} km</td><td class="n">{{ $peso($t['van']) }}</td><td class="n">{{ $peso($t['long_van']) }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </section>

            <section aria-labelledby="dr-good" class="grid gap-6 md:grid-cols-2">
                <div>
                    <h2 id="dr-good" class="text-xl font-bold text-steel-950">Good to know</h2>
                    <ul class="dr-notes mt-2">
                        <li>Rates are estimates for one delivery from our warehouse to one address.</li>
                        <li>Expressway tolls, extra helpers, long waiting time and extra drop-off points are charged separately.</li>
                        <li>Courier rates can change with fuel prices and demand, so we confirm the final fee in your quotation before we dispatch.</li>
                        <li>Online shop orders are for pickup at our warehouse. Call our Sales team if you would like them delivered.</li>
                    </ul>
                </div>
                <div class="rounded-[var(--radius-panel)] border border-steel-200 bg-steel-50 p-6">
                    <h2 class="text-xl font-bold text-steel-950">Farther than {{ DeliveryRates::MAX_KM }} km?</h2>
                    <p class="mt-2 text-steel-700">We deliver beyond this list too. Tell us your town and what you are ordering, and we will quote the delivery fee.</p>
                    <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                        <a href="{{ Site::QUOTE_HREF }}" class="{{ Ui::button('primary', 'lg') }}">Request a Quote</a>
                        @if ($settings->phone_e164)
                            <a href="{{ Site::telHref($settings->phone_e164) }}" class="{{ Ui::button('secondary', 'lg') }}"><x-icon name="phone" class="size-4" /> Call {{ $settings->phone_display }}</a>
                        @endif
                    </div>
                </div>
            </section>

            <p class="border-t border-steel-200 pt-4 text-sm text-steel-600">Pickup point: {{ $from }}. Fees are based on courier truck rates checked {{ DeliveryRates::CHECKED }} and are rounded up to the nearest ₱{{ DeliveryRates::ROUND_TO }}.</p>
        </div>
    </x-container>
    <script>
        (function () {
            var sel = document.getElementById('dr-town'), box = document.getElementById('dr-result'), last = null;
            if (!sel) return;
            sel.addEventListener('change', function () {
                var o = sel.options[sel.selectedIndex];
                if (last) last.classList.remove('is-picked');
                if (!o || !o.value) { box.hidden = true; return; }
                document.getElementById('dr-where').textContent = o.dataset.town + ' · about ' + o.dataset.km + ' km from our warehouse';
                document.getElementById('dr-van').textContent = o.dataset.van;
                document.getElementById('dr-long').textContent = o.dataset.long;
                box.hidden = false;
                last = document.getElementById(o.value);
                if (last) last.classList.add('is-picked');
            });
        })();
    </script>
</x-site-layout>
