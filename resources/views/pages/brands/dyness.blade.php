@php
    // Key figures of the Dyness PowerBrick batteries we carry (from their product pages / Dyness datasheets).
    $models = [
        // [model, rated / usable energy, max charge & discharge, cycle life, protection, weight, extra]
        ['PowerBrick Max', '16.08 / 15.28 kWh', '200 A, 10.24 kW', '8,000+', 'IP20 (indoor)', '116 kg', '—'],
        ['PowerBrick Plus', '16.08 / 15.28 kWh', '200 A, 10.24 kW', '8,000+', 'IP65 (outdoor)', '117 kg', 'Outdoor-rated'],
        ['PowerBrick SC', '16.08 / 15.28 kWh', '200 A, 10.24 kW', '8,000+', 'IP20 (indoor)', '116 kg', 'Built-in display'],
        ['PowerBrick Max20', '20.07 / 19.07 kWh', '210 A, 10.75 kW', '10,000+', 'IP20 (indoor)', '137 kg', 'Fire extinguisher built in'],
    ];
@endphp
<x-brand-article :slug="$article['slug']" :brand="$article['brand']" :filter="$article['filter']" :products="$products" :settings="$settings"
    title="Dyness batteries"
    intro="Who Dyness is, how its PowerBrick LiFePO4 home batteries compare, which inverters they work with, and how to install and size them."
    description="Everything about Dyness batteries: company background, PowerBrick Max, Plus, SC and Max20 LiFePO4 batteries, inverter compatibility (GoodWe, Luxpower, Solis), Dyness in the Philippines, installation, sizing and warranty."
    :toc="[
        'who' => 'Who is Dyness?', 'makes' => 'What Dyness makes', 'philippines' => 'Dyness in the Philippines',
        'powerbrick' => 'The PowerBrick batteries we carry', 'which' => 'Which PowerBrick is right for you?', 'compatibility' => 'Inverter compatibility',
        'size' => 'How much battery do I need?', 'warranty' => 'Warranty',
    ]">
    <section aria-labelledby="who">
        <h2 id="who">Who is Dyness?</h2>
        <p>Dyness Digital Energy Technology Co., Ltd. is a Chinese maker of lithium battery storage, founded in August 2017. Its global headquarters opened in Suzhou in 2023, and it makes its own battery management systems (BMS), the electronics that protect and balance the cells.</p>
        <p>Dyness focuses on batteries for homes and businesses and is one of the most widely used battery brands in Southeast Asia.</p>
        <dl class="ba-facts mt-6">
            <div><dt>Founded</dt><dd>2017<small>headquarters in Suzhou, China</small></dd></div>
            <div><dt>Batteries shipped</dt><dd>Over 1,000,000 units<small>end of 2025</small></dd></div>
            <div><dt>Production</dt><dd>3 GWh a year<small>three production centers (2025)</small></dd></div>
            <div><dt>Market position</dt><dd>No. 3 in Asia<small>home battery shipments, InfoLink, first half of 2026</small></dd></div>
            <div><dt>Award</dt><dd>EUPD Top Brand PV Storage<small>Southeast Asia, 2026</small></dd></div>
            <div><dt>Reach</dt><dd>100+ countries<small>1,000+ staff, 240+ patents (2025)</small></dd></div>
        </dl>
    </section>

    <section aria-labelledby="makes">
        <h2 id="makes">What Dyness makes</h2>
        <div class="ba-family">
            <h3>Low-voltage home batteries (51.2 V)</h3>
            <p>The <strong>PowerBrick</strong> series we carry, plus the rack-style PowerBox and stackable STACK batteries, for 48 V hybrid and off-grid inverters.</p>
        </div>
        <div class="ba-family">
            <h3>High-voltage and all-in-one</h3>
            <p>The Tower Pro high-voltage battery and the Cygni all-in-one system (inverter and battery in one unit).</p>
        </div>
        <div class="ba-family">
            <h3>Commercial storage</h3>
            <p>Battery cabinets for businesses and factories, up to several megawatt-hours.</p>
        </div>
    </section>

    <section aria-labelledby="philippines">
        <h2 id="philippines">Dyness in the Philippines</h2>
        <ul class="dots">
            <li>Dyness batteries are distributed in the Philippines by <strong>Photonergy Philippines Inc.</strong>, its main distributor here.</li>
            <li>Dyness has a local team in the Philippines for pre-sales, order tracking and after-sales support.</li>
            <li>In May 2024 it held its “Light of Tomorrow” event at Conrad Manila together with Solis and JA Solar, and in May 2025 a Brand Day there with Solis and JA Solar, where the PowerBrick was shown.</li>
            <li>In August 2025 Solis toured the provinces (Calapan, Tuguegarao, Kalibo, Naga, Dumaguete and Ozamiz) demonstrating its hybrid inverter with Dyness batteries.</li>
        </ul>
    </section>

    <section aria-labelledby="powerbrick">
        <h2 id="powerbrick">The PowerBrick batteries we carry</h2>
        <p>All four are 51.2 V lithium iron phosphate (LiFePO4) batteries with 95% usable depth of discharge, Wi-Fi monitoring through the Dyness app, and CAN/RS485 communication with the inverter.</p>
        <div class="ba-table-wrap mt-4">
            <table class="ba-table">
                <caption class="sr-only">Key figures of the Dyness PowerBrick batteries</caption>
                <thead><tr><th scope="col">Model</th><th scope="col">Rated / usable</th><th scope="col">Max. charge &amp; discharge</th><th scope="col">Cycle life</th><th scope="col">Protection</th><th scope="col">Weight</th><th scope="col">Extra</th></tr></thead>
                <tbody>
                    @foreach ($models as $m)
                        <tr><th scope="row">{{ $m[0] }}</th>@foreach (array_slice($m, 1) as $v)<td>{{ $v }}</td>@endforeach</tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-2 text-sm text-steel-600">Cycle life is Dyness’s test figure (8,000 cycles to 70% capacity for the 314 Ah models). One cycle a day for 20 years is about 7,300 cycles.</p>
        <h3 class="mt-6">What this means for you</h3>
        <ul class="dots">
            <li><strong>About 15 kWh you can actually use</strong> from the 16 kWh models, enough for a typical home’s evening and night.</li>
            <li><strong>Up to 10 kW of power</strong>, so the battery alone can run several aircons and a pump at once (the inverter sets the final limit).</li>
            <li><strong>LiFePO4 safety</strong>: the safest common lithium chemistry for homes, very resistant to overheating.</li>
        </ul>
    </section>

    <section aria-labelledby="which">
        <h2 id="which">Which PowerBrick is right for you?</h2>
        <ul class="dots">
            <li><strong>PowerBrick Max:</strong> the standard 16 kWh battery for indoor installation, such as a utility room or garage.</li>
            <li><strong>PowerBrick Plus:</strong> the same capacity in an IP65 outdoor case, for when there is no indoor space. Still install it in the shade.</li>
            <li><strong>PowerBrick SC:</strong> the Max with a built-in screen, so you can see the charge level without the app.</li>
            <li><strong>PowerBrick Max20:</strong> 20 kWh in one unit for bigger homes or longer brownouts, with a built-in aerosol fire extinguisher.</li>
        </ul>
        <p class="mt-3">These batteries weigh 116–137 kg, so they need a solid floor or wall, and a cool, dry, ventilated spot. Batteries last longest when kept away from heat.</p>
    </section>

    <section aria-labelledby="compatibility">
        <h2 id="compatibility">Inverter compatibility</h2>
        <p>A lithium battery must “talk” to the hybrid inverter so the inverter knows its exact charge level and charges it safely. Dyness publishes a list of compatible inverters, which includes:</p>
        <ul class="dots mt-3">
            <li><strong>GoodWe</strong>, including the ES-C10 8–12 kW hybrids and EO off-grid inverters (<a href="/brands/goodwe" class="inline">our GoodWe guide</a>);</li>
            <li><strong>Luxpower</strong> hybrid inverters (<a href="/brands/luxpower" class="inline">our Luxpower guide</a>);</li>
            <li><strong>Solis</strong>, and many other brands.</li>
        </ul>
        <p class="mt-3">We check your exact inverter model against the current list and set the right protocol during commissioning.</p>
    </section>

    <section aria-labelledby="size">
        <h2 id="size">How much battery do I need?</h2>
        <p>Add up the electricity you use from sunset to sunrise, plus a reserve for brownouts. As an example, a home that uses about 10 kWh at night fits one 16 kWh PowerBrick with room to spare; a home with aircon running all night may need the Max20 or two batteries in parallel. Our <a href="/pv-calculator" class="inline">PV Calculator</a> gives a first estimate, and we fine-tune it from your bill.</p>
    </section>

    <section aria-labelledby="warranty">
        <h2 id="warranty">Warranty</h2>
        <ul class="dots">
            <li>Dyness states a <strong>10-year warranty</strong> for its PowerBrick batteries. The exact terms (such as the guaranteed capacity at the end of the warranty) are in the warranty document for your unit; ask us for a copy.</li>
            <li>We coordinate any warranty claim for you. See <a href="/faq" class="inline">how warranty claims work</a>.</li>
        </ul>
    </section>

    <x-slot:questions>
        <div class="ba-qa">
            <h3>Can I add a second battery later?</h3>
            <p>Yes. PowerBricks can be connected in parallel. Add the same model, and ideally within a few years so the batteries age similarly.</p>
        </div>
        <div class="ba-qa">
            <h3>Dyness or Alltopelec?</h3>
            <p>Both make 51.2 V LiFePO4 batteries of about 16 kWh. Dyness is the bigger, longer-established brand with published compatibility lists and a local team; Alltopelec offers a slightly larger 16.5 kWh battery. See our <a href="/brands/alltopelec" class="inline">Alltopelec guide</a>.</p>
        </div>
        <div class="ba-qa">
            <h3>Can I use it off-grid?</h3>
            <p>Yes, with an off-grid or hybrid inverter that supports 48 V lithium batteries.</p>
        </div>
    </x-slot:questions>

    <x-slot:sources>Dyness, <a href="https://dyness.com/company-profile" target="_blank" rel="noopener" class="inline">company profile</a>,
        product pages for the <a href="https://www.dyness.com/powerbrick-max-low-voltage-residential-battery" target="_blank" rel="noopener" class="inline">PowerBrick Max</a>,
        <a href="https://www.dyness.com/powerbrick-plus-low-voltage-storage-batteries-for-household-use" target="_blank" rel="noopener" class="inline">Plus</a>,
        <a href="https://www.dyness.com/powerbrick-sc-low-voltage-storage-batteries-for-household-use" target="_blank" rel="noopener" class="inline">SC</a> and
        <a href="https://www.dyness.com/dyness-powerbrick-max20-low-voltage-home-battery" target="_blank" rel="noopener" class="inline">Max20</a>,
        <a href="https://apac.dyness.com/Public/Uploads/uploadfile/files/20260205/diyaEN.pdf" target="_blank" rel="noopener" class="inline">compatible inverter list</a> (2026),
        <a href="https://apac.dyness.com/company-news-207" target="_blank" rel="noopener" class="inline">EUPD Top Brand</a> (2026) and
        <a href="https://apac.dyness.com/company-news-74" target="_blank" rel="noopener" class="inline">“Light of Tomorrow” in Manila</a> (2024);
        <a href="https://www.pvtime.org/?p=26163" target="_blank" rel="noopener" class="inline">InfoLink home battery ranking</a> (pvtime, 2026);
        Power Philippines, <a href="https://powerphilippines.com/?p=19716" target="_blank" rel="noopener" class="inline">Brand Day 2025</a> and
        <a href="https://powerphilippines.com/?p=21595" target="_blank" rel="noopener" class="inline">Solis roadshow</a> (2025).</x-slot:sources>
</x-brand-article>
