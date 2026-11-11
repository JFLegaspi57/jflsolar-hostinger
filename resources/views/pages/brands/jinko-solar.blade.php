@php
    // Key figures of the Jinko Tiger Neo panels we carry (from their product pages / JinkoSolar datasheets).
    $panels = [
        // [model, power, efficiency, rear-side boost (BNPI), bifaciality, heat loss, Voc, Isc]
        ['JKM620N-66HL4M-BDV', '620 W', '22.95%', 'up to 685 W', '80%', '−0.29%/°C', '49.08 V', '16.08 A'],
        ['JKM650N-66QL6-BDV', '650 W', '24.06%', 'up to 724 W', '85%', '−0.26%/°C', '50.26 V', '15.98 A'],
    ];
@endphp
<x-brand-article :slug="$article['slug']" :brand="$article['brand']" :filter="$article['filter']" :products="$products" :settings="$settings"
    title="Jinko Solar panels"
    intro="Who JinkoSolar is, what makes its Tiger Neo N-type panels different, and how to choose and install them for Philippine roofs."
    description="Everything about JinkoSolar: company background, Tiger Neo N-type TOPCon bifacial panels, JinkoSolar in the Philippines, the 620 W and 650 W panels we carry, warranty and sizing."
    :toc="[
        'who' => 'Who is JinkoSolar?', 'makes' => 'What JinkoSolar makes', 'tech' => 'The technology, in plain words',
        'philippines' => 'JinkoSolar in the Philippines', 'tiger-neo' => 'The Tiger Neo panels we carry', 'size' => 'How many panels do I need?',
        'install' => 'Installing big panels', 'warranty' => 'Warranty',
    ]">
    <section aria-labelledby="who">
        <h2 id="who">Who is JinkoSolar?</h2>
        <p>JinkoSolar is one of the world’s largest solar panel manufacturers. It was founded in 2006 in Shangrao, Jiangxi province, China, where it is still headquartered, by Li Xiande (now chairman) and Li Xianhua. It listed on the New York Stock Exchange in 2010 (code JKS).</p>
        <p>Unlike many panel brands that buy their parts, JinkoSolar makes almost the whole chain itself: silicon ingots, wafers, solar cells and finished panels. That gives it close control over quality and cost.</p>
        <dl class="ba-facts mt-6">
            <div><dt>Founded</dt><dd>2006<small>Shangrao, Jiangxi, China</small></dd></div>
            <div><dt>Stock listing</dt><dd>New York Stock Exchange<small>since 2010, code JKS</small></dd></div>
            <div><dt>Panels shipped in 2025</dt><dd>86.8 GW<small>its seventh year as the top seller, by its own report</small></dd></div>
            <div><dt>Total shipped</dt><dd>Over 400 GW<small>by early 2026</small></dd></div>
            <div><dt>Factory capacity</dt><dd>130 GW of panels a year<small>plus 95 GW of cells and 120 GW of wafers (end of 2025)</small></dd></div>
            <div><dt>Independent testing</dt><dd>RETC “Overall Highest Achiever”<small>PV Module Index, seven years in a row (2026)</small></dd></div>
        </dl>
        <p class="mt-6">For scale: the 86.8 GW it shipped in 2025 is about 140 million panels of the 620 W size.</p>
        <x-brand-tier :slug="$article['slug']" />
    </section>

    <section aria-labelledby="makes">
        <h2 id="makes">What JinkoSolar makes</h2>
        <div class="ba-family">
            <h3>Tiger Neo solar panels</h3>
            <p>Its main product line: N-type TOPCon panels for homes, businesses and solar farms. JinkoSolar reports over 220 GW of Tiger Neo panels shipped, which it calls the best-selling solar panel in history. The current Tiger Neo 3.0 reaches up to 670 W and over 24.8% efficiency in mass production.</p>
        </div>
        <div class="ba-family">
            <h3>Energy storage</h3>
            <p>Battery storage systems for homes, businesses and the grid. BloombergNEF lists JinkoSolar as a Tier 1 energy storage manufacturer.</p>
        </div>
        <div class="ba-family">
            <h3>Research</h3>
            <p>Over 3,500 patents, including more than 700 for N-type TOPCon, and record lab cell efficiencies of 27.79% (TOPCon) and 34.76% (TOPCon-perovskite tandem).</p>
        </div>
    </section>

    <section aria-labelledby="tech">
        <h2 id="tech">The technology, in plain words</h2>
        <ul class="dots">
            <li><strong>N-type TOPCon cells.</strong> The newer cell type that has replaced older P-type PERC panels. It turns more sunlight into electricity, loses less power as it ages, and handles heat and low light (morning, late afternoon, cloudy days) better.</li>
            <li><strong>Bifacial, glass on both sides.</strong> The back of the panel also makes power from light reflected off the roof or ground, adding up to about 10% on the 620 W panel (its rear-side boosted rating is 685 W). Two layers of 2 mm glass also protect the cells better against moisture than a plastic backsheet.</li>
            <li><strong>Cut cells.</strong> The 620 W panel uses 132 half cells and the newer 650 W Tiger Neo 3.0 uses 264 quarter cells, instead of 66 full cells. Smaller cells carry less current, so less power is lost as heat, and shade on one part of the panel costs less output.</li>
            <li><strong>Heat tolerance.</strong> All panels lose some output when hot. The Tiger Neo loses only 0.29% per °C above 25 °C (0.26% on the 650 W Tiger Neo 3.0), compared with about 0.35% for older PERC panels. On a hot Philippine roof, where panels often reach 60 °C or more, that difference adds up every afternoon.</li>
            <li><strong>Strong frame.</strong> Certified for 5,400 Pa of load on the front and 2,400 Pa on the back, and resistant to PID (power loss caused by high system voltage).</li>
        </ul>
    </section>

    <section aria-labelledby="philippines">
        <h2 id="philippines">JinkoSolar in the Philippines</h2>
        <ul class="dots">
            <li>JinkoSolar has a long history here. It reported over 40% of the commercial and industrial rooftop solar market in the Philippines in 2021.</li>
            <li>In 2022, after Typhoon Odette (Rai), it donated 500 kWp of solar panels (1,080 panels) to the Philippine Red Cross, shipped to Cebu, to restore power to affected communities.</li>
            <li>In August 2023 it co-hosted a PV Technical Roadshow in the Philippines with GoodWe and Clenergy for installers and distributors.</li>
            <li>In July 2024 it signed a partnership in Manila with mounting maker MECASOLAR for utility-scale solar projects across Southeast Asia.</li>
        </ul>
    </section>

    <section aria-labelledby="tiger-neo">
        <h2 id="tiger-neo">The Tiger Neo panels we carry</h2>
        <p>Both are N-type TOPCon bifacial dual-glass panels of the same size, so they fit the same mounting. The 650 W is the newer Tiger Neo 3.0, with higher efficiency, a stronger back side (85% bifaciality) and better heat tolerance.</p>
        <div class="ba-table-wrap mt-4">
            <table class="ba-table">
                <caption class="sr-only">Key figures of the Jinko Tiger Neo panels we carry</caption>
                <thead><tr><th scope="col">Model</th><th scope="col">Power</th><th scope="col">Efficiency</th><th scope="col">With rear-side gain</th><th scope="col">Bifaciality</th><th scope="col">Heat loss</th><th scope="col">Voc</th><th scope="col">Isc</th></tr></thead>
                <tbody>
                    @foreach ($panels as $m)
                        <tr><th scope="row">{{ $m[0] }}</th>@foreach (array_slice($m, 1) as $v)<td>{{ $v }}</td>@endforeach</tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-2 text-sm text-steel-600">Both: 2382 × 1134 × 30 mm, about 32.5 kg, IP68 junction box, 1,500 V maximum system voltage, −40 to +70 °C. Voc (open-circuit voltage) and Isc (short-circuit current) are what we use to match the panels to your inverter.</p>
    </section>

    <section aria-labelledby="size">
        <h2 id="size">How many panels do I need?</h2>
        <p>Start from your monthly electricity use (the kWh on your bill). As a rough guide, one 620 W panel produces about 2 to 2.5 kWh on an average Philippine day, depending on your location, the season, roof direction and shade. So a home using 600 kWh a month (about 20 kWh a day) needs roughly 8 to 10 panels.</p>
        <p>The panels are then wired in “strings”. The number of panels per string must keep the voltage within your inverter’s limits, including on cool mornings when panel voltage rises. We work this out for your exact inverter. Try our <a href="/pv-calculator" class="inline">PV Calculator</a> for a first estimate.</p>
    </section>

    <section aria-labelledby="install">
        <h2 id="install">Installing big panels</h2>
        <p>At about 2.4 × 1.1 m and 32 kg, these are large panels. They need two people to carry, rails and clamps sized for the panel, and correct clamp positions (on the long side, within the zones in JinkoSolar’s manual) to keep the warranty and the wind rating. Leave a gap under the panels so the back side gets reflected light and air can cool them. Our <a href="/products?category=mounting-hardware#results" class="inline">mounting hardware</a> is matched to these panels.</p>
    </section>

    <section aria-labelledby="warranty">
        <h2 id="warranty">Warranty</h2>
        <ul class="dots">
            <li><strong>12-year product warranty</strong> against defects in materials and workmanship.</li>
            <li><strong>30-year linear power warranty.</strong> At most 1% loss in the first year, then at most 0.40% a year on the 620 W (at least 87.4% of rated power after 30 years) and 0.35% a year on the 650 W (at least 88.85%).</li>
            <li>We coordinate any warranty claim for you. See <a href="/faq" class="inline">how warranty claims work</a>.</li>
        </ul>
    </section>

    <x-slot:questions>
        <div class="ba-qa">
            <h3>Is a bigger panel always better?</h3>
            <p>Not always. Fewer, bigger panels mean less wiring and fewer clamps, but on a small or oddly shaped roof, smaller panels can fit more total power. We check your roof layout first.</p>
        </div>
        <div class="ba-qa">
            <h3>Can I mix 620 W and 650 W panels?</h3>
            <p>Not in the same string, because the weakest panel limits the whole string. They can go on separate strings (separate MPPT inputs on the inverter).</p>
        </div>
        <div class="ba-qa">
            <h3>Does the back side work on a metal roof?</h3>
            <p>Yes, but the gain is smaller than over a white roof or light-colored ground. Plan with the front-side rating and treat the rear-side gain as a bonus.</p>
        </div>
        <div class="ba-qa">
            <h3>Which inverters work with these panels?</h3>
            <p>Any inverter whose input voltage and current limits fit the panels, including the GoodWe, Sungrow, Luxpower and Solis inverters we sell. Read about <a href="/brands/goodwe" class="inline">GoodWe inverters</a>.</p>
        </div>
    </x-slot:questions>

    <x-slot:sources>JinkoSolar via
        <a href="https://www.pv-tech.org/industry-updates/jinkosolar-ships-86-8gw-of-modules-in-2025/" target="_blank" rel="noopener" class="inline">PV Tech, “JinkoSolar ships 86.8 GW of modules in 2025”</a> (2026);
        <a href="https://stockanalysis.com/stocks/jks/company/" target="_blank" rel="noopener" class="inline">company profile</a> (Stock Analysis);
        <a href="https://www.pv-tech.org/industry-updates/jinkosolar-receives-retc-overall-highest-achiever-award-for-seventh-consecutive-year/" target="_blank" rel="noopener" class="inline">RETC Overall Highest Achiever, seventh year</a> (PV Tech, 2026);
        JinkoSolar news: <a href="https://www.jinkosolar.com/en/site/newsdetail/1714" target="_blank" rel="noopener" class="inline">Philippine Red Cross donation</a> (2022) and
        <a href="https://www.jinkosolar.com/en/site/newsdetail/2203" target="_blank" rel="noopener" class="inline">MECASOLAR partnership</a> (2024);
        GoodWe, <a href="https://en.goodwe.com/goodwe-presents-tailored-solutions-in-the-philippines-at-pv-technical-roadshow-energizing-renewable-energy-advancements" target="_blank" rel="noopener" class="inline">Philippines PV Technical Roadshow</a> (2023); JinkoSolar datasheets for the JKM620N-66HL4M-BDV and JKM650N-66QL6-BDV (2026).</x-slot:sources>
</x-brand-article>
