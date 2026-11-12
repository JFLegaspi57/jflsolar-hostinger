@php
    use App\Support\Site;
    use App\Support\Ui;
    $toc = [
        'short' => 'The short version', 'parts' => 'The parts of the system', 'inverter' => 'Inside the hybrid inverter',
        'flow' => 'How the power is shared', 'day' => 'A day in the life', 'modes' => 'Operating modes',
        'brownout' => 'What happens in a brownout', 'battery' => 'The battery and its BMS', 'array' => 'The solar array and MPPT',
        'protection' => 'Protection and safety', 'losses' => 'Efficiency and losses', 'compare' => 'Hybrid vs on-grid vs off-grid',
    ];
@endphp
<x-site-layout title="How a Hybrid Solar PV System Works" path="/hybrid-pv-system-guide" :reviews-above-footer="false"
    description="A technical guide to how a hybrid solar PV system works: solar array and MPPT, the hybrid inverter's converters, battery and BMS, power-flow priorities, operating modes, brownout backup, protection devices and losses.">
    <x-slot:head>
        @include('partials.guide-styles')
        @include('partials.hybrid-guide-hreflang')
    </x-slot:head>
    <x-page-header eyebrow="Technical guide" title="How a hybrid solar PV system works"
        intro="From sunlight to socket: what each part does, what happens inside the hybrid inverter, how power is shared between your home, the battery and the grid, and how the system keeps the lights on during a brownout." />
    <x-container class="py-10 sm:py-14">
        <div class="hg space-y-10 text-steel-800" style="max-width: 48rem">
            <p class="text-sm"><a href="/hybrid-pv-system-guide/tagalog" hreflang="fil" lang="fil" class="inline">Basahin ang gabay na ito sa Tagalog</a></p>

            <nav aria-label="On this page" class="hg-toc hg-note">
                <p class="font-bold text-steel-950">On this page</p>
                <ul class="mt-1 grid sm:grid-cols-2" style="column-gap: 1.5rem">
                    @foreach ($toc as $id => $label)<li><a href="#{{ $id }}">{{ $label }}</a></li>@endforeach
                </ul>
            </nav>

            <section aria-labelledby="short">
                <h2 id="short">The short version</h2>
                <p>A <strong>hybrid solar PV system</strong> combines solar panels, a battery and a connection to the utility grid, all managed by one <strong>hybrid inverter</strong>. During the day the panels power your home first, then charge the battery, and any surplus can be exported to the grid. At night the battery takes over. When the grid fails, the inverter disconnects from it and keeps your backup circuits running from the battery and panels.</p>
                <p>Technically, it is a <strong>DC-coupled</strong> system: the panels and the battery both connect to the inverter’s internal DC bus, and a single inverter stage turns that DC into 230 V, 60 Hz AC for your home.</p>
            </section>

            <section aria-labelledby="parts">
                <h2 id="parts">The parts of the system</h2>
                <div class="hg-diagram mt-4">
                    <svg viewBox="0 0 760 430" role="img" aria-labelledby="dg-title dg-desc">
                        <title id="dg-title">Hybrid solar PV system power-flow diagram</title>
                        <desc id="dg-desc">Solar panels and the battery connect on the DC side of the hybrid inverter. The inverter's AC side feeds a backup load panel directly and the main panel, which connects through the bi-directional meter to the grid. A generator can connect to the inverter's generator port. A current sensor on the grid line and the battery management system send data to the inverter.</desc>
                        {{-- PV array --}}
                        <rect class="box" x="20" y="20" width="170" height="80" rx="10"/>
                        <text class="t" x="105" y="52" text-anchor="middle">Solar PV array</text>
                        <text class="s" x="105" y="72" text-anchor="middle">strings of panels</text>
                        <text class="s" x="105" y="88" text-anchor="middle">150–550 V DC</text>
                        {{-- Battery --}}
                        <rect class="box" x="20" y="300" width="170" height="90" rx="10"/>
                        <text class="t" x="105" y="333" text-anchor="middle">Battery (LiFePO4)</text>
                        <text class="s" x="105" y="353" text-anchor="middle">48–51.2 V DC</text>
                        <text class="s" x="105" y="369" text-anchor="middle">with BMS</text>
                        {{-- Inverter --}}
                        <rect class="box-main" x="250" y="60" width="250" height="300" rx="12"/>
                        <text class="t" x="375" y="86" text-anchor="middle">Hybrid inverter</text>
                        <rect class="inner" x="270" y="100" width="210" height="44" rx="6"/>
                        <text class="s" x="375" y="118" text-anchor="middle">MPPT DC–DC converters</text>
                        <text class="s" x="375" y="134" text-anchor="middle">(one per MPPT input)</text>
                        <rect class="inner" x="270" y="160" width="210" height="36" rx="6"/>
                        <text class="s" x="375" y="183" text-anchor="middle">High-voltage DC bus (DC link)</text>
                        <rect class="inner" x="270" y="212" width="210" height="44" rx="6"/>
                        <text class="s" x="375" y="230" text-anchor="middle">Bi-directional battery</text>
                        <text class="s" x="375" y="246" text-anchor="middle">DC–DC converter</text>
                        <rect class="inner" x="270" y="272" width="210" height="44" rx="6"/>
                        <text class="s" x="375" y="290" text-anchor="middle">Bi-directional inverter bridge</text>
                        <text class="s" x="375" y="306" text-anchor="middle">+ filter, relays, controller</text>
                        <text class="s" x="375" y="345" text-anchor="middle">GRID · BACKUP · GEN ports</text>
                        {{-- AC side --}}
                        <rect class="box" x="560" y="20" width="180" height="70" rx="10"/>
                        <text class="t" x="650" y="50" text-anchor="middle">Backup load panel</text>
                        <text class="s" x="650" y="70" text-anchor="middle">essential circuits</text>
                        <rect class="box" x="560" y="150" width="180" height="70" rx="10"/>
                        <text class="t" x="650" y="180" text-anchor="middle">Main panel</text>
                        <text class="s" x="650" y="200" text-anchor="middle">other household loads</text>
                        <rect class="box" x="560" y="270" width="180" height="70" rx="10"/>
                        <text class="t" x="650" y="298" text-anchor="middle">Utility grid</text>
                        <text class="s" x="650" y="318" text-anchor="middle">via bi-directional meter</text>
                        <rect class="box" x="560" y="370" width="180" height="48" rx="10"/>
                        <text class="t" x="650" y="399" text-anchor="middle">Generator (optional)</text>
                        {{-- DC lines --}}
                        <path class="dc" d="M190 60 H220 V122 H270"/>
                        <path class="dc" d="M190 345 H220 V234 H270"/>
                        {{-- AC lines --}}
                        <path class="ac" d="M500 280 H530 V55 H560"/>
                        <path class="ac" d="M500 300 H545 V185 H560"/>
                        <path class="ac" d="M650 220 V270"/>
                        <path class="ac" d="M500 320 H520 V394 H560"/>
                        {{-- Data lines --}}
                        <path class="data" d="M105 300 V270 H240 V250 H270"/>
                        <circle cx="650" cy="245" r="9" fill="#fff" stroke="var(--color-steel-500)" stroke-width="1.5"/>
                        <text class="s" x="666" y="249">CT / meter</text>
                        <path class="data" d="M641 245 H515 V340 H500"/>
                        <text class="s" x="120" y="285" text-anchor="middle">CAN / RS485</text>
                    </svg>
                    <p class="hg-legend" style="position: sticky; left: 0"><span class="l-dc">DC power</span><span class="l-ac">AC power</span><span class="l-data">Data / measurement</span></p>
                </div>
                <div class="hg-table-wrap mt-6">
                    <table class="hg-table">
                        <caption class="sr-only">Main components of a hybrid solar PV system</caption>
                        <thead><tr><th scope="col">Part</th><th scope="col">What it does</th><th scope="col">Technical notes</th></tr></thead>
                        <tbody>
                            <tr><th scope="row">Solar PV array</th><td>Turns sunlight into DC electricity.</td><td>Panels wired in series “strings”; string voltage must stay inside the inverter’s MPPT and maximum DC voltage limits.</td></tr>
                            <tr><th scope="row">Hybrid inverter</th><td>The brain: tracks the panels’ best operating point, charges and discharges the battery, makes AC, and manages the grid and backup.</td><td>Contains MPPT converters, a DC bus, a battery converter, an inverter bridge, relays and a controller.</td></tr>
                            <tr><th scope="row">Battery + BMS</th><td>Stores surplus solar for the evening and for brownouts.</td><td>Usually 48–51.2 V LiFePO4 for homes; the BMS reports state of charge and limits to the inverter over CAN or RS485.</td></tr>
                            <tr><th scope="row">Backup load panel</th><td>Circuits that stay on during a brownout.</td><td>Fed from the inverter’s BACKUP (EPS/UPS) port; size it within the inverter’s backup rating.</td></tr>
                            <tr><th scope="row">CT or smart meter</th><td>Measures the power flowing to or from the grid.</td><td>Lets the inverter follow your load in real time and enforce export limits (including zero export).</td></tr>
                            <tr><th scope="row">Bi-directional meter</th><td>Records imported and exported energy.</td><td>Installed by your utility for <a href="/net-metering" class="inline">net metering</a>.</td></tr>
                            <tr><th scope="row">Protection devices</th><td>Keep people and equipment safe.</td><td>DC isolators, PV and AC surge protectors, breakers, battery fuse/MCCB, rapid shutdown device and earthing.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section aria-labelledby="inverter">
                <h2 id="inverter">Inside the hybrid inverter</h2>
                <p>A hybrid inverter is several power converters around a shared DC bus, coordinated by a controller that measures voltages and currents thousands of times per second.</p>
                <h3>1. MPPT DC–DC converters (solar input)</h3>
                <p>Each MPPT input has a DC–DC (usually boost) converter that adjusts the operating voltage of its string to the <strong>maximum power point</strong> (MPP), where voltage × current is highest. Because the MPP moves with sunlight and temperature, the tracker keeps nudging the voltage and checking whether power rose or fell (“perturb and observe”), and periodically sweeps the whole curve to avoid getting stuck at a false peak caused by partial shade.</p>
                <h3>2. The DC bus (DC link)</h3>
                <p>The converters feed a high-voltage DC bus held by large capacitors, typically several hundred volts so the inverter can make a 230 V AC sine wave (whose peak is about 325 V). The bus voltage is the system’s balance point: if more power flows in than out, it rises, and the controller responds by charging the battery, raising output or curtailing the panels.</p>
                <h3>3. Bi-directional battery converter</h3>
                <p>This converter moves power between the low-voltage battery and the high-voltage DC bus in both directions: <em>charging</em> (bus → battery) and <em>discharging</em> (battery → bus). In 48 V systems it is usually an isolated, high-frequency converter with a transformer to bridge the large voltage gap; high-voltage battery systems use a simpler non-isolated buck-boost stage. It follows the charge and discharge current limits sent by the battery’s BMS.</p>
                <h3>4. Bi-directional inverter bridge and filter</h3>
                <p>Semiconductor switches (IGBTs or MOSFETs) chop the DC bus voltage at high frequency using <strong>pulse-width modulation</strong> (PWM); an inductor–capacitor filter smooths the pulses into a clean sine wave. The same bridge can run in reverse as a rectifier to charge the battery from the grid or a generator.</p>
                <h3>5. Grid synchronisation, relays and the controller</h3>
                <ul class="dots">
                    <li><strong>Phase-locked loop (PLL):</strong> while grid-connected, the inverter locks its output to the grid’s voltage, frequency (60 Hz) and phase, and acts as a <em>current source</em>, pushing exactly the current it needs.</li>
                    <li><strong>Off-grid / backup:</strong> without the grid, it switches to <em>voltage-source</em> mode and creates its own 230 V, 60 Hz reference.</li>
                    <li><strong>Relays:</strong> internal relays connect or isolate the GRID, BACKUP and GEN ports. Grid-code protection (voltage and frequency limits, anti-islanding) decides when the grid relay must open.</li>
                    <li><strong>Monitoring:</strong> the controller logs everything and sends it by Wi-Fi or LAN to the manufacturer’s app.</li>
                </ul>
            </section>

            <section aria-labelledby="flow">
                <h2 id="flow">How the power is shared</h2>
                <p>At every moment the inverter balances one simple equation. Power from the panels must go somewhere: to your loads, into the battery, or out to the grid (and when the panels are short, the battery or grid fills the gap):</p>
                <p class="hg-formula">P(solar) + P(grid import) + P(battery discharge) = P(loads) + P(battery charge) + P(grid export) + losses</p>
                <p class="mt-3">In the usual <strong>self-consumption</strong> mode, the priorities are:</p>
                <ol class="steps mt-3">
                    <li><strong>Solar to loads first.</strong> Using your own solar avoids buying a full-price kWh from the utility.</li>
                    <li><strong>Surplus to the battery.</strong> Charged at up to the battery’s and inverter’s current limits.</li>
                    <li><strong>Remaining surplus to the grid</strong>, if export is allowed (and credited under net metering), or the panels are <em>curtailed</em> (the MPPT moves off the maximum power point) when export is limited to zero.</li>
                    <li><strong>When solar is short:</strong> the battery supplies the gap down to its minimum state of charge, then the grid supplies the rest.</li>
                </ol>
                <p class="mt-3">The CT or smart meter on the grid line is what makes this precise: the inverter adjusts its output every fraction of a second so that grid power stays near zero (or below your export limit).</p>
            </section>

            <section aria-labelledby="day">
                <h2 id="day">A day in the life</h2>
                <ul class="dots">
                    <li><strong>6–8 AM:</strong> panels start producing; the battery tops up what the panels cannot yet cover.</li>
                    <li><strong>8 AM–3 PM:</strong> solar covers the home’s daytime load; surplus charges the battery, then any extra is exported (or curtailed).</li>
                    <li><strong>3–6 PM:</strong> production falls; the battery begins supporting the load as solar drops below demand.</li>
                    <li><strong>6 PM–sunrise:</strong> the battery powers the home until it reaches its reserve level; the grid covers the rest.</li>
                    <li><strong>Brownout at any time:</strong> the inverter isolates from the grid and runs the backup circuits from battery and solar (see below).</li>
                </ul>
            </section>

            <section aria-labelledby="modes">
                <h2 id="modes">Operating modes</h2>
                <p>Names differ by brand, but most hybrid inverters offer:</p>
                <ul class="dots mt-3">
                    <li><strong>Self-use / self-consumption:</strong> maximise use of your own solar and battery (the default for most homes).</li>
                    <li><strong>Backup / UPS priority:</strong> keep the battery above a set reserve (for example 40–60%) so there is always energy for brownouts.</li>
                    <li><strong>Feed-in / export priority:</strong> send surplus to the grid before charging the battery; useful for some net-metering setups.</li>
                    <li><strong>Time-of-use / economic:</strong> charge or discharge in set time windows (for example, charge from the grid when it is cheap).</li>
                    <li><strong>Zero export / export limit:</strong> never send more than a set power to the grid, using the CT or meter reading.</li>
                    <li><strong>Off-grid:</strong> no grid at all; the inverter is always the voltage source and can start a generator when the battery is low.</li>
                </ul>
            </section>

            <section aria-labelledby="brownout">
                <h2 id="brownout">What happens in a brownout</h2>
                <ol class="steps">
                    <li><strong>Detection.</strong> The inverter sees grid voltage or frequency leave its limits, or detects islanding.</li>
                    <li><strong>Isolation (anti-islanding).</strong> The grid relay opens so no power is sent back into the utility’s lines, protecting line workers. This is mandatory for every grid-connected inverter.</li>
                    <li><strong>Transfer.</strong> The inverter switches to voltage-source mode and powers the BACKUP port, typically in under 10 ms (some models under 4 ms), quick enough that most appliances and computers keep running.</li>
                    <li><strong>Island operation.</strong> Battery and solar supply the backup loads. Surge capability (often 2× rated power for a few seconds) helps start motors like pumps and aircon compressors. A generator can be started if the battery runs low.</li>
                    <li><strong>Return.</strong> When the grid is stable again for a set time, the inverter re-synchronises, closes the grid relay and resumes normal operation.</li>
                </ol>
                <div class="hg-note mt-4"><p><strong>Important:</strong> only circuits wired to the backup panel stay on. Large loads should be kept within the inverter’s backup rating and the battery’s maximum discharge current.</p></div>
            </section>

            <section aria-labelledby="battery">
                <h2 id="battery">The battery and its BMS</h2>
                <ul class="dots">
                    <li><strong>Chemistry:</strong> lithium iron phosphate (LiFePO4) is the standard for homes: very stable thermally, thousands of cycles, and usable to about 90–95% depth of discharge.</li>
                    <li><strong>BMS (battery management system):</strong> balances the cells, protects against over-voltage, under-voltage, over-current and over-temperature, and estimates the <strong>state of charge</strong> (SOC).</li>
                    <li><strong>Closed-loop communication:</strong> the BMS tells the inverter, over CAN or RS485, the allowed charge voltage and the maximum charge and discharge currents in real time. That is why the battery must be on the inverter’s compatibility list.</li>
                    <li><strong>Charging:</strong> constant current (CC) until a target voltage, then constant voltage (CV) with tapering current until full.</li>
                    <li><strong>Energy and power:</strong> energy (kWh) = voltage × amp-hours; for example 51.2 V × 314 Ah ≈ 16 kWh. Power is limited by the BMS current limit, for example 200 A × 51.2 V ≈ 10 kW.</li>
                    <li><strong>Temperature:</strong> lithium batteries must not be charged below about 0 °C and age faster when hot, so install them in a cool, shaded, ventilated place.</li>
                </ul>
            </section>

            <section aria-labelledby="array">
                <h2 id="array">The solar array and MPPT</h2>
                <ul class="dots">
                    <li><strong>I–V curve:</strong> a panel’s current falls as voltage rises; power peaks at Vmp × Imp. Current follows sunlight; voltage falls as the panel heats up.</li>
                    <li><strong>String voltage:</strong> panels in series add their voltage. The string’s open-circuit voltage (Voc) on the coolest morning must stay below the inverter’s maximum DC input, and its working voltage must stay inside the MPPT range on the hottest afternoon.</li>
                    <li><strong>Current per MPPT:</strong> the string current (Imp, and Isc for safety) must suit each MPPT input’s limits.</li>
                    <li><strong>Multiple MPPTs:</strong> roof faces with different directions or shading go on separate MPPT inputs so one does not drag down the other.</li>
                    <li><strong>DC/AC oversizing:</strong> connecting more panel power than the inverter’s AC rating (for example 130–160%) raises output in the mornings, afternoons and cloudy weather; at midday peaks the inverter simply limits (“clips”) the excess.</li>
                </ul>
                <p class="hg-formula">Max. string Voc ≈ Voc(STC) × [1 + temp. coefficient(Voc) × (T(min) − 25 °C)] × number of panels</p>
            </section>

            <section aria-labelledby="protection">
                <h2 id="protection">Protection and safety</h2>
                <ul class="dots">
                    <li><strong>PV side:</strong> DC isolator, DC surge protective device (SPD), string fuses where three or more strings are paralleled, and a <a href="/brands/projoy" class="inline">rapid shutdown device</a> to de-energise roof cabling in an emergency.</li>
                    <li><strong>Battery side:</strong> a DC breaker or fuse sized for the battery current (often 125–250 A), short, heavy cables and correct lugs.</li>
                    <li><strong>AC side:</strong> breakers for the GRID, BACKUP and GEN circuits, an AC SPD, and (where required) residual-current protection.</li>
                    <li><strong>Earthing:</strong> panel frames, mounting rails, inverter and enclosures bonded to a proper earth rod; SPDs only work with good earthing.</li>
                    <li><strong>Inverter functions:</strong> anti-islanding, insulation-resistance and residual-current monitoring, and, on newer models, arc-fault detection (AFCI).</li>
                </ul>
            </section>

            <section aria-labelledby="losses">
                <h2 id="losses">Efficiency and losses</h2>
                <p>No conversion is perfect. Typical losses in a well-built system:</p>
                <div class="hg-table-wrap mt-3">
                    <table class="hg-table">
                        <caption class="sr-only">Typical losses in a hybrid solar PV system</caption>
                        <thead><tr><th scope="col">Loss</th><th scope="col">Typical size</th><th scope="col">How to reduce it</th></tr></thead>
                        <tbody>
                            <tr><th scope="row">Panel temperature</th><td>8–15% at midday in the Philippines</td><td>Panels with a low temperature coefficient; airflow under the panels.</td></tr>
                            <tr><th scope="row">Dust and soiling</th><td>2–5%</td><td>Regular cleaning, especially in the dry season.</td></tr>
                            <tr><th scope="row">Shading and mismatch</th><td>0–10%+</td><td>Good layout, separate MPPTs, avoiding shade.</td></tr>
                            <tr><th scope="row">DC and AC cabling</th><td>1–2%</td><td>Correct cable sizes and short runs.</td></tr>
                            <tr><th scope="row">Inverter conversion</th><td>2–4% (95–98% efficient)</td><td>Choose a quality inverter sized to the load.</td></tr>
                            <tr><th scope="row">Battery round trip</th><td>5–10% of energy stored</td><td>Use solar directly when possible; store only the surplus.</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-3">This is why <strong>using solar power directly</strong> is always the most efficient path, and why systems are sized around your daytime use first.</p>
            </section>

            <section aria-labelledby="compare">
                <h2 id="compare">Hybrid vs on-grid vs off-grid</h2>
                <div class="hg-table-wrap">
                    <table class="hg-table">
                        <caption class="sr-only">Comparison of hybrid, on-grid and off-grid solar PV systems</caption>
                        <thead><tr><th scope="col"></th><th scope="col">On-grid</th><th scope="col">Hybrid</th><th scope="col">Off-grid</th></tr></thead>
                        <tbody>
                            <tr><th scope="row">Battery</th><td>None</td><td>Yes</td><td>Yes (essential)</td></tr>
                            <tr><th scope="row">Power in a brownout</th><td>No (shuts down by law)</td><td>Yes, on backup circuits</td><td>Yes, no grid involved</td></tr>
                            <tr><th scope="row">Net metering</th><td>Yes</td><td>Yes</td><td>No</td></tr>
                            <tr><th scope="row">Upfront cost</th><td>Lowest</td><td>Medium to high</td><td>Highest per kWh needed</td></tr>
                            <tr><th scope="row">Best for</th><td>Daytime use, reliable grid</td><td>Homes and businesses wanting savings and backup</td><td>Islands, farms and remote sites</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-3">Read <a href="/grid-tied-pv-system-guide" class="inline">how a grid-tied solar PV system works</a>, see our <a href="/services#system-types" class="inline">system types</a>, the <a href="/packages?type=complete" class="inline">complete hybrid packages</a> and the inverter brands we carry, such as <a href="/brands/goodwe" class="inline">GoodWe</a>, <a href="/brands/solis" class="inline">Solis</a> and <a href="/brands/sungrow" class="inline">Sungrow</a>.</p>
            </section>

            <section class="hg-note">
                <h2>Planning a hybrid solar PV system?</h2>
                <p>Send us your monthly bill and the appliances you want running during brownouts, and we’ll design the array, inverter, battery and protection for your home or business.</p>
                <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                    <a href="{{ Site::QUOTE_HREF }}" class="{{ Ui::button('primary', 'lg') }}">Request a Quote</a>
                    <a href="/pv-calculator" class="{{ Ui::button('secondary', 'lg') }}">Try the PV Calculator</a>
                </div>
            </section>

            <p class="border-t border-steel-200 pt-4 text-sm text-steel-600">This guide describes how typical residential hybrid inverters work; exact limits, timings and mode names differ by brand and model, so always follow the manufacturer’s datasheet and manual and the Philippine Electrical Code. Last updated November 2026.</p>
        </div>
    </x-container>
</x-site-layout>
