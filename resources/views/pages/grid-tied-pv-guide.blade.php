@php
    use App\Support\Site;
    use App\Support\Ui;
    $toc = [
        'short' => 'The short version', 'parts' => 'The parts of the system', 'inverter' => 'Inside the grid-tied inverter',
        'flow' => 'How the power is shared', 'day' => 'A day in the life', 'grid' => 'Working with the grid',
        'brownout' => 'Why it switches off in a brownout', 'export' => 'Net metering and export limits', 'array' => 'The solar array and MPPT',
        'variants' => 'String, micro and optimizer systems', 'protection' => 'Protection and safety', 'losses' => 'Efficiency and losses',
        'compare' => 'Grid-tied vs hybrid vs off-grid',
    ];
@endphp
<x-site-layout title="How a Grid-Tied Solar PV System Works" path="/grid-tied-pv-system-guide" :reviews-above-footer="false"
    description="A technical guide to how a grid-tied (on-grid) solar PV system works: solar array and MPPT, the inverter's converters, grid synchronisation, anti-islanding, power sharing with the grid, net metering, export limits, protection devices and losses.">
    <x-slot:head>
        @include('partials.guide-styles')
    </x-slot:head>
    <x-page-header eyebrow="Technical guide" title="How a grid-tied solar PV system works"
        intro="The simplest and most cost-effective way to cut your electric bill: how the panels, inverter and grid work together, what happens inside the inverter, and why the system switches off during a brownout." />
    <x-container class="py-10 sm:py-14">
        <div class="hg space-y-10 text-steel-800" style="max-width: 48rem">
            <nav aria-label="On this page" class="hg-toc hg-note">
                <p class="font-bold text-steel-950">On this page</p>
                <ul class="mt-1 grid sm:grid-cols-2" style="column-gap: 1.5rem">
                    @foreach ($toc as $id => $label)<li><a href="#{{ $id }}">{{ $label }}</a></li>@endforeach
                </ul>
            </nav>

            <section aria-labelledby="short">
                <h2 id="short">The short version</h2>
                <p>A <strong>grid-tied solar PV system</strong> (also called on-grid) has solar panels and a <strong>grid-tied inverter</strong>, connected to your home’s main panel alongside the utility grid. There is no battery. During the day the solar power is used by your appliances first; when the panels make more than you use, the surplus flows out to the grid, and when they make less (or at night), the grid supplies the difference automatically.</p>
                <p>Technically, the inverter works as a <strong>grid-following current source</strong>: it measures the grid’s voltage and frequency and injects a current exactly in step with it. It cannot run without the grid, which is why it shuts down in a brownout.</p>
            </section>

            <section aria-labelledby="parts">
                <h2 id="parts">The parts of the system</h2>
                <div class="hg-diagram mt-4">
                    <svg viewBox="0 0 760 380" role="img" aria-labelledby="dg-title dg-desc">
                        <title id="dg-title">Grid-tied solar PV system power-flow diagram</title>
                        <desc id="dg-desc">Solar panels connect through a DC isolator and rapid shutdown device to the grid-tied inverter. The inverter's AC output connects through a breaker to the main panel, which feeds the household loads and connects through the bi-directional meter to the utility grid. A current sensor on the grid line sends data to the inverter for export limiting.</desc>
                        <rect class="box" x="20" y="40" width="170" height="80" rx="10"/>
                        <text class="t" x="105" y="72" text-anchor="middle">Solar PV array</text>
                        <text class="s" x="105" y="92" text-anchor="middle">strings of panels</text>
                        <text class="s" x="105" y="108" text-anchor="middle">200–1,000 V DC</text>
                        <rect class="box" x="20" y="200" width="170" height="64" rx="10"/>
                        <text class="t" x="105" y="227" text-anchor="middle">DC protection</text>
                        <text class="s" x="105" y="247" text-anchor="middle">isolator · SPD · RSD</text>
                        <rect class="box-main" x="240" y="40" width="250" height="300" rx="12"/>
                        <text class="t" x="365" y="66" text-anchor="middle">Grid-tied inverter</text>
                        <rect class="inner" x="260" y="82" width="210" height="44" rx="6"/>
                        <text class="s" x="365" y="100" text-anchor="middle">MPPT DC–DC converters</text>
                        <text class="s" x="365" y="116" text-anchor="middle">(one per MPPT input)</text>
                        <rect class="inner" x="260" y="142" width="210" height="36" rx="6"/>
                        <text class="s" x="365" y="165" text-anchor="middle">DC link (capacitors)</text>
                        <rect class="inner" x="260" y="194" width="210" height="44" rx="6"/>
                        <text class="s" x="365" y="212" text-anchor="middle">Inverter bridge (PWM)</text>
                        <text class="s" x="365" y="228" text-anchor="middle">+ output filter</text>
                        <rect class="inner" x="260" y="254" width="210" height="44" rx="6"/>
                        <text class="s" x="365" y="272" text-anchor="middle">Controller: PLL, anti-islanding,</text>
                        <text class="s" x="365" y="288" text-anchor="middle">grid protection, relays</text>
                        <text class="s" x="365" y="325" text-anchor="middle">AC output: 230 V, 60 Hz</text>
                        <rect class="box" x="560" y="40" width="180" height="70" rx="10"/>
                        <text class="t" x="650" y="70" text-anchor="middle">Main panel</text>
                        <text class="s" x="650" y="90" text-anchor="middle">PV breaker + AC SPD</text>
                        <rect class="box" x="560" y="150" width="180" height="60" rx="10"/>
                        <text class="t" x="650" y="177" text-anchor="middle">Household loads</text>
                        <text class="s" x="650" y="196" text-anchor="middle">all circuits</text>
                        <rect class="box" x="560" y="270" width="180" height="70" rx="10"/>
                        <text class="t" x="650" y="298" text-anchor="middle">Utility grid</text>
                        <text class="s" x="650" y="318" text-anchor="middle">via bi-directional meter</text>
                        <path class="dc" d="M105 120 V200"/>
                        <path class="dc" d="M190 232 H215 V104 H260"/>
                        <path class="ac" d="M490 215 H525 V75 H560"/>
                        <path class="ac" d="M610 110 V150"/>
                        <path class="ac" d="M740 75 H752 V250 H659"/>
                        <path class="ac" d="M650 259 V270"/>
                        <circle cx="650" cy="250" r="9" fill="#fff" stroke="var(--color-steel-500)" stroke-width="1.5"/>
                        <text class="s" x="600" y="240" text-anchor="middle">CT / meter</text>
                        <path class="data" d="M641 250 H545 V280 H490"/>
                    </svg>
                    <p class="hg-legend" style="position: sticky; left: 0"><span class="l-dc">DC power</span><span class="l-ac">AC power</span><span class="l-data">Data / measurement</span></p>
                </div>
                <div class="hg-table-wrap mt-6">
                    <table class="hg-table">
                        <caption class="sr-only">Main components of a grid-tied solar PV system</caption>
                        <thead><tr><th scope="col">Part</th><th scope="col">What it does</th><th scope="col">Technical notes</th></tr></thead>
                        <tbody>
                            <tr><th scope="row">Solar PV array</th><td>Turns sunlight into DC electricity.</td><td>Panels in series “strings”; voltage must fit the inverter’s MPPT range and maximum DC input on the coolest morning.</td></tr>
                            <tr><th scope="row">Grid-tied inverter</th><td>Converts the DC into AC that is synchronised with the grid.</td><td>Single-phase for homes, three-phase for businesses; up to about 98% efficient.</td></tr>
                            <tr><th scope="row">Main panel</th><td>Joins solar power with grid power and distributes it to your circuits.</td><td>The inverter connects through its own breaker; loads draw from solar and grid seamlessly.</td></tr>
                            <tr><th scope="row">CT or smart meter (optional)</th><td>Measures power to or from the grid.</td><td>Needed for export limits or zero export, and for showing household consumption in the app.</td></tr>
                            <tr><th scope="row">Bi-directional meter</th><td>Records imported and exported energy.</td><td>Installed by your utility for <a href="/net-metering" class="inline">net metering</a>.</td></tr>
                            <tr><th scope="row">Protection devices</th><td>Keep people and equipment safe.</td><td>DC isolator, PV and AC surge protectors, breakers, rapid shutdown device and earthing.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section aria-labelledby="inverter">
                <h2 id="inverter">Inside the grid-tied inverter</h2>
                <p>A grid-tied inverter is simpler than a hybrid one: no battery converter and no backup port. It has two power stages around a DC link, and a fast controller that keeps it in step with the grid.</p>
                <h3>1. MPPT DC–DC converters</h3>
                <p>Each MPPT input adjusts its string’s voltage to the <strong>maximum power point</strong>, where voltage × current is highest. The tracker continuously nudges the operating point and checks whether power rose or fell (“perturb and observe”), and periodically scans the whole curve so partial shade does not trap it on a false peak. Many inverters have two or more MPPTs so differently facing roof sections are optimised separately.</p>
                <h3>2. The DC link</h3>
                <p>Large capacitors hold a stable DC voltage between the two stages and buffer the energy, because a single-phase inverter delivers power to the grid in pulses at twice the grid frequency (120 Hz) while the panels deliver it steadily.</p>
                <h3>3. Inverter bridge and filter</h3>
                <p>Semiconductor switches (IGBTs or MOSFETs) chop the DC at high frequency with <strong>pulse-width modulation</strong> (PWM). An inductor–capacitor filter turns the pulses into a smooth sinusoidal current with low harmonic distortion (typically under 3%), as grid codes require.</p>
                <h3>4. Controller</h3>
                <ul class="dots">
                    <li><strong>Phase-locked loop (PLL):</strong> locks the output current to the grid voltage’s frequency (60 Hz) and phase, so power flows out at the intended power factor (normally about 1).</li>
                    <li><strong>Current control:</strong> the inverter does not set the voltage, the grid does; it regulates how much current it pushes in, based on how much power the MPPT stage is harvesting.</li>
                    <li><strong>Grid protection:</strong> continuously checks voltage and frequency against the grid code’s limits and opens its output relay if they go out of range.</li>
                    <li><strong>Insulation and residual-current monitoring:</strong> checks the PV array’s insulation to earth before connecting and watches for leakage current while running.</li>
                </ul>
            </section>

            <section aria-labelledby="flow">
                <h2 id="flow">How the power is shared</h2>
                <p>The grid acts like an infinitely large battery that is always connected. Power follows physics, not a schedule: your appliances draw from the nearest source, and the inverter pushes in all the solar power it can harvest.</p>
                <p class="hg-formula">P(solar) + P(grid import) = P(loads) + P(grid export) + losses</p>
                <ul class="dots mt-3">
                    <li><strong>Solar &gt; load:</strong> the surplus flows backwards through your meter to the grid (exported).</li>
                    <li><strong>Solar &lt; load:</strong> the grid automatically supplies the difference (imported).</li>
                    <li><strong>Night:</strong> the inverter sleeps; everything comes from the grid as before.</li>
                </ul>
                <p class="mt-3">Because solar power used directly in your home saves you the full retail rate, while exported power is credited at a lower rate, a grid-tied system pays back fastest when it is sized around your <strong>daytime</strong> consumption, for example offices, shops, factories, schools and homes with daytime aircon.</p>
            </section>

            <section aria-labelledby="day">
                <h2 id="day">A day in the life</h2>
                <ul class="dots">
                    <li><strong>About 6 AM:</strong> the array voltage rises above the inverter’s start-up voltage; it checks insulation and the grid, waits the required reconnection time, then begins exporting a small current.</li>
                    <li><strong>Morning:</strong> output climbs with the sun; your appliances use it first and draw less from the grid.</li>
                    <li><strong>Midday:</strong> peak output; if it exceeds your use, the surplus is exported (or limited if you have an export cap). On oversized arrays the inverter may briefly “clip” at its AC rating.</li>
                    <li><strong>Afternoon:</strong> output falls; the grid gradually supplies more.</li>
                    <li><strong>Evening and night:</strong> the inverter disconnects and goes to standby; all power comes from the grid.</li>
                </ul>
            </section>

            <section aria-labelledby="grid">
                <h2 id="grid">Working with the grid</h2>
                <ul class="dots">
                    <li><strong>Synchronisation:</strong> before connecting, the inverter must see a stable grid within limits for a set time, then match its output to it.</li>
                    <li><strong>Voltage rise:</strong> exporting power slightly raises the voltage at your connection point. On long service drops or weak rural lines this can push voltage past the limit and make the inverter trip; correct cable sizing and, where allowed, reactive-power settings help.</li>
                    <li><strong>Power quality:</strong> inverters must keep harmonics low and, depending on the grid code, can adjust power factor or reduce output when grid frequency or voltage is high.</li>
                    <li><strong>Grid code settings:</strong> the installer selects the country or utility profile so trip limits and reconnection times match local requirements.</li>
                </ul>
            </section>

            <section aria-labelledby="brownout">
                <h2 id="brownout">Why it switches off in a brownout</h2>
                <p>When the grid fails, a grid-tied inverter must stop within a fraction of a second, even if the sun is shining. This is called <strong>anti-islanding</strong> protection, and every grid-tied inverter is required to have it.</p>
                <ol class="steps mt-3">
                    <li><strong>Safety of line workers:</strong> if the inverter kept running, it could energise “dead” utility lines that technicians are repairing.</li>
                    <li><strong>Detection:</strong> the inverter watches for voltage and frequency moving out of range, and uses active methods (small deliberate shifts in frequency or current) that would cause a rapid drift if the grid were missing.</li>
                    <li><strong>Disconnection:</strong> its relay opens, typically within about two seconds at most and often much faster.</li>
                    <li><strong>Reconnection:</strong> after the grid returns and stays within limits for the required time (often a few minutes), it reconnects automatically.</li>
                </ol>
                <div class="hg-note mt-4"><p><strong>Need power during brownouts?</strong> Add a battery with a <a href="/hybrid-pv-system-guide" class="inline">hybrid solar PV system</a>, or retrofit an AC-coupled battery inverter to an existing grid-tied system.</p></div>
            </section>

            <section aria-labelledby="export">
                <h2 id="export">Net metering and export limits</h2>
                <ul class="dots">
                    <li><strong>Net metering:</strong> your utility installs a bi-directional meter; exported kWh earn credits that reduce your bill. Systems of 100 kW or smaller can apply. Read our <a href="/net-metering" class="inline">net metering guide</a>.</li>
                    <li><strong>Without net metering:</strong> exported power may simply be lost, or your old meter may even count it wrongly, so many owners set a <strong>zero-export</strong> limit until net metering is approved.</li>
                    <li><strong>How export limiting works:</strong> a CT or smart meter at the grid connection tells the inverter how much is flowing out; the inverter moves its MPPT away from the maximum power point to reduce output so export stays at the set limit.</li>
                    <li><strong>Commercial sites:</strong> businesses often use zero export and size the array to their daytime base load, so almost every kWh is consumed on site.</li>
                </ul>
            </section>

            <section aria-labelledby="array">
                <h2 id="array">The solar array and MPPT</h2>
                <ul class="dots">
                    <li><strong>I–V curve:</strong> current follows sunlight; voltage falls as panels heat up. Power peaks at Vmp × Imp.</li>
                    <li><strong>String voltage:</strong> the coldest-morning open-circuit voltage must stay below the inverter’s maximum DC input, and the hot-afternoon working voltage inside the MPPT range.</li>
                    <li><strong>Current:</strong> string current must suit each MPPT input’s maximum and short-circuit ratings.</li>
                    <li><strong>DC/AC ratio:</strong> 1.1 to 1.3 (and up to about 1.5 on some models) is common, so the inverter runs near full power for more hours; small midday clipping losses are usually outweighed by the extra morning and afternoon energy.</li>
                </ul>
                <p class="hg-formula">Max. string Voc ≈ Voc(STC) × [1 + temp. coefficient(Voc) × (T(min) − 25 °C)] × number of panels</p>
            </section>

            <section aria-labelledby="variants">
                <h2 id="variants">String, micro and optimizer systems</h2>
                <div class="hg-table-wrap">
                    <table class="hg-table">
                        <caption class="sr-only">Grid-tied inverter architectures</caption>
                        <thead><tr><th scope="col">Type</th><th scope="col">How it works</th><th scope="col">Best for</th></tr></thead>
                        <tbody>
                            <tr><th scope="row">String inverter</th><td>One central inverter with a few MPPT inputs, each serving a string of panels.</td><td>Most homes and businesses with simple, unshaded roofs; lowest cost per kW.</td></tr>
                            <tr><th scope="row">Microinverters</th><td>A small inverter under each panel (or pair of panels), turning DC into AC right on the roof.</td><td>Complex or partly shaded roofs; panel-level monitoring; only low-voltage DC on the roof.</td></tr>
                            <tr><th scope="row">String inverter + optimizers</th><td>A DC–DC optimizer on each panel does its own MPPT, feeding a central inverter.</td><td>Shaded roofs that still want a central inverter; panel-level monitoring and shutdown.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section aria-labelledby="protection">
                <h2 id="protection">Protection and safety</h2>
                <ul class="dots">
                    <li><strong>DC side:</strong> DC isolator, DC surge protective device (SPD), string fuses where three or more strings are paralleled, and a <a href="/brands/projoy" class="inline">rapid shutdown device</a> to de-energise roof cabling in an emergency.</li>
                    <li><strong>AC side:</strong> a dedicated breaker for the inverter in the main panel, an AC SPD, and residual-current protection where required.</li>
                    <li><strong>Earthing:</strong> panel frames, rails, inverter and enclosures bonded to a proper earth rod.</li>
                    <li><strong>Inverter functions:</strong> anti-islanding, grid voltage and frequency protection, insulation and residual-current monitoring, and arc-fault detection (AFCI) on many newer models.</li>
                </ul>
            </section>

            <section aria-labelledby="losses">
                <h2 id="losses">Efficiency and losses</h2>
                <div class="hg-table-wrap">
                    <table class="hg-table">
                        <caption class="sr-only">Typical losses in a grid-tied solar PV system</caption>
                        <thead><tr><th scope="col">Loss</th><th scope="col">Typical size</th><th scope="col">How to reduce it</th></tr></thead>
                        <tbody>
                            <tr><th scope="row">Panel temperature</th><td>8–15% at midday in the Philippines</td><td>Panels with a low temperature coefficient; airflow under the panels.</td></tr>
                            <tr><th scope="row">Dust and soiling</th><td>2–5%</td><td>Regular cleaning, especially in the dry season.</td></tr>
                            <tr><th scope="row">Shading and mismatch</th><td>0–10%+</td><td>Good layout, separate MPPTs, optimizers or microinverters.</td></tr>
                            <tr><th scope="row">DC and AC cabling</th><td>1–2%</td><td>Correct cable sizes and short runs.</td></tr>
                            <tr><th scope="row">Inverter conversion</th><td>1.5–3% (97–98.5% efficient)</td><td>A quality inverter sized to the array.</td></tr>
                            <tr><th scope="row">Clipping and export limits</th><td>0–5%</td><td>Sensible DC/AC ratio; net metering instead of zero export.</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-3">With no battery to charge and discharge, a grid-tied system is the most efficient way to turn sunlight into usable electricity.</p>
            </section>

            <section aria-labelledby="compare">
                <h2 id="compare">Grid-tied vs hybrid vs off-grid</h2>
                <div class="hg-table-wrap">
                    <table class="hg-table">
                        <caption class="sr-only">Comparison of grid-tied, hybrid and off-grid solar PV systems</caption>
                        <thead><tr><th scope="col"></th><th scope="col">Grid-tied</th><th scope="col">Hybrid</th><th scope="col">Off-grid</th></tr></thead>
                        <tbody>
                            <tr><th scope="row">Battery</th><td>None</td><td>Yes</td><td>Yes (essential)</td></tr>
                            <tr><th scope="row">Power in a brownout</th><td>No (shuts down by law)</td><td>Yes, on backup circuits</td><td>Yes, no grid involved</td></tr>
                            <tr><th scope="row">Net metering</th><td>Yes</td><td>Yes</td><td>No</td></tr>
                            <tr><th scope="row">Upfront cost</th><td>Lowest</td><td>Medium to high</td><td>Highest per kWh needed</td></tr>
                            <tr><th scope="row">Best for</th><td>Daytime use, reliable grid, fastest payback</td><td>Savings plus brownout backup</td><td>Islands, farms and remote sites</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-3">Read <a href="/hybrid-pv-system-guide" class="inline">how a hybrid solar PV system works</a>, compare our <a href="/services#system-types" class="inline">system types</a>, or see the grid-tied inverters we carry from <a href="/brands/solis" class="inline">Solis</a>.</p>
            </section>

            <section class="hg-note">
                <h2>Planning a grid-tied solar PV system?</h2>
                <p>Send us your monthly bills and when you use the most power during the day, and we’ll size the array and inverter for the fastest payback, with or without net metering.</p>
                <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                    <a href="{{ Site::QUOTE_HREF }}" class="{{ Ui::button('primary', 'lg') }}">Request a Quote</a>
                    <a href="/pv-calculator" class="{{ Ui::button('secondary', 'lg') }}">Try the PV Calculator</a>
                </div>
            </section>

            <p class="border-t border-steel-200 pt-4 text-sm text-steel-600">This guide describes how typical grid-tied inverters work; exact limits, timings and settings differ by brand, model and utility, so always follow the manufacturer’s datasheet and manual, your utility’s requirements and the Philippine Electrical Code. Last updated November 2026.</p>
        </div>
    </x-container>
</x-site-layout>
