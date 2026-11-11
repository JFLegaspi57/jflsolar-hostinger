<x-brand-article :slug="$article['slug']" :brand="$article['brand']" :filter="$article['filter']" :products="$products" :settings="$settings"
    title="Alltopelec (ATE) batteries"
    intro="Who Alltopelec is, how its 16.5 kWh LiFePO4 home battery works, and how to install and size it for a Philippine home."
    description="Everything about Alltopelec (ATE) LiFePO4 batteries: the brand, its local presence in the Philippines, the 51.2 V 324 Ah 16.5 kWh battery we carry, inverter compatibility, installation, sizing and warranty."
    :toc="[
        'who' => 'Who is Alltopelec?', 'lifepo4' => 'LiFePO4 batteries in plain words', 'battery' => 'The 16.5 kWh battery we carry',
        'compatibility' => 'Inverter compatibility', 'install' => 'Where to install it', 'size' => 'How much battery do I need?', 'warranty' => 'Warranty',
    ]">
    <section aria-labelledby="who">
        <h2 id="who">Who is Alltopelec?</h2>
        <p>Alltopelec (also sold as <strong>ATE</strong> or ALLTOP) is a Chinese brand of lithium iron phosphate (LiFePO4) battery systems. By its own account it was established in 2018, expanded into Southeast Asia in 2020, and now sells in more than 30 markets.</p>
        <p>Alltopelec has a Philippine sales team and a warehouse in Manila, so batteries and support are available locally. Its range covers 51.2 V home batteries from 5.12 kWh to 20.48 kWh, high-voltage battery cabinets for businesses, and portable power stations. Its batteries carry CE and UN38.3 (transport safety) certification.</p>
        <p class="mt-3">Alltopelec is a younger and smaller brand than Dyness or GoodWe. Its appeal is a large battery at a competitive price with local stock.</p>
    </section>

    <section aria-labelledby="lifepo4">
        <h2 id="lifepo4">LiFePO4 batteries in plain words</h2>
        <ul class="dots">
            <li><strong>Safe chemistry.</strong> Lithium iron phosphate is far more resistant to overheating and fire than the lithium types used in phones and many EVs. That is why it has become the standard for home batteries.</li>
            <li><strong>Long life.</strong> A good LiFePO4 battery lasts thousands of charge cycles. One cycle a day for 10 years is about 3,650 cycles.</li>
            <li><strong>Usable capacity.</strong> Unlike lead-acid, which should only be half-emptied, LiFePO4 can use most of its capacity every day.</li>
            <li><strong>BMS (battery management system).</strong> The electronics inside the battery that balance the cells, protect against over-charge, over-discharge and overheating, and report the state of charge to the inverter.</li>
        </ul>
    </section>

    <section aria-labelledby="battery">
        <h2 id="battery">The 16.5 kWh battery we carry (51.2 V, 324 Ah)</h2>
        <dl class="ba-facts mt-4">
            <div><dt>Energy</dt><dd>16.5 kWh<small>51.2 V × 324 Ah</small></dd></div>
            <div><dt>Cells</dt><dd>LiFePO4<small>CATL EV-grade cells</small></dd></div>
            <div><dt>Cycle life</dt><dd>10,000+ cycles<small>under the maker’s test conditions</small></dd></div>
            <div><dt>Expansion</dt><dd>Up to 20 in parallel<small>start with one, add more later</small></dd></div>
            <div><dt>Monitoring</dt><dd>Wi-Fi and Bluetooth app<small>smart BMS with cell balancing</small></dd></div>
            <div><dt>Enclosure</dt><dd>IP54, wall-mounted<small>discharge from −20 to 40 °C</small></dd></div>
        </dl>
        <p class="mt-4">51.2 V is the standard for “48 V” home batteries, so it suits the low-voltage hybrid and off-grid inverters most Filipino homes use.</p>
    </section>

    <section aria-labelledby="compatibility">
        <h2 id="compatibility">Inverter compatibility</h2>
        <p>A lithium battery should “talk” to the hybrid inverter over a communication cable (CAN or RS485), so the inverter knows exactly how full the battery is and charges it safely. Alltopelec lists brands such as Growatt, Deye, Victron and SMA, subject to protocol confirmation.</p>
        <p>Before you buy, we confirm that the battery communicates with your exact inverter model and set the right protocol during commissioning. Tell us your inverter when you ask for a quote.</p>
    </section>

    <section aria-labelledby="install">
        <h2 id="install">Where to install it</h2>
        <ul class="dots">
            <li><strong>Keep it cool and shaded.</strong> The battery is rated to work up to 40 °C, and like every lithium battery it lasts longer when kept cool. Install it indoors or under a roof, never in direct afternoon sun.</li>
            <li><strong>IP54</strong> means protected against dust and splashing water, not heavy rain or flooding. Keep it off the floor in flood-prone areas.</li>
            <li><strong>Short, thick cables.</strong> At 51.2 V a 10 kW load draws about 200 A, so the cables to the inverter must be properly sized, with the right battery lugs and a DC breaker or fuse.</li>
        </ul>
    </section>

    <section aria-labelledby="size">
        <h2 id="size">How much battery do I need?</h2>
        <p>Add up the electricity you use from sunset to sunrise, plus what you want available for a brownout. For example, a home that uses about 8 kWh at night (lights, fans, a refrigerator, a TV and an inverter aircon for a few hours) can run on one 16.5 kWh battery and still keep a reserve for a brownout. Our <a href="/pv-calculator" class="inline">PV Calculator</a> gives a first estimate, and we fine-tune it from your bill.</p>
    </section>

    <section aria-labelledby="warranty">
        <h2 id="warranty">Warranty</h2>
        <ul class="dots">
            <li><strong>5-year product warranty</strong>. Alltopelec reviews the serial number and the installation before handling a claim, so keep your invoice and installation photos.</li>
            <li>We coordinate any warranty claim for you. See <a href="/faq" class="inline">how warranty claims work</a>.</li>
        </ul>
    </section>

    <x-slot:questions>
        <div class="ba-qa">
            <h3>Alltopelec or Dyness?</h3>
            <p>Both are 51.2 V LiFePO4 batteries of about 16 kWh. Dyness is the larger, longer-established brand with published datasheets and wide inverter compatibility lists; Alltopelec offers a slightly larger 16.5 kWh battery with local stock. See our <a href="/brands/dyness" class="inline">Dyness guide</a>.</p>
        </div>
        <div class="ba-qa">
            <h3>Can I add a second battery later?</h3>
            <p>Yes, up to 20 in parallel. It is best to add the same model, and to add it within a few years so the batteries age similarly.</p>
        </div>
        <div class="ba-qa">
            <h3>Can I use it off-grid?</h3>
            <p>Yes, with an off-grid or hybrid inverter that supports 48 V lithium batteries.</p>
        </div>
    </x-slot:questions>

    <x-slot:sources>Alltopelec, <a href="https://alltopelec.com/about/" target="_blank" rel="noopener" class="inline">About</a>,
        <a href="https://alltopelec.com/" target="_blank" rel="noopener" class="inline">home page</a>,
        <a href="https://alltopelec.com/product/16-5kwh-lifepo4-battery/" target="_blank" rel="noopener" class="inline">16.5 kWh LiFePO4 battery</a> and
        <a href="https://alltopelec.com/olar-battery-price-philippines-5kwh-11kwh-16kwh-20kwh-cost-guide/" target="_blank" rel="noopener" class="inline">Philippine battery range</a> (2026); our product listing for the 16.5 kWh battery.</x-slot:sources>
</x-brand-article>
