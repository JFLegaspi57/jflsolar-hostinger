@php
    // Key figures of the GoodWe models we carry (from their product pages / GoodWe datasheets).
    $esModels = [
        // [model, rated power, max solar, battery current, backup peak (10 s), weight]
        ['GW6000-ES-C10', '6 kW', '12 kW', '140 A', '12 kVA', '15.5 kg'],
        ['GW8000-ES-C10', '8 kW', '16 kW', '160 A', '16 kVA', '27 kg'],
        ['GW10K-ES-C10', '10 kW', '20 kW', '200 A', '20 kVA', '29 kg'],
        ['GW12K-ES-C10', '12 kW', '24 kW', '240 A', '24 kVA', '29 kg'],
    ];
@endphp
<x-brand-article :slug="$article['slug']" :brand="$article['brand']" :filter="$article['filter']" :products="$products" :settings="$settings"
    title="GoodWe inverters and batteries"
    intro="Who GoodWe is, what they make, and how to choose the GoodWe hybrid inverter and battery that fit your home or business."
    description="Everything about GoodWe: who they are, what they make, GoodWe in the Philippines, the ES-C10 hybrid inverters and Lynx batteries we carry, warranty and how to choose a size."
    :toc="[
        'who' => 'Who is GoodWe?', 'makes' => 'What GoodWe makes', 'philippines' => 'GoodWe in the Philippines',
        'es-c10' => 'The GoodWe ES-C10 hybrid inverters', 'lynx' => 'The Lynx A G4 battery', 'size' => 'Choosing a size',
        'warranty' => 'Warranty and support',
    ]">
            <section aria-labelledby="who">
                <h2 id="who">Who is GoodWe?</h2>
                <p>GoodWe Technologies is a Chinese manufacturer of solar inverters, batteries and energy-management products. It was founded in 2010 in Suzhou, China, by Daniel Huang, starting with fewer than ten people, and released its first solar inverter in 2011. Suzhou is still its headquarters.</p>
                <p>The <strong>inverter</strong> is the brain of a solar PV system. Solar panels produce direct current (DC), but your appliances run on alternating current (AC). The inverter converts one into the other, and a <em>hybrid</em> inverter also decides, second by second, whether power goes to your loads, into the battery or out to the grid, and switches your home to backup during a brownout.</p>
                <dl class="ba-facts mt-6">
                    <div><dt>Founded</dt><dd>2010<small>Suzhou, China</small></dd></div>
                    <div><dt>Stock listing</dt><dd>Shanghai Stock Exchange<small>STAR Market since 2020, code 688390</small></dd></div>
                    <div><dt>Installed worldwide</dt><dd>Over 100 GW<small>in more than 100 countries (2025)</small></dd></div>
                    <div><dt>People</dt><dd>About 6,000<small>over 1,000 of them in research and development (2025)</small></dd></div>
                    <div><dt>Industry rating</dt><dd>BloombergNEF Tier 1<small>global inverter manufacturer, Q3 2026</small></dd></div>
                    <div><dt>Patents</dt><dd>Over 545<small>as reported in 2025</small></dd></div>
                </dl>
                <p class="mt-6">GoodWe began with grid-tie inverters and has grown into hybrid inverters, home and commercial batteries, all-in-one storage systems, solar roofing (BIPV) and energy-management software. Today it describes its aim as connecting generation, the grid, household loads, storage and smart controls into one system.</p>
                <x-brand-tier :slug="$article['slug']" />
            </section>

            <section aria-labelledby="makes">
                <h2 id="makes">What GoodWe makes</h2>
                <div class="ba-family">
                    <h3>Grid-tie (on-grid) inverters</h3>
                    <p>Inverters without a battery that feed solar power into your home and the grid. The range goes from small single-phase home units to three-phase commercial inverters and utility inverters of up to 250 kW.</p>
                </div>
                <div class="ba-family">
                    <h3>Hybrid inverters</h3>
                    <p>Solar plus battery in one unit, with backup power during brownouts. The main families are:</p>
                    <ul class="dots mt-2">
                        <li><strong>ES series</strong>: single-phase, for <strong>48 V low-voltage</strong> batteries. This is the family we carry (the ES-C10, below).</li>
                        <li><strong>EH series</strong>: single-phase, for high-voltage batteries.</li>
                        <li><strong>ET series</strong>: three-phase, for high-voltage batteries, for larger homes and commercial sites.</li>
                    </ul>
                </div>
                <div class="ba-family">
                    <h3>Off-grid inverters</h3>
                    <p>The <strong>EO series</strong> runs a home or small business with no grid connection at all, with a generator input and up to 10 units in parallel. GoodWe launched the EO G2 in the Philippines in September 2026 for islands and areas with a weak grid.</p>
                </div>
                <div class="ba-family">
                    <h3>Batteries</h3>
                    <p>The <strong>Lynx</strong> family of lithium iron phosphate (LiFePO4) batteries, in low-voltage versions for 48 V inverters such as the ES series, and high-voltage stackable versions for the EH and ET series.</p>
                </div>
                <div class="ba-family">
                    <h3>Monitoring</h3>
                    <p>GoodWe inverters connect by Wi-Fi, LAN or Bluetooth to GoodWe’s SEMS Portal app, so you can see on your phone how much your panels produce, how full the battery is and how much you draw from the grid.</p>
                </div>
                <div class="ba-note mt-5">
                    <h3>Low-voltage or high-voltage battery?</h3>
                    <p><strong>Low-voltage (48–51.2 V)</strong> batteries are the most common type for homes in the Philippines. They are easy to expand and many brands are available. <strong>High-voltage</strong> batteries stack modules in series for a few hundred volts, which means lower current and slightly higher efficiency, but the battery must match the inverter brand. The inverter decides which kind you can use.</p>
                </div>
            </section>

            <section aria-labelledby="philippines">
                <h2 id="philippines">GoodWe in the Philippines</h2>
                <ul class="dots">
                    <li>GoodWe has a Philippine sales team and provides sales, pre-sales and after-sales service here, with four to five installer training sessions a month on installation, commissioning and servicing.</li>
                    <li>In August 2023 GoodWe held a PV Technical Roadshow in the Philippines together with Jinko Solar and Clenergy, presenting its commercial and utility inverters to installers and distributors.</li>
                    <li>In September 2026 it launched the EO G2 off-grid inverter here, aimed at the country’s islands and areas where the grid is weak or absent.</li>
                </ul>
                <p class="mt-3">For you, this means spare parts, warranty service and trained installers are available locally, not only through the factory in China.</p>
            </section>

            <section aria-labelledby="es-c10">
                <h2 id="es-c10">The GoodWe ES-C10 hybrid inverters</h2>
                <p>The ES-C10 is GoodWe’s single-phase hybrid inverter for 48 V batteries, and it is the GoodWe inverter we stock and install. It comes in four sizes:</p>
                <div class="ba-table-wrap mt-4">
                    <table class="ba-table">
                        <caption class="sr-only">Key figures of the GoodWe ES-C10 models</caption>
                        <thead>
                            <tr><th scope="col">Model</th><th scope="col">Rated power</th><th scope="col">Max. solar panels</th><th scope="col">Battery current</th><th scope="col">Backup peak (10 s)</th><th scope="col">Weight</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($esModels as $m)
                                <tr><th scope="row">{{ $m[0] }}</th>@foreach (array_slice($m, 1) as $v)<td>{{ $v }}</td>@endforeach</tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 text-sm text-steel-600">Battery current is the most the inverter can charge or draw from a 48 V battery. The 6 kW model has no fan (silent); the 8, 10 and 12 kW models use a smart fan that runs only when needed.</p>
                <h3 class="mt-6">What the features mean for you</h3>
                <ul class="dots">
                    <li><strong>Up to 200% solar oversizing.</strong> You can connect twice the inverter’s rating in panels, for example 12 kW of panels on the 6 kW model. You get more energy in the early morning, late afternoon and on cloudy days, when panels produce less than their rating.</li>
                    <li><strong>Backup switching in under 4 milliseconds.</strong> When the grid goes down, the inverter switches to the battery so fast that lights don’t flicker and computers and Wi-Fi stay on, much like a UPS.</li>
                    <li><strong>Works with lithium and lead-acid 48 V batteries.</strong> A lithium battery talks to the inverter over a communication cable (CAN), so the inverter knows exactly how full it is and charges it safely.</li>
                    <li><strong>Generator input.</strong> You can connect a generator, and the inverter manages it together with solar and battery.</li>
                    <li><strong>Room to grow.</strong> Up to 6 inverters can run in parallel, on-grid or off-grid, if your needs grow.</li>
                    <li><strong>Built for heat and rain.</strong> IP66 rated (dust-tight and protected against strong water jets) and rated from −35 to 60 °C. For the longest life we still mount it out of direct sun and rain.</li>
                    <li><strong>Efficient.</strong> Up to 97.6% conversion efficiency, so very little of your solar power is lost as heat.</li>
                    <li><strong>Easy to check.</strong> A touch screen on the unit, plus Wi-Fi, LAN and Bluetooth for the app.</li>
                    <li><strong>Grid voltage.</strong> Works on the Philippine 220–240 V, 60 Hz single-phase supply.</li>
                </ul>
            </section>

            <section aria-labelledby="lynx">
                <h2 id="lynx">The Lynx A G4 battery</h2>
                <p>The GoodWe Lynx A G4 (GW16.1-BAT-LV-G10) is a 51.2 V low-voltage LiFePO4 battery made for 48 V hybrid inverters such as the ES-C10.</p>
                <ul class="dots mt-3">
                    <li><strong>16.1 kWh</strong> rated energy, at least 14.9 kWh usable, from 314 Ah cells.</li>
                    <li>Up to <strong>10 kW</strong> continuous output and 16 kW peak for 15 seconds, enough to start motors such as aircon compressors and water pumps.</li>
                    <li>Rated for <strong>10,000 or more cycles</strong> under GoodWe’s test conditions.</li>
                    <li><strong>IP65</strong>, wall- or floor-mounted, indoors or outdoors.</li>
                    <li>Up to <strong>30 batteries in parallel</strong>, so you can start with one and add more later.</li>
                    <li>Safety standards: IEC 62619, IEC 60730, IEC 63056 and CE; transport tested to UN38.3.</li>
                </ul>
                <p class="mt-3">LiFePO4 (lithium iron phosphate) is the safest common lithium chemistry for homes: it is very resistant to overheating and lasts many more cycles than lead-acid batteries.</p>
            </section>

            <section aria-labelledby="size">
                <h2 id="size">Choosing a size</h2>
                <p>A hybrid system has three parts to size, and each answers a different question:</p>
                <ul class="dots mt-3">
                    <li><strong>Inverter (kW):</strong> how much you want running <em>at the same time</em>, especially during a brownout. Add up the appliances you want on backup, for example aircon, refrigerator, lights and pump, and leave some headroom for motors starting.</li>
                    <li><strong>Solar panels (kWp):</strong> how much energy you use per month (kWh on your electric bill). With the ES-C10 you can install up to twice the inverter rating in panels.</li>
                    <li><strong>Battery (kWh):</strong> how much you use after sunset, and how long you want backup during a brownout.</li>
                </ul>
                <p class="mt-3">Our free <a href="/pv-calculator" class="inline">PV Calculator</a> gives a first estimate from your monthly bill, and our <a href="/packages" class="inline">packages</a> show complete GoodWe systems. For an exact design, send us your bill and the appliances you want on backup.</p>
            </section>

            <section aria-labelledby="warranty">
                <h2 id="warranty">Warranty and support</h2>
                <ul class="dots">
                    <li>The GoodWe ES-C10 inverters we sell carry GoodWe’s <strong>5-year replacement warranty</strong>. The warranty for each product is shown on its product page.</li>
                    <li>GoodWe offers paid warranty extensions through its online warranty portal.</li>
                    <li>Every customer who buys from us gets our free technical support, from installation and commissioning to troubleshooting, and we coordinate any warranty claim for you. See <a href="/faq" class="inline">how warranty claims work</a>.</li>
                </ul>
            </section>

    <x-slot:questions>
                <div class="ba-qa">
                    <h3>Can I use a battery from another brand?</h3>
                    <p>Yes. The ES-C10 works with 48 V lithium and lead-acid batteries. A lithium battery should be on GoodWe’s compatibility list so that it can communicate with the inverter. Ask us which batteries we have matched with it.</p>
                </div>
                <div class="ba-qa">
                    <h3>Will I have power during a brownout?</h3>
                    <p>Yes, with a battery. The loads connected to the inverter’s backup output switch to the battery in under 4 milliseconds and keep running, and during the day the panels keep charging the battery.</p>
                </div>
                <div class="ba-qa">
                    <h3>Can I join net metering with a GoodWe hybrid inverter?</h3>
                    <p>Yes. The ES-C10 is grid-connected, so a system with it can apply for net metering. See our <a href="/net-metering" class="inline">net metering guide</a>.</p>
                </div>
                <div class="ba-qa">
                    <h3>Can I start small and expand later?</h3>
                    <p>Yes. Choose the inverter for your future backup load, then add panels up to twice its rating and add batteries over time (up to 30 Lynx A G4 in parallel).</p>
                </div>
                <div class="ba-qa">
                    <h3>Can I check my system from my phone?</h3>
                    <p>Yes. The ES-C10 has built-in Wi-Fi, LAN and Bluetooth and connects to GoodWe’s SEMS Portal app.</p>
                </div>
    </x-slot:questions>

    <x-slot:sources>GoodWe, <a href="https://en.goodwe.com/au/company-history" target="_blank" rel="noopener" class="inline">company history</a>,
                <a href="https://en.goodwe.com/goodwe-expands-eo-g2-off-grid-solution-to-the-philippines-supporting-energy-resilience-for-homes-and-businesses" target="_blank" rel="noopener" class="inline">EO G2 launch in the Philippines</a>,
                <a href="https://en.goodwe.com/goodwe-presents-tailored-solutions-in-the-philippines-at-pv-technical-roadshow-energizing-renewable-energy-advancements" target="_blank" rel="noopener" class="inline">Philippines PV Technical Roadshow</a> and
                <a href="https://en.goodwe.com/warranty" target="_blank" rel="noopener" class="inline">warranty</a>;
                pv magazine, <a href="https://www.pv-magazine.com/2025/11/07/a-new-blueprint-for-the-power-system-from-goodwe" target="_blank" rel="noopener" class="inline">A new blueprint for the power system from GoodWe</a> (November 2025);
                <a href="https://pes.eu.com/press-releases/goodwe-celebrates-15-years-with-international-summit-and-strategy-milestone" target="_blank" rel="noopener" class="inline">GoodWe celebrates 15 years</a> (PES, 2025); GoodWe datasheets for the ES-C10 and Lynx A G4.</x-slot:sources>
</x-brand-article>
