@php
    use App\Support\SolarCalculator as Calc;
    use App\Support\Site;
    use App\Support\Ui;
    $field = 'min-h-11 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3 text-base text-steel-900 focus:border-steel-900';
    $label = 'mb-1 block text-sm font-semibold text-steel-900';
    $hint = 'mt-1 text-sm text-steel-600';
    $legend = 'text-xl font-bold text-steel-950';
    $n = fn ($v, $dp = 1) => Calc::num((float) $v, $dp);
    $plain = fn ($x) => rtrim(rtrim(number_format((float) $x, 2, '.', ''), '0'), '.');
    $r = $result;
    $quoteHref = '/contact?'.http_build_query(array_merge(request()->query(), ['from' => 'calculator'])).'#quote';
@endphp
<x-site-layout title="PV System Calculator" path="/pv-calculator" description="Estimate the solar panels, inverter and battery your home or business needs from your monthly electricity bill or appliance list, then request a JFL Solar quotation.">
    <x-slot:head>
        <style>
            .calc-opt { display: block; cursor: pointer; border: 1px solid var(--color-steel-300); border-radius: var(--radius-panel); background: #fff; padding: 1rem; }
            .calc-opt:hover { border-color: var(--color-steel-900); }
            .calc-opt:has(input:checked) { border-color: var(--color-gold-600); background: var(--color-gold-50); box-shadow: inset 0 0 0 1px var(--color-gold-600); }
            .calc-opt:has(input:focus-visible) { outline: 3px solid var(--color-steel-900); outline-offset: 2px; }
            .calc-opt input { accent-color: var(--color-steel-900); }
            .calc-seg { display: inline-flex; border: 1px solid var(--color-steel-500); border-radius: var(--radius-control); overflow: hidden; }
            .calc-seg label { display: inline-flex; align-items: center; min-height: 2.75rem; padding: 0 1rem; cursor: pointer; font-weight: 600; color: var(--color-steel-800); }
            .calc-seg label + label { border-left: 1px solid var(--color-steel-500); }
            .calc-seg label:has(input:checked) { background: var(--color-steel-900); color: #fff; }
            .calc-seg label:has(input:focus-visible) { outline: 3px solid var(--color-gold-500); outline-offset: -3px; }
            .calc-seg input { position: absolute; opacity: 0; width: 1px; height: 1px; }
            .calc-table input { min-height: 2.5rem; width: 4.5rem; border: 1px solid var(--color-steel-400); border-radius: var(--radius-control); padding: 0 .5rem; font-variant-numeric: tabular-nums; }
            .calc-table input.calc-name { width: 11rem; }
            .calc-table th, .calc-table td { padding: .4rem .5rem; text-align: left; vertical-align: middle; }
            .js [data-mode-panel][hidden] { display: none; }
            .calc-grid { grid-template-columns: minmax(0, 1fr); }
            .calc-grid fieldset { min-width: 0; }
            @media (min-width: 1024px) { .calc-grid { grid-template-columns: minmax(0, 1fr) 20rem; } }
            @media print { header, footer, .calc-noprint { display: none !important; } }
        </style>
    </x-slot:head>

    <x-page-hero eyebrow="PV System Calculator" title="How much solar do you need?"
        intro="Start from your monthly electricity bill or your list of appliances. We'll estimate the panels, inverter and battery for your home or business, and how much you could save."
        image="/images/calculator-crew-1280.jpg" srcset="/images/calculator-crew-640.jpg 640w, /images/calculator-crew-960.jpg 960w, /images/calculator-crew-1280.jpg 1280w"
        imageAlt="Two JFL Solar technicians in hard hats installing solar panels on a metal rooftop at sunset" focus="55% 45%" :flip="true" />

    <x-section labelledby="calc-title">
        <h2 id="calc-title" class="sr-only">Calculator</h2>
        <form action="/pv-calculator#results" method="get" class="calc-grid grid gap-10" data-calc-form>
            <div class="min-w-0 space-y-10">
                {{-- 1. System type --}}
                <fieldset>
                    <legend class="{{ $legend }}">1. Choose a system type</legend>
                    <p class="{{ $hint }}">Not sure? Hybrid suits most homes that want backup during brownouts.</p>
                    <div class="mt-4 grid gap-3 md:grid-cols-3">
                        @foreach (Calc::TYPES as $key => $t)
                            <label class="calc-opt">
                                <span class="flex items-center gap-2 font-bold text-steel-950"><input type="radio" name="type" value="{{ $key }}" @checked($in['type'] === $key) class="size-5"> {{ $t['label'] }}</span>
                                <span class="mt-2 block text-sm text-steel-600">{{ $t['body'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                {{-- 2. Usage --}}
                <fieldset>
                    <legend class="{{ $legend }}">2. Tell us how much electricity you use</legend>
                    <div class="mt-4 calc-seg" role="radiogroup" aria-label="Estimate from">
                        <label><input type="radio" name="mode" value="bill" @checked($in['mode'] === 'bill') data-mode>My monthly bill</label>
                        <label><input type="radio" name="mode" value="appliances" @checked($in['mode'] === 'appliances') data-mode>My appliances</label>
                    </div>

                    <div data-mode-panel="bill" class="mt-6 space-y-6">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="bill" class="{{ $label }}">Average monthly bill (₱)</label>
                                <input id="bill" name="bill" type="number" inputmode="decimal" min="0" step="any" value="{{ $plain($in['bill']) }}" class="{{ $field }} tabular" aria-describedby="bill-hint">
                                <p id="bill-hint" class="{{ $hint }}">The total on your Meralco or electric cooperative bill.</p>
                            </div>
                            <div>
                                <label for="rate" class="{{ $label }}">Electricity rate (₱ per kWh)</label>
                                <input id="rate" name="rate" type="number" inputmode="decimal" min="1" max="50" step="any" value="{{ $plain($in['rate']) }}" class="{{ $field }} tabular" aria-describedby="rate-hint">
                                <p id="rate-hint" class="{{ $hint }}">Bill total ÷ kWh used. Both are printed on your bill.</p>
                            </div>
                        </div>
                        <fieldset>
                            <legend class="{{ $label }}">When do you use the most electricity?</legend>
                            <p class="{{ $hint }}">Daytime is about 6 AM to 6 PM. Solar powers daytime use directly; evening use comes from a battery or the grid.</p>
                            <div class="mt-3 grid gap-3 md:grid-cols-3">
                                @foreach (Calc::PROFILES as $pct => $p)
                                    <label class="calc-opt">
                                        <span class="flex items-center gap-2 font-semibold text-steel-950"><input type="radio" name="day" value="{{ $pct }}" @checked($in['day'] === $pct) class="size-5"> {{ $p['label'] }}</span>
                                        <span class="mt-1 block text-sm text-steel-600"><span class="tabular">{{ $pct }}% day · {{ 100 - $pct }}% night.</span> {{ $p['body'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    </div>

                    <div data-mode-panel="appliances" class="mt-6">
                        <p class="text-steel-700">Enter how many of each you have and the hours each one runs in the <strong>day</strong> (6 AM – 6 PM) and at <strong>night</strong>. Leave the quantity at 0 for things you don't have. Watts are typical values — check the label on your appliance if you know it.</p>
                        <div class="mt-4 overflow-x-auto rounded-[var(--radius-panel)] border border-steel-200">
                            <table class="calc-table w-full text-sm">
                                <caption class="sr-only">Appliance list</caption>
                                <thead class="bg-steel-50 text-steel-700">
                                    <tr><th scope="col">Appliance</th><th scope="col">Watts</th><th scope="col">Qty</th><th scope="col">Day hrs</th><th scope="col">Night hrs</th></tr>
                                </thead>
                                <tbody class="divide-y divide-steel-200">
                                    @foreach ($in['rows'] as $i => $row)
                                        @php $id = 'a'.$i; $rowName = $row['custom'] ? 'Other appliance '.($i - count(Calc::APPLIANCES) + 1) : $row['name']; @endphp
                                        <tr>
                                            <th scope="row" class="font-semibold text-steel-900">
                                                @if ($row['custom'])
                                                    <label for="{{ $id }}-name" class="sr-only">{{ $rowName }} name</label>
                                                    <input id="{{ $id }}-name" name="a[{{ $i }}][name]" type="text" maxlength="40" value="{{ $row['name'] }}" placeholder="Other appliance" class="calc-name font-normal">
                                                @else
                                                    {{ $row['name'] }}
                                                @endif
                                            </th>
                                            @foreach (['w' => ['Watts', 10000, 1], 'q' => ['Quantity', 50, 1], 'd' => ['Day hours', 12, 'any'], 'n' => ['Night hours', 12, 'any']] as $k => [$what, $max, $step])
                                                <td><input name="a[{{ $i }}][{{ $k }}]" type="number" inputmode="decimal" min="0" max="{{ $max }}" step="{{ $step }}" value="{{ $plain($row[$k]) }}" aria-label="{{ $rowName }}: {{ strtolower($what) }}"></td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="{{ $hint }}">Fridges cycle on and off, so their watts here are an average. Aircon hours count the time it is switched on.</p>
                    </div>
                </fieldset>

                {{-- 3. Advanced --}}
                <details class="rounded-[var(--radius-panel)] border border-steel-200 bg-steel-50 p-4" @if (request()->hasAny(['sun', 'eff', 'panel', 'unit', 'export', 'days']) && collect(['sun', 'eff', 'panel', 'unit', 'export', 'days'])->contains(fn ($k) => (float) $in[$k] !== (float) Calc::DEFAULTS[$k])) open @endif>
                    <summary class="inline-flex min-h-11 cursor-pointer items-center font-semibold text-steel-900">Advanced settings</summary>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        @foreach ([
                            'sun' => ['Peak sun hours per day', '4 to 5 hours is typical across the Philippines.', 'any'],
                            'eff' => ['System efficiency (%)', 'Covers heat, dust, wiring and inverter losses.', 1],
                            'panel' => ['Panel size (watts)', 'We usually supply 610–630 W panels.', 1],
                            'unit' => ['Battery module size (kWh)', 'Batteries are added one module at a time.', 'any'],
                            'export' => ['Export credit (₱ per kWh)', 'What the utility credits for solar sent back under net metering.', 'any'],
                            'days' => ['Off-grid backup (days)', 'How many days of full use the off-grid battery should hold.', 'any'],
                        ] as $k => [$text, $help, $step])
                            <div>
                                <label for="f-{{ $k }}" class="{{ $label }}">{{ $text }}</label>
                                <input id="f-{{ $k }}" name="{{ $k }}" type="number" inputmode="decimal" step="{{ $step }}" value="{{ $plain($in[$k]) }}" class="{{ $field }} tabular" aria-describedby="f-{{ $k }}-hint">
                                <p id="f-{{ $k }}-hint" class="{{ $hint }}">{{ $help }}</p>
                            </div>
                        @endforeach
                    </div>
                </details>

                <div class="calc-noprint flex flex-wrap items-center gap-3">
                    <button type="submit" class="{{ Ui::button('primary', 'lg') }}">Calculate</button>
                    @if ($submitted)<a href="/pv-calculator" class="{{ Ui::button('secondary', 'lg') }}">Start over</a>@endif
                </div>
            </div>

            {{-- Results --}}
            <aside id="results" aria-labelledby="results-title" aria-live="polite" class="calc-results self-start rounded-[var(--radius-panel)] bg-steel-950 p-6 text-white on-dark">
                <p class="text-sm font-semibold tracking-wide text-gold-400 uppercase">Your estimate</p>
                @if (! $submitted)
                    <h2 id="results-title" class="mt-2 text-2xl font-extrabold">Your results appear here</h2>
                    <p class="mt-3 text-steel-300">Fill in steps 1 and 2, then select <strong class="text-white">Calculate</strong>.</p>
                @elseif (! $r)
                    <h2 id="results-title" class="mt-2 text-2xl font-extrabold">We need a bit more</h2>
                    <p class="mt-3 text-steel-300">Enter your monthly bill, or at least one appliance with a quantity and hours, then select Calculate.</p>
                @else
                    <h2 id="results-title" class="mt-2 text-3xl font-extrabold"><span class="tabular">{{ $n($r['kwp'], 2) }} kWp</span> {{ Calc::TYPES[$in['type']]['short'] }} system</h2>
                    <p class="mt-2 text-sm text-steel-300">For about <span class="tabular">{{ $n($r['daily']) }} kWh</span> a day (<span class="tabular">{{ $n($r['monthly'], 0) }} kWh</span> a month){{ $in['mode'] === 'appliances' ? ' from your appliance list' : '' }}.</p>

                    <dl class="mt-6 space-y-4 border-t border-white/15 pt-5">
                        @php
                            $rows = [
                                ['Solar panels', $r['panels'].' × '.$r['panel'].' W', 'About '.$n($r['area'], 0).' m² of roof'],
                                ['Inverter', $r['inverter']['label'], $in['type'] === 'grid' ? 'Grid-tied' : 'Hybrid, with backup output'],
                            ];
                            if ($r['battery'] > 0) {
                                $rows[] = ['Battery', $n($r['battery']).' kWh', ($r['units'] > 1 ? $r['units'].' × '.$n($r['unit']).' kWh · ' : '').($r['backup'] ? 'about '.$n(min($r['backup'], 99), 0).' hrs of typical evening use' : '')];
                            }
                            $rows[] = ['Solar production', $n($r['output'], 0).' kWh / month', 'Estimated average'];
                        @endphp
                        @foreach ($rows as [$k, $v, $sub])
                            <div>
                                <dt class="text-xs font-semibold tracking-wide text-steel-300 uppercase">{{ $k }}</dt>
                                <dd class="mt-0.5 text-xl font-bold tabular">{{ $v }}</dd>
                                @if ($sub)<dd class="text-sm text-steel-300">{{ $sub }}</dd>@endif
                            </div>
                        @endforeach
                    </dl>

                    @if ($r['bill'] > 0)
                        <div class="mt-6 rounded-[var(--radius-control)] bg-white/5 border border-white/10 p-4">
                            <p class="text-xs font-semibold tracking-wide text-steel-300 uppercase">Estimated savings</p>
                            <p class="mt-1 text-2xl font-extrabold text-gold-400 tabular">₱{{ number_format($r['savings']) }} <span class="text-base font-semibold text-white">/ month</span></p>
                            <p class="mt-1 text-sm text-steel-300"><span class="tabular">₱{{ number_format($r['savings'] * 12) }}</span> a year · covers about <span class="tabular">{{ round(($r['offset'] ?? 0) * 100) }}%</span> of your bill</p>
                        </div>
                    @endif

                    <div class="mt-4 rounded-[var(--radius-control)] bg-white/5 border border-white/10 p-4">
                        <p class="text-xs font-semibold tracking-wide text-steel-300 uppercase">Indicative price, installed</p>
                        @if ($price)
                            <p class="mt-1 text-2xl font-extrabold text-white tabular">₱{{ number_format($price['low']) }} – ₱{{ number_format($price['high']) }}</p>
                            @if ($price['payback'])
                                <p class="mt-1 text-sm text-steel-300">Pays for itself in about <span class="tabular">{{ $n($price['payback'][0]) }}–{{ $n($price['payback'][1]) }}</span> years</p>
                            @endif
                            @if ($price['extraBattery'])
                                <p class="mt-2 text-sm text-gold-400">Covers the panels, inverter and {{ $n($r['battery'] - $price['extraBattery']) }} kWh of battery. The extra {{ $n($price['extraBattery']) }} kWh of battery is priced on quotation.</p>
                            @endif
                            <p class="mt-2 text-sm text-steel-300">Based on our listed {{ strtolower(Calc::TYPES[$in['type']]['label']) }} packages. Closest: <a href="/packages/{{ $price['closest']->slug }}" class="font-semibold text-white underline">{{ $price['closest']->name }}</a></p>
                        @else
                            <p class="mt-1 text-lg font-bold text-white">Price on quotation</p>
                            <p class="mt-1 text-sm text-steel-300">We don't have a listed {{ Calc::TYPES[$in['type']]['short'] }} package close to this size. Talk to us for a price.</p>
                        @endif
                    </div>

                    <div class="calc-noprint mt-6 space-y-3">
                        <a href="{{ $quoteHref }}" class="{{ Ui::button('primary', 'md', 'w-full') }}">Talk to Us About This</a>
                        <button type="button" data-print hidden class="w-full text-center text-sm font-semibold text-white underline">Print this estimate</button>
                    </div>
                    <p class="mt-4 text-xs text-steel-300">A planning estimate, not an offer. Your quotation follows a site assessment.</p>
                @endif
            </aside>
        </form>
    </x-section>

    <x-section tone="muted" labelledby="method-title">
        <x-section-heading id="method-title" eyebrow="How it works" title="How we estimate your system" intro="A first-pass sizing, the same way we start before a site visit. You can change the assumptions under Advanced settings." />
        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['Daily energy use', 'From your bill: bill ÷ rate ÷ 30 days, split into day and night use. From appliances: watts × quantity × hours, added up for day and night.'],
                ['Panels', 'Energy needed ÷ (peak sun hours × efficiency) gives the array size, rounded up to whole panels. Battery systems add enough to recharge the battery; off-grid adds 25% for cloudy days.'],
                ['Inverter', 'The smallest standard size that matches the panels. If you listed appliances, it must also carry about 80% of them running at once.'],
                ['Battery', 'Hybrid batteries cover your evening use. Off-grid batteries hold your full daily use for the number of days you choose. Usable capacity is taken as 90%.'],
                ['Indicative price', 'Scaled from the installed prices of our listed packages of the same system type (price per kWp × your system size). Roof type, wiring distance and site conditions can move the final price either way.'],
                ['Savings', 'Solar first powers what you use during the day. Hybrid systems store the extra for the evening. With grid-tied and hybrid systems, any excess is exported and credited at the lower net-metering rate.'],
                ['What it doesn\'t include', 'Roof direction, shading, wiring distance and local weather. These are checked during the site assessment, and the final design and price come with your quotation.'],
            ] as [$title, $body])
                <section class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6">
                    <h3 class="text-lg font-bold text-steel-950">{{ $title }}</h3>
                    <p class="mt-2 text-steel-700">{{ $body }}</p>
                </section>
            @endforeach
        </div>
        <p class="mt-8 max-w-3xl text-sm text-steel-600">Results are estimates for planning only and are not a binding offer. Actual production depends on roof orientation, shading, weather and the equipment chosen.</p>
    </x-section>

    <script>
        (function () {
            var form = document.querySelector('[data-calc-form]');
            if (!form) return;
            document.documentElement.classList.add('js');
            var panels = form.querySelectorAll('[data-mode-panel]');
            function sync() {
                var mode = (form.querySelector('[data-mode]:checked') || {}).value || 'bill';
                panels.forEach(function (p) { p.hidden = p.getAttribute('data-mode-panel') !== mode; });
            }
            form.querySelectorAll('[data-mode]').forEach(function (r) { r.addEventListener('change', sync); });
            sync();
            // Send only the section in use, so the results link stays short.
            form.addEventListener('submit', function () {
                panels.forEach(function (p) {
                    if (p.hidden) p.querySelectorAll('input').forEach(function (i) { i.disabled = true; });
                });
            });
            window.addEventListener('pageshow', function () { form.querySelectorAll('input:disabled').forEach(function (i) { i.disabled = false; }); });
            var print = document.querySelector('[data-print]');
            if (print) { print.hidden = false; print.addEventListener('click', function () { window.print(); }); }
        })();
    </script>
</x-site-layout>
