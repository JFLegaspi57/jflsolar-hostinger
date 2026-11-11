@php
    // Key figures of the Sungrow MG RL hybrid inverters we carry (from their product pages / Sungrow datasheets).
    $models = [
        // [model, rated power, recommended max PV, max usable PV, MPPTs, battery current]
        ['MG6RL', '6 kW', '12 kWp', '9.6 kWp', '2', '135 A'],
        ['MG8RL', '8 kW', '16 kWp', '12.8 kWp', '3', '190 A'],
        ['MG10RL', '10 kW', '20 kWp', '16 kWp', '3', '220 A'],
        ['MG12RL', '12 kW', '24 kWp', '19.2 kWp', '3', '240 A'],
    ];
@endphp
<x-brand-article :slug="$article['slug']" :brand="$article['brand']" :filter="$article['filter']" :products="$products" :settings="$settings"
    title="Sungrow inverters"
    intro="Who Sungrow is, why it is one of the most trusted inverter makers in the world, its support network in the Philippines, and the MG RL hybrid inverters we carry."
    description="Everything about Sungrow: company background and rankings, inverter and battery storage products, Sungrow in the Philippines, the MG6RL to MG12RL single-phase hybrid inverters, features and sizing."
    :toc="[
        'who' => 'Who is Sungrow?', 'makes' => 'What Sungrow makes', 'philippines' => 'Sungrow in the Philippines',
        'mgrl' => 'The MG RL hybrid inverters', 'size' => 'Choosing a size', 'warranty' => 'Warranty and support',
    ]">
    <section aria-labelledby="who">
        <h2 id="who">Who is Sungrow?</h2>
        <p>Sungrow Power Supply Co., Ltd. is one of the world’s largest makers of solar inverters and battery storage systems. It was founded in 1997 in Hefei, Anhui province, China, by Cao Renxian, then a teacher at Hefei University of Technology, and it is still headquartered in Hefei. It has been listed on the Shenzhen Stock Exchange since 2011 (code 300274).</p>
        <p>Sungrow builds inverters for everything from homes to the largest solar farms and battery plants in the world.</p>
        <dl class="ba-facts mt-6">
            <div><dt>Founded</dt><dd>1997<small>Hefei, Anhui, China</small></dd></div>
            <div><dt>Inverters installed</dt><dd>Over 660 GW<small>worldwide (end of 2025)</small></dd></div>
            <div><dt>Shipped in 2025</dt><dd>143 GW of inverters<small>plus 43 GWh of battery storage</small></dd></div>
            <div><dt>Market rank</dt><dd>No. 1 in inverter shipments<small>S&amp;P Global, 2025</small></dd></div>
            <div><dt>Bankability</dt><dd>No. 1 most bankable inverter brand<small>BloombergNEF survey 2025, sixth time</small></dd></div>
            <div><dt>Team and service</dt><dd>7,600+ in research and development<small>520 service outlets worldwide</small></dd></div>
        </dl>
        <p class="mt-6"><strong>What “bankable” means:</strong> banks and investors that finance large solar projects are surveyed on which brands they trust to last and to honor their warranties. Being ranked first tells you the company is expected to be around for the life of your inverter.</p>
        <x-brand-tier :slug="$article['slug']" />
    </section>

    <section aria-labelledby="makes">
        <h2 id="makes">What Sungrow makes</h2>
        <div class="ba-family">
            <h3>Home hybrid inverters</h3>
            <p>The <strong>MG RL series</strong> for 48 V low-voltage batteries (the models we carry), and the <strong>SH RS series</strong> for Sungrow’s high-voltage SBR and SBH batteries.</p>
        </div>
        <div class="ba-family">
            <h3>Commercial and utility inverters</h3>
            <p>Three-phase inverters for businesses, commercial hybrids up to 125 kW, and central and modular inverters of several megawatts for solar farms.</p>
        </div>
        <div class="ba-family">
            <h3>Battery storage</h3>
            <p>Home batteries, and large systems such as PowerStack for businesses and PowerTitan containers for the grid.</p>
        </div>
        <div class="ba-family">
            <h3>Monitoring and EV charging</h3>
            <p>The <strong>iSolarCloud</strong> app shows your solar production, battery level and grid use on your phone. Sungrow also makes home and fast EV chargers.</p>
        </div>
    </section>

    <section aria-labelledby="philippines">
        <h2 id="philippines">Sungrow in the Philippines</h2>
        <ul class="dots">
            <li>Sungrow has been active in the Philippine market since <strong>2013</strong>.</li>
            <li>Sungrow inverters are distributed in the Philippines by <strong>Photonergy Philippines Inc.</strong></li>
            <li>It has distributors in <strong>Manila, Cebu and Davao</strong>, its own after-sales warehouse in <strong>Las Piñas</strong>, and spare units kept at each distributor hub, so a faulty unit can be swapped quickly.</li>
            <li>Sungrow calls the Philippines its largest battery storage market in Southeast Asia. In December 2024 it signed a 1.5 GWh battery storage deal with Citicore.</li>
            <li>In June 2026 it held the grand final of its <strong>Sungrow Master</strong> installer competition in Makati, after more than 2,000 installers across Southeast Asia took part.</li>
        </ul>
    </section>

    <section aria-labelledby="mgrl">
        <h2 id="mgrl">The MG RL hybrid inverters</h2>
        <p>Single-phase hybrid inverters for 48 V (40–60 V) batteries, launched in Southeast Asia in July 2025; the 12 kW model followed in June 2026.</p>
        <div class="ba-table-wrap mt-4">
            <table class="ba-table">
                <caption class="sr-only">Key figures of the Sungrow MG RL models</caption>
                <thead><tr><th scope="col">Model</th><th scope="col">Rated power</th><th scope="col">Recommended max. solar</th><th scope="col">Max. usable solar</th><th scope="col">MPPTs</th><th scope="col">Battery current</th></tr></thead>
                <tbody>
                    @foreach ($models as $m)
                        <tr><th scope="row">{{ $m[0] }}</th>@foreach (array_slice($m, 1) as $v)<td>{{ $v }}</td>@endforeach</tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-2 text-sm text-steel-600">“Max. usable solar” is the panel power the inverter can actually convert at once (160% of its rating); you can connect up to the recommended figure so it reaches full output for more hours of the day. Battery current is the most it can charge or draw from the battery.</p>
        <h3 class="mt-6">What the features mean for you</h3>
        <ul class="dots">
            <li><strong>Backup switching in 4 milliseconds or less.</strong> When the grid goes down, lights don’t flicker and computers stay on.</li>
            <li><strong>Strong surge power.</strong> Up to twice its rating for 10 seconds (170% on the 12 kW), to start motors such as water pumps and aircon compressors.</li>
            <li><strong>Multi-function port.</strong> Connect a generator, a “smart load” that only runs when there is surplus solar (such as a water heater), or an existing microinverter system.</li>
            <li><strong>Grow with parallel units.</strong> Up to 8 inverters can run together.</li>
            <li><strong>Battery choice.</strong> Works with Sungrow’s low-voltage batteries and compatible third-party 48 V lithium batteries; the 12 kW also supports lead-acid.</li>
            <li><strong>Built for heat.</strong> IP65 rated and delivers full power up to 45 °C, with arc-fault protection (AFCI) for fire safety.</li>
            <li><strong>97.6% maximum efficiency</strong>, and monitoring through the iSolarCloud app.</li>
        </ul>
    </section>

    <section aria-labelledby="size">
        <h2 id="size">Choosing a size</h2>
        <ul class="dots">
            <li><strong>Inverter:</strong> pick it for what you want running at the same time during a brownout: aircon, refrigerator, pump, lights. A typical home with one or two inverter aircons often fits the 6 or 8 kW model.</li>
            <li><strong>Panels:</strong> sized from your monthly kWh, up to the recommended maximum in the table.</li>
            <li><strong>Battery:</strong> sized from your night-time use and how long you want backup.</li>
        </ul>
        <p class="mt-3">Our <a href="/pv-calculator" class="inline">PV Calculator</a> gives a first estimate; send us your bill for an exact design.</p>
    </section>

    <section aria-labelledby="warranty">
        <h2 id="warranty">Warranty and support</h2>
        <ul class="dots">
            <li>Sungrow’s manufacturer warranty covers the MG RL inverters. The period that applies to your unit is stated on your quotation.</li>
            <li>With Sungrow’s own service warehouse in Las Piñas and spare units held by Philippine distributors, warranty replacements don’t have to come from China.</li>
            <li>We coordinate any warranty claim for you. See <a href="/faq" class="inline">how warranty claims work</a>.</li>
        </ul>
    </section>

    <x-slot:questions>
        <div class="ba-qa">
            <h3>Sungrow or GoodWe?</h3>
            <p>Both are top-tier brands with local support. Sungrow’s MG RL has three MPPTs from 8 kW up (useful for roofs facing different directions) and a multi-function port; GoodWe’s ES-C10 allows up to 200% solar oversizing. See our <a href="/brands/goodwe" class="inline">GoodWe guide</a>, or ask us to compare for your roof.</p>
        </div>
        <div class="ba-qa">
            <h3>Will I have power during a brownout?</h3>
            <p>Yes, with a battery. The backup loads switch over in 4 ms or less, and the panels keep charging the battery during the day.</p>
        </div>
        <div class="ba-qa">
            <h3>Can I join net metering?</h3>
            <p>Yes, a grid-connected hybrid system can apply. Read our <a href="/net-metering" class="inline">net metering guide</a>.</p>
        </div>
    </x-slot:questions>

    <x-slot:sources>
        <a href="https://en.wikipedia.org/wiki/Sungrow" target="_blank" rel="noopener" class="inline">Sungrow</a> (Wikipedia);
        <a href="https://taiyangnews.info/business/sungrow-fy-2025-financial-results" target="_blank" rel="noopener" class="inline">Sungrow FY 2025 results</a> (TaiyangNews, 2026);
        <a href="https://www.sungrowpower.com/eu/en/no1-spglobal-pvinverter-2025" target="_blank" rel="noopener" class="inline">No. 1 in S&amp;P Global inverter shipments</a> and
        <a href="https://www.sungrowpower.com/eu/en/sungrow-secures-top-rank-again-in-bloombergnef-bankability-survey" target="_blank" rel="noopener" class="inline">BloombergNEF bankability survey</a> (Sungrow, 2026);
        Power Philippines: <a href="https://powerphilippines.com/?p=14550" target="_blank" rel="noopener" class="inline">2023</a>,
        <a href="https://powerphilippines.com/?p=21724" target="_blank" rel="noopener" class="inline">distribution and service (2025)</a>,
        <a href="https://powerphilippines.com/?p=21167" target="_blank" rel="noopener" class="inline">MG RL launch (2025)</a>;
        <a href="https://www.pvtime.org/?p=22255" target="_blank" rel="noopener" class="inline">Citicore storage deal</a> (2024);
        <a href="https://www.sungrowpower.com/en/sungrow-master-2026-advances-installer-excellence-for-a-stronger-solar-industry-in-the-philippines" target="_blank" rel="noopener" class="inline">Sungrow Master 2026</a>;
        <a href="https://www.sungrowpower.com/en/sungrow-expands-residential-ess-portfolio-with-next-gen-solution-for-reliable-backup-power" target="_blank" rel="noopener" class="inline">MG12RL announcement</a> (2026);
        <a href="https://www.sungrowpower.com/me/en/products/residential-energy-storage-system/mgrl" target="_blank" rel="noopener" class="inline">MG RL product page</a>; Sungrow MG RL datasheets.</x-slot:sources>
</x-brand-article>
