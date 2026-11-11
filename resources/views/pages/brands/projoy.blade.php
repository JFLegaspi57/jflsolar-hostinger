@php
    // Key figures of the Projoy rapid shutdown devices we carry (from their product pages / Projoy datasheets).
    $models = [
        // [model, strings, poles, current per string (up to 1000 V), protection]
        ['PEFS-EL40H-4-P2', '2', '4', '40 A', 'IP66'],
        ['PEFS-EL50H-8-P2', '4', '8', '50 A', 'IP65'],
        ['PEFS-EL50H-16-P2', '8', '16', '50 A', 'IP65'],
    ];
@endphp
<x-brand-article :slug="$article['slug']" :brand="$article['brand']" :filter="$article['filter']" :products="$products" :settings="$settings"
    title="Projoy rapid shutdown and protection"
    intro="Who Projoy Electric is, what a rapid shutdown device does for rooftop fire safety, and how the Projoy safety switches and surge protectors we carry work."
    description="Everything about Projoy Electric: company background, PEFS rapid shutdown (firefighter safety switch) devices, how rapid shutdown works and why it matters, AC surge protection, Projoy in the Philippines and warranty."
    :cta="'Making your solar PV system safer?'"
    :toc="[
        'who' => 'Who is Projoy?', 'why' => 'Why rapid shutdown matters', 'how' => 'How the Projoy switch works',
        'rsd' => 'The rapid shutdown devices we carry', 'spd' => 'AC surge protection', 'philippines' => 'Projoy in the Philippines', 'warranty' => 'Warranty',
    ]">
    <section aria-labelledby="who">
        <h2 id="who">Who is Projoy?</h2>
        <p>Projoy Electric (Jiangsu Projoy Electric Co., Ltd.) is a Chinese maker of electrical safety equipment for solar and battery systems, founded in 2011 in Suzhou. It makes the switches and protection devices that sit between the panels, the inverter and the house.</p>
        <p>Projoy says it supplies DC isolators to major inverter makers, naming GoodWe, Growatt and Solis among its customers, and that its products are exported to more than 60 countries.</p>
        <dl class="ba-facts mt-6">
            <div><dt>Founded</dt><dd>2011<small>Suzhou, China</small></dd></div>
            <div><dt>Exports</dt><dd>60+ countries<small>overseas sales since 2013</small></dd></div>
            <div><dt>Certifications</dt><dd>UL, TÜV, CE, CB<small>ISO 9001 factory</small></dd></div>
            <div><dt>Research</dt><dd>100+ engineers<small>16+ production lines</small></dd></div>
        </dl>
        <h3 class="mt-6">What Projoy makes</h3>
        <ul class="dots">
            <li><strong>Rapid shutdown devices</strong>: string-level “firefighter safety switches” (PEFS-EL, the ones we carry) and module-level devices.</li>
            <li><strong>DC isolators and DC breakers</strong> for solar strings up to 1,500 V.</li>
            <li><strong>Surge protective devices (SPDs)</strong> for the AC and DC sides.</li>
            <li><strong>Combiner and distribution boxes</strong>.</li>
        </ul>
    </section>

    <section aria-labelledby="why">
        <h2 id="why">Why rapid shutdown matters</h2>
        <p>Solar panels produce electricity whenever light falls on them. Switching off the main breaker or the inverter <strong>does not</strong> switch off the panels: the cables from the roof to the inverter can still carry several hundred volts DC. In a fire, that is a danger to firefighters cutting through the roof or spraying water, and it can keep an arc burning.</p>
        <p>A <strong>rapid shutdown device (RSD)</strong> cuts the panels off from the cables going into the house, so the long DC runs are no longer live. In the United States, the National Electrical Code (NEC 690.12) has required rapid shutdown on rooftop systems since 2017. In the Philippines, the Department of Trade and Industry proposed in May 2026 that rapid shutdown devices, like inverters and panels, carry the Philippine Standard (PS) safety mark.</p>
    </section>

    <section aria-labelledby="how">
        <h2 id="how">How the Projoy switch works</h2>
        <p>The PEFS-EL is a <strong>string-level</strong> switch: one box near the panels disconnects whole strings (rows of panels) from the cable to the inverter. It switches off automatically when:</p>
        <ul class="dots mt-3">
            <li><strong>The AC power goes off</strong> for more than about 5 seconds, for example when firefighters or you switch off the main breaker. It is powered from the house’s AC supply, so no extra wiring or network is needed.</li>
            <li><strong>The temperature inside the box reaches 70 °C</strong>, as in a fire.</li>
            <li><strong>Someone presses the emergency button.</strong></li>
        </ul>
        <p class="mt-3">The contacts open in under 5 milliseconds and the switch turns back on by itself when AC power returns. Feedback contacts can report its status to a monitoring system. It is rated for up to 1,500 V DC and 10,000 mechanical operations.</p>
    </section>

    <section aria-labelledby="rsd">
        <h2 id="rsd">The rapid shutdown devices we carry</h2>
        <div class="ba-table-wrap mt-2">
            <table class="ba-table">
                <caption class="sr-only">Key figures of the Projoy rapid shutdown devices</caption>
                <thead><tr><th scope="col">Model</th><th scope="col">Strings</th><th scope="col">Poles</th><th scope="col">Current per string</th><th scope="col">Protection</th></tr></thead>
                <tbody>
                    @foreach ($models as $m)
                        <tr><th scope="row">{{ $m[0] }}</th>@foreach (array_slice($m, 1) as $v)<td>{{ $v }}</td>@endforeach</tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-2 text-sm text-steel-600">Reading the model code: PEFS = firefighter safety switch; EL = string-level enclosure; 40H / 50H = current rating; the next number is the number of poles (two per string); P2 = second-generation plastic enclosure. Current ratings fall at higher system voltages (for example 30 A per string at 1,500 V on the 50H models).</p>
        <p class="mt-3"><strong>Which one?</strong> Count the strings going to your inverter. A home inverter with two MPPTs and one string each needs the 2-string unit; larger hybrids with several strings, or commercial systems, need the 4- or 8-string unit.</p>
    </section>

    <section aria-labelledby="spd">
        <h2 id="spd">AC surge protection</h2>
        <p>A <strong>surge protective device (SPD)</strong> protects your inverter and appliances from voltage spikes caused by lightning and switching on the grid, which are common in the Philippine rainy season. The Projoy 2-pole AC SPD mounts on a 35 mm DIN rail in your AC panel, between the line conductors and earth. It only works with a good earthing (grounding) connection, so it should be installed by a licensed electrician.</p>
    </section>

    <section aria-labelledby="philippines">
        <h2 id="philippines">Projoy in the Philippines</h2>
        <ul class="dots">
            <li>Projoy has exhibited at Solar &amp; Storage Live Philippines at the SMX Convention Center in Manila, showing its rapid shutdown devices, isolators, breakers and surge protectors.</li>
            <li>In May 2025 it held a product launch roadshow in the Philippines for its string-level and module-level rapid shutdown devices.</li>
        </ul>
    </section>

    <section aria-labelledby="warranty">
        <h2 id="warranty">Warranty</h2>
        <ul class="dots">
            <li><strong>5-year warranty</strong> (60 months from delivery or installation) on the rapid shutdown devices, against manufacturing defects, as long as the seals are intact.</li>
            <li>We coordinate any warranty claim for you. See <a href="/faq" class="inline">how warranty claims work</a>.</li>
        </ul>
    </section>

    <x-slot:questions>
        <div class="ba-qa">
            <h3>Does a rapid shutdown device reduce my solar output?</h3>
            <p>No. In normal operation the switch is closed and the panels work as usual. It only opens when the AC power is off, it overheats, or the button is pressed.</p>
        </div>
        <div class="ba-qa">
            <h3>Can it be added to an existing system?</h3>
            <p>Yes. The string-level switch goes into the DC cables between the panels and the inverter, so it can be retrofitted to most systems.</p>
        </div>
        <div class="ba-qa">
            <h3>Will my system restart by itself after a brownout?</h3>
            <p>Yes. When AC power returns, the switch closes automatically and the inverter starts again.</p>
        </div>
    </x-slot:questions>

    <x-slot:sources>
        <a href="https://www.projoy-electric.com/Companys.html" target="_blank" rel="noopener" class="inline">Projoy company page</a>;
        <a href="https://ar.enfsolar.com/news/17898/projoy-electric-s-rich-product-line-improve-production-capacity" target="_blank" rel="noopener" class="inline">Projoy product line and capacity</a> (ENF, 2021);
        <a href="https://www.pvtime.org/?p=10837" target="_blank" rel="noopener" class="inline">PEFS firefighter safety switch</a> (pvtime, 2021);
        <a href="https://taiyangnews.info/technology/projoy-electric-dc-safety-snec-2025" target="_blank" rel="noopener" class="inline">Projoy DC safety products</a> (TaiyangNews, 2025);
        <a href="https://solar-distribution.baywa-re.pl/out/media/2_-_PROJOY_3-5_strings_EN_PROJOY_manual.pdf" target="_blank" rel="noopener" class="inline">PEFS-EL manual</a> (2022);
        <a href="https://iaeimagazine.org/electrical-fundamentals/nec-rapid-shutdown-requirements-and-ul-3741/" target="_blank" rel="noopener" class="inline">NEC rapid shutdown requirements</a> (IAEI, 2023);
        <a href="https://mb.com.ph/2026/05/26/dti-eyes-stricter-safety-certification-for-solar-energy-systems" target="_blank" rel="noopener" class="inline">DTI plans stricter safety certification for solar PV systems</a> (Manila Bulletin, 2026);
        Projoy news: <a href="https://www.projoy-electric.com/News_details/21.html" target="_blank" rel="noopener" class="inline">Solar &amp; Storage Live Philippines</a> and
        <a href="https://www.projoy-electric.com/News_details/59.html" target="_blank" rel="noopener" class="inline">Philippines roadshow</a> (2025).</x-slot:sources>
</x-brand-article>
