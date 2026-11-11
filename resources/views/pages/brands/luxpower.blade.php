@php
    // Key figures of the Luxpower inverters we carry (from their product pages / LuxpowerTek datasheets).
    $models = [
        // [model, type, rated power, max PV, MPPTs, battery current, switchover]
        ['GEN2-LB-EU-6K', 'Hybrid', '6 kW', '9.6 kW', '2', '140 A', '7 ms'],
        ['GEN-LB-EU-8K', 'Hybrid', '8 kW', '12 kW', '2', '167 A', '20 ms'],
        ['GEN-LB-EU-10K', 'Hybrid', '10 kW', '15 kW', '2', '208 A', '20 ms'],
        ['GEN2-LB-EU-12K', 'Hybrid', '12 kW', '18 kW', '3', '250 A', '10 ms'],
        ['GEN2-LB-EU-14K', 'Hybrid', '14 kW (12 kW backup)', '18 kW', '3', '250 A', '10 ms'],
        ['SNA PRO-EU 6.5K', 'Off-grid', '6.5 kW', '12 kW', '2', '140 A', 'under 10 ms'],
        ['SNA 12K', 'Off-grid', '12 kW', '24 kW', '2', '250 A', 'under 10 ms'],
    ];
@endphp
<x-brand-article :slug="$article['slug']" :brand="$article['brand']" :filter="$article['filter']" :products="$products" :settings="$settings"
    title="Luxpower inverters"
    intro="Who Luxpower is, what its hybrid and off-grid inverters do well, its training and support in the Philippines, and how to choose between the models we carry."
    description="Everything about Luxpower (LuxpowerTek): company background, low-voltage hybrid and SNA off-grid inverters, features such as generator and smart-load ports, Luxpower in the Philippines, model comparison and warranty."
    :toc="[
        'who' => 'Who is Luxpower?', 'makes' => 'What Luxpower makes', 'features' => 'Features in plain words',
        'philippines' => 'Luxpower in the Philippines', 'models' => 'The models we carry', 'choose' => 'Hybrid or off-grid?', 'warranty' => 'Warranty',
    ]">
    <section aria-labelledby="who">
        <h2 id="who">Who is Luxpower?</h2>
        <p>Luxpower (LuxpowerTek, legally Shenzhen Lux Power Technology Co., Ltd.) is a Chinese inverter maker founded in 2017 in Shenzhen, where it is headquartered. It specializes in inverters for 48 V battery systems: hybrid inverters for grid-connected homes and off-grid inverters for places with no or unreliable grid power.</p>
        <p>It entered Europe in 2019, launched its first off-grid inverter in South Africa in 2021 and entered the United States in 2023. It is a younger company than GoodWe or Sungrow, focused on homes and small businesses.</p>
        <dl class="ba-facts mt-6">
            <div><dt>Founded</dt><dd>2017<small>Shenzhen, China</small></dd></div>
            <div><dt>Systems installed</dt><dd>Over 350,000<small>2026</small></dd></div>
            <div><dt>Markets</dt><dd>100+ countries<small>2026</small></dd></div>
            <div><dt>Monitoring</dt><dd>LUX app<small>on the LuxCloud 2.0 platform</small></dd></div>
        </dl>
    </section>

    <section aria-labelledby="makes">
        <h2 id="makes">What Luxpower makes</h2>
        <div class="ba-family">
            <h3>Low-voltage hybrid inverters (GEN, GEN2 and GEN3-LB series)</h3>
            <p>Single-phase inverters for 48 V batteries that work with the grid, the battery, solar panels and a generator at the same time.</p>
        </div>
        <div class="ba-family">
            <h3>Off-grid inverters (SNA series)</h3>
            <p>Inverters that run a home or business on solar and batteries without exporting to the grid; the grid or a generator can still be used as backup to charge the battery.</p>
        </div>
        <div class="ba-family">
            <h3>Three-phase and AC-coupled inverters</h3>
            <p>Three-phase hybrids for high-voltage batteries, and an AC-coupled unit that adds a battery to an existing grid-tie solar system.</p>
        </div>
    </section>

    <section aria-labelledby="features">
        <h2 id="features">Features in plain words</h2>
        <ul class="dots">
            <li><strong>Generator port.</strong> A dedicated input for a generator; on some models the inverter can start it automatically when the battery runs low.</li>
            <li><strong>Smart load.</strong> Lets a load such as a water heater or pump run only when there is spare solar power.</li>
            <li><strong>AC coupling.</strong> Works alongside an existing grid-tie inverter, so you can add a battery to an older system.</li>
            <li><strong>Parallel operation.</strong> Up to 10 hybrid inverters, or up to 16 SNA off-grid inverters, can run together for bigger loads.</li>
            <li><strong>UPS switchover.</strong> When the grid fails, backup loads switch to the battery in 7 to 20 milliseconds, depending on the model. Most appliances don’t notice; very sensitive computers may restart on the 20 ms models.</li>
            <li><strong>Weatherproof.</strong> The hybrid inverters are rated IP66 (dust-tight, protected against strong water jets).</li>
        </ul>
    </section>

    <section aria-labelledby="philippines">
        <h2 id="philippines">Luxpower in the Philippines</h2>
        <ul class="dots">
            <li>In January 2026 Luxpower held a technical training in Manila for about 60 installers and distributors, covering troubleshooting, operating modes and the LuxCloud app.</li>
            <li>In March 2026 it ran an installer training in Bacolor, Pampanga, with Power Ai Philippines, launching its SNA off-grid and Geta series.</li>
            <li>Also in March 2026, at the Power Ai summit in San Juan, a nationwide technical service center network for Luxpower and SolaX was announced.</li>
        </ul>
    </section>

    <section aria-labelledby="models">
        <h2 id="models">The models we carry</h2>
        <div class="ba-table-wrap mt-2">
            <table class="ba-table">
                <caption class="sr-only">Key figures of the Luxpower models we carry</caption>
                <thead><tr><th scope="col">Model</th><th scope="col">Type</th><th scope="col">Rated power</th><th scope="col">Max. solar</th><th scope="col">MPPTs</th><th scope="col">Battery current</th><th scope="col">Switchover</th></tr></thead>
                <tbody>
                    @foreach ($models as $m)
                        <tr><th scope="row">{{ $m[0] }}</th>@foreach (array_slice($m, 1) as $v)<td>{{ $v }}</td>@endforeach</tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-2 text-sm text-steel-600">All work with 48 V lithium or lead-acid batteries. “Switchover” is how fast backup loads move to the battery when the grid fails. The SNA PRO is weatherproof for outdoor use; the SNA 12K is for indoor installation.</p>
    </section>

    <section aria-labelledby="choose">
        <h2 id="choose">Hybrid or off-grid?</h2>
        <ul class="dots">
            <li><strong>Choose a hybrid (GEN / GEN2)</strong> if you have a reliable grid connection and want to cut your bill, keep power during brownouts and possibly join <a href="/net-metering" class="inline">net metering</a>.</li>
            <li><strong>Choose an off-grid inverter (SNA)</strong> for farms, islands, rest houses or anywhere without a reliable grid, or if you don’t want to export power at all. It can still use the grid or a generator to top up the battery.</li>
        </ul>
        <p class="mt-3">Then size the inverter for the loads you want running at the same time, and the battery for your night-time use. Our <a href="/pv-calculator" class="inline">PV Calculator</a> gives a first estimate.</p>
    </section>

    <section aria-labelledby="warranty">
        <h2 id="warranty">Warranty</h2>
        <ul class="dots">
            <li><strong>Hybrid inverters:</strong> 5 years standard. Luxpower extends the GEN-LB-EU to 10 years when it is installed with LuxpowerTek batteries and registered with Luxpower.</li>
            <li><strong>Off-grid inverters:</strong> 5 years for the SNA PRO-EU; 2 years for the SNA 12K (extendable to 3 years under the same program).</li>
            <li>We coordinate any warranty claim for you. See <a href="/faq" class="inline">how warranty claims work</a>.</li>
        </ul>
    </section>

    <x-slot:questions>
        <div class="ba-qa">
            <h3>Can I use my existing generator?</h3>
            <p>Yes. All the models we carry have a generator input, so the inverter can charge the battery and power the house from the generator when solar and the battery are not enough.</p>
        </div>
        <div class="ba-qa">
            <h3>Which batteries work with Luxpower?</h3>
            <p>48 V lithium batteries that support Luxpower’s communication protocol, including the Dyness batteries we sell, and lead-acid batteries. See our <a href="/brands/dyness" class="inline">Dyness guide</a>.</p>
        </div>
        <div class="ba-qa">
            <h3>Can I add a second inverter later?</h3>
            <p>Yes, the same model can run in parallel (up to 10 hybrids or 16 SNA off-grid units) when your loads grow.</p>
        </div>
    </x-slot:questions>

    <x-slot:sources>LuxpowerTek, <a href="https://luxpowertek.com/about-us/" target="_blank" rel="noopener" class="inline">About us</a>,
        <a href="https://luxpowertek.com/wp-content/uploads/2026/04/LuxpowerTek-EU-Series-Inverter-Catalogue-2026.03.31.pdf" target="_blank" rel="noopener" class="inline">EU inverter catalogue 2026</a>,
        <a href="https://luxpowertek.com/?p=15394" target="_blank" rel="noopener" class="inline">warranty</a> and
        <a href="https://luxpowertek.com/wp-content/uploads/2024/07/Terms-for-Extended-Warranty-24731.pdf" target="_blank" rel="noopener" class="inline">extended warranty terms</a>;
        datasheets: <a href="https://luxpowertek.com/wp-content/uploads/2025/10/GEN2-LB-EU-3-6K-Datasheet.pdf" target="_blank" rel="noopener" class="inline">GEN2-LB-EU 3–6K</a>,
        <a href="https://luxpowertek.com/wp-content/uploads/2025/10/GEN-LB-EU-7-10K-Datasheet.pdf" target="_blank" rel="noopener" class="inline">GEN-LB-EU 7–10K</a>,
        <a href="https://luxpowertek.com/wp-content/uploads/2026/04/GEN2-LB-EU-7-14K-Datasheet20260325.pdf" target="_blank" rel="noopener" class="inline">GEN2-LB-EU 7–14K</a>,
        <a href="https://luxpowertek.com/wp-content/uploads/2026/04/SNA-PRO-EU-3-6.5K-Datasheet-260410.pdf" target="_blank" rel="noopener" class="inline">SNA PRO-EU</a>;
        Philippines: <a href="https://luxpowertek.com/?p=17570" target="_blank" rel="noopener" class="inline">Manila training</a> (2026),
        <a href="https://powerphilippines.com/pampangas-best-owners-solarpeak-ph-tapped-as-power-ai-distributor-in-pampanga/" target="_blank" rel="noopener" class="inline">Pampanga training</a> and
        <a href="https://powerphilippines.com/solar-firms-highlight-hybrid-tech-nationwide-support-at-power-ai-summit/" target="_blank" rel="noopener" class="inline">Power Ai summit</a> (Power Philippines, 2026).</x-slot:sources>
</x-brand-article>
