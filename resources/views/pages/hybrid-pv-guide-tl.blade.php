@php
    use App\Support\Site;
    use App\Support\Ui;
    // Tagalog version of pages/hybrid-pv-guide.blade.php: same sections and ids. Technical terms stay in English, as Filipino installers use them.
    $toc = [
        'short' => 'Ang maikling paliwanag', 'parts' => 'Mga bahagi ng sistema', 'inverter' => 'Sa loob ng hybrid inverter',
        'flow' => 'Paano hinahati ang kuryente', 'day' => 'Isang araw ng sistema', 'modes' => 'Mga operating mode',
        'brownout' => 'Ano ang nangyayari kapag brownout', 'battery' => 'Ang battery at ang BMS nito', 'array' => 'Ang solar array at MPPT',
        'protection' => 'Proteksyon at kaligtasan', 'losses' => 'Efficiency at mga loss', 'compare' => 'Hybrid vs on-grid vs off-grid',
    ];
@endphp
<x-site-layout title="Paano Gumagana ang Hybrid Solar PV System" path="/hybrid-pv-system-guide/tagalog" lang="fil" :reviews-above-footer="false"
    description="Teknikal na gabay sa Tagalog kung paano gumagana ang hybrid solar PV system: solar array at MPPT, mga converter sa loob ng hybrid inverter, battery at BMS, priority ng power flow, operating mode, backup kapag brownout, proteksyon at mga loss.">
    <x-slot:head>
        @include('partials.guide-styles')
        @include('partials.hybrid-guide-hreflang')
    </x-slot:head>
    <x-page-header eyebrow="Teknikal na gabay" title="Paano gumagana ang hybrid solar PV system"
        intro="Mula sa sikat ng araw hanggang sa saksakan: ano ang ginagawa ng bawat bahagi, ano ang nangyayari sa loob ng hybrid inverter, paano hinahati ang kuryente sa bahay mo, sa battery at sa grid, at paano nananatiling may ilaw kapag brownout." />
    <x-container class="py-10 sm:py-14">
        <div class="hg space-y-10 text-steel-800" style="max-width: 48rem">
            <p class="text-sm"><a href="/hybrid-pv-system-guide" hreflang="en" lang="en" class="inline">Read this guide in English</a></p>

            <nav aria-label="Sa pahinang ito" class="hg-toc hg-note">
                <p class="font-bold text-steel-950">Sa pahinang ito</p>
                <ul class="mt-1 grid sm:grid-cols-2" style="column-gap: 1.5rem">
                    @foreach ($toc as $id => $label)<li><a href="#{{ $id }}">{{ $label }}</a></li>@endforeach
                </ul>
            </nav>

            <section aria-labelledby="short">
                <h2 id="short">Ang maikling paliwanag</h2>
                <p>Pinagsasama ng <strong>hybrid solar PV system</strong> ang mga solar panel, isang battery at koneksyon sa utility grid, at iisang <strong>hybrid inverter</strong> ang namamahala sa lahat ng ito. Sa araw, inuuna ng mga panel na paandarin ang bahay mo, saka nila chine-charge ang battery, at ang sobra ay puwedeng i-export sa grid. Sa gabi, ang battery naman ang sumasalo. Kapag nawalan ng kuryente ang grid, humihiwalay ang inverter dito at patuloy na pinapaandar ang mga backup circuit mula sa battery at sa mga panel.</p>
                <p>Sa teknikal na paliwanag, ito ay <strong>DC-coupled</strong> na sistema: parehong nakakonekta ang mga panel at ang battery sa internal DC bus ng inverter, at iisang inverter stage ang nagko-convert ng DC na iyon sa 230 V, 60 Hz na AC para sa bahay mo.</p>
            </section>

            <section aria-labelledby="parts">
                <h2 id="parts">Mga bahagi ng sistema</h2>
                <div class="hg-diagram mt-4">
                    <svg viewBox="0 0 760 430" role="img" aria-labelledby="dg-title dg-desc">
                        <title id="dg-title">Diagram ng daloy ng kuryente sa hybrid solar PV system</title>
                        <desc id="dg-desc">Ang mga solar panel at ang battery ay nakakonekta sa DC side ng hybrid inverter. Mula sa AC side ng inverter, direktang pinapakain ang backup load panel at ang main panel, na nakakonekta sa grid sa pamamagitan ng bi-directional meter. Puwedeng ikabit ang generator sa generator port ng inverter. Nagpapadala ng data sa inverter ang current sensor sa linya ng grid at ang battery management system.</desc>
                        {{-- PV array --}}
                        <rect class="box" x="20" y="20" width="170" height="80" rx="10"/>
                        <text class="t" x="105" y="52" text-anchor="middle">Solar PV array</text>
                        <text class="s" x="105" y="72" text-anchor="middle">mga string ng panel</text>
                        <text class="s" x="105" y="88" text-anchor="middle">150–550 V DC</text>
                        {{-- Battery --}}
                        <rect class="box" x="20" y="300" width="170" height="90" rx="10"/>
                        <text class="t" x="105" y="333" text-anchor="middle">Battery (LiFePO4)</text>
                        <text class="s" x="105" y="353" text-anchor="middle">48–51.2 V DC</text>
                        <text class="s" x="105" y="369" text-anchor="middle">may BMS</text>
                        {{-- Inverter --}}
                        <rect class="box-main" x="250" y="60" width="250" height="300" rx="12"/>
                        <text class="t" x="375" y="86" text-anchor="middle">Hybrid inverter</text>
                        <rect class="inner" x="270" y="100" width="210" height="44" rx="6"/>
                        <text class="s" x="375" y="118" text-anchor="middle">MPPT DC–DC converter</text>
                        <text class="s" x="375" y="134" text-anchor="middle">(isa bawat MPPT input)</text>
                        <rect class="inner" x="270" y="160" width="210" height="36" rx="6"/>
                        <text class="s" x="375" y="183" text-anchor="middle">High-voltage DC bus (DC link)</text>
                        <rect class="inner" x="270" y="212" width="210" height="44" rx="6"/>
                        <text class="s" x="375" y="230" text-anchor="middle">Bi-directional battery</text>
                        <text class="s" x="375" y="246" text-anchor="middle">DC–DC converter</text>
                        <rect class="inner" x="270" y="272" width="210" height="44" rx="6"/>
                        <text class="s" x="375" y="290" text-anchor="middle">Bi-directional inverter bridge</text>
                        <text class="s" x="375" y="306" text-anchor="middle">+ filter, relay, controller</text>
                        <text class="s" x="375" y="345" text-anchor="middle">GRID · BACKUP · GEN port</text>
                        {{-- AC side --}}
                        <rect class="box" x="560" y="20" width="180" height="70" rx="10"/>
                        <text class="t" x="650" y="50" text-anchor="middle">Backup load panel</text>
                        <text class="s" x="650" y="70" text-anchor="middle">mahahalagang circuit</text>
                        <rect class="box" x="560" y="150" width="180" height="70" rx="10"/>
                        <text class="t" x="650" y="180" text-anchor="middle">Main panel</text>
                        <text class="s" x="650" y="200" text-anchor="middle">iba pang load sa bahay</text>
                        <rect class="box" x="560" y="270" width="180" height="70" rx="10"/>
                        <text class="t" x="650" y="298" text-anchor="middle">Utility grid</text>
                        <text class="s" x="650" y="318" text-anchor="middle">sa bi-directional meter</text>
                        <rect class="box" x="560" y="370" width="180" height="48" rx="10"/>
                        <text class="t" x="650" y="399" text-anchor="middle">Generator (opsyonal)</text>
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
                    <p class="hg-legend" style="position: sticky; left: 0"><span class="l-dc">DC na kuryente</span><span class="l-ac">AC na kuryente</span><span class="l-data">Data / sukat</span></p>
                </div>
                <div class="hg-table-wrap mt-6">
                    <table class="hg-table">
                        <caption class="sr-only">Mga pangunahing bahagi ng hybrid solar PV system</caption>
                        <thead><tr><th scope="col">Bahagi</th><th scope="col">Ano ang ginagawa</th><th scope="col">Teknikal na detalye</th></tr></thead>
                        <tbody>
                            <tr><th scope="row">Solar PV array</th><td>Ginagawang DC na kuryente ang sikat ng araw.</td><td>Naka-series ang mga panel sa mga “string”; dapat manatili ang string voltage sa loob ng MPPT range at maximum DC voltage ng inverter.</td></tr>
                            <tr><th scope="row">Hybrid inverter</th><td>Ang utak ng sistema: hinahanap ang pinakamainam na operating point ng mga panel, nagcha-charge at nagdi-discharge ng battery, gumagawa ng AC, at namamahala sa grid at sa backup.</td><td>Binubuo ng mga MPPT converter, DC bus, battery converter, inverter bridge, mga relay at controller.</td></tr>
                            <tr><th scope="row">Battery + BMS</th><td>Iniipon ang sobrang solar para sa gabi at para sa brownout.</td><td>Karaniwang 48–51.2 V LiFePO4 para sa bahay; ipinapaalam ng BMS sa inverter ang state of charge at mga limit nito sa pamamagitan ng CAN o RS485.</td></tr>
                            <tr><th scope="row">Backup load panel</th><td>Mga circuit na hindi namamatay kapag brownout.</td><td>Pinapakain mula sa BACKUP (EPS/UPS) port ng inverter; dapat pasok ito sa backup rating ng inverter.</td></tr>
                            <tr><th scope="row">CT o smart meter</th><td>Sinusukat ang kuryenteng pumapasok mula sa grid o lumalabas papunta rito.</td><td>Dahil dito, nasusundan ng inverter ang load mo nang real time at naipapatupad ang export limit (kasama ang zero export).</td></tr>
                            <tr><th scope="row">Bi-directional meter</th><td>Itinatala ang enerhiyang na-import at na-export.</td><td>Ikinakabit ng utility mo para sa <a href="/net-metering" class="inline">net metering</a>.</td></tr>
                            <tr><th scope="row">Mga protection device</th><td>Pinapanatiling ligtas ang tao at ang kagamitan.</td><td>DC isolator, PV at AC surge protector, breaker, battery fuse/MCCB, rapid shutdown device at earthing.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section aria-labelledby="inverter">
                <h2 id="inverter">Sa loob ng hybrid inverter</h2>
                <p>Ang hybrid inverter ay ilang power converter na nakapalibot sa iisang DC bus, at pinag-uugnay ng isang controller na sumusukat ng voltage at current nang libu-libong beses bawat segundo.</p>
                <h3>1. MPPT DC–DC converter (solar input)</h3>
                <p>Bawat MPPT input ay may DC–DC converter (kadalasang boost) na nag-aayos ng operating voltage ng string nito papunta sa <strong>maximum power point</strong> (MPP), kung saan pinakamataas ang voltage × current. Dahil gumagalaw ang MPP depende sa sikat ng araw at temperatura, paulit-ulit na tinutulak nang kaunti ng tracker ang voltage at tinitingnan kung tumaas o bumaba ang power (“perturb and observe”). Paminsan-minsan, sinusuyod din nito ang buong curve para hindi maipit sa maling peak na dulot ng bahagyang lilim (partial shade).</p>
                <h3>2. Ang DC bus (DC link)</h3>
                <p>Ipinapasok ng mga converter ang power sa isang high-voltage DC bus na hawak ng malalaking capacitor. Karaniwang ilang daang volt ito para makagawa ang inverter ng 230 V na AC sine wave (na ang peak ay mga 325 V). Ang bus voltage ang timbangan ng buong sistema: kapag mas maraming power ang pumapasok kaysa lumalabas, tumataas ito, at tumutugon ang controller sa pamamagitan ng pag-charge ng battery, pagtaas ng output o pagbawas (curtail) sa mga panel.</p>
                <h3>3. Bi-directional battery converter</h3>
                <p>Inililipat ng converter na ito ang power sa pagitan ng low-voltage na battery at ng high-voltage na DC bus, pabalik-balik: <em>charging</em> (bus → battery) at <em>discharging</em> (battery → bus). Sa 48 V na sistema, kadalasan itong isolated, high-frequency converter na may transformer para tawirin ang malaking agwat ng voltage; ang mga high-voltage battery system naman ay gumagamit ng mas simpleng non-isolated buck-boost stage. Sinusunod nito ang charge at discharge current limit na ipinapadala ng BMS ng battery.</p>
                <h3>4. Bi-directional inverter bridge at filter</h3>
                <p>Hinahati-hati ng mga semiconductor switch (IGBT o MOSFET) ang DC bus voltage sa napakataas na frequency gamit ang <strong>pulse-width modulation</strong> (PWM); pinapakinis naman ng inductor–capacitor filter ang mga pulse para maging malinis na sine wave. Puwede ring baligtarin ang takbo ng parehong bridge bilang rectifier para i-charge ang battery mula sa grid o sa generator.</p>
                <h3>5. Grid synchronisation, mga relay at ang controller</h3>
                <ul class="dots">
                    <li><strong>Phase-locked loop (PLL):</strong> habang nakakonekta sa grid, ikinakandado (lock) ng inverter ang output nito sa voltage, frequency (60 Hz) at phase ng grid, at kumikilos ito bilang <em>current source</em> na nagtutulak ng eksaktong current na kailangan.</li>
                    <li><strong>Off-grid / backup:</strong> kapag walang grid, lumilipat ito sa <em>voltage-source</em> mode at gumagawa ng sarili nitong 230 V, 60 Hz na reference.</li>
                    <li><strong>Mga relay:</strong> ikinokonekta o inihihiwalay ng mga internal relay ang GRID, BACKUP at GEN port. Ang grid-code protection (voltage at frequency limit, anti-islanding) ang nagpapasya kung kailan dapat bumukas ang grid relay.</li>
                    <li><strong>Monitoring:</strong> itinatala ng controller ang lahat at ipinapadala ito sa app ng manufacturer sa pamamagitan ng Wi-Fi o LAN.</li>
                </ul>
            </section>

            <section aria-labelledby="flow">
                <h2 id="flow">Paano hinahati ang kuryente</h2>
                <p>Sa bawat sandali, binabalanse ng inverter ang isang simpleng equation. Kailangang may mapuntahan ang power mula sa mga panel: sa mga load mo, sa battery, o palabas sa grid (at kapag kulang ang solar, ang battery o ang grid ang pumupuno sa kulang):</p>
                <p class="hg-formula">P(solar) + P(import mula sa grid) + P(discharge ng battery) = P(load) + P(charge ng battery) + P(export sa grid) + loss</p>
                <p class="mt-3">Sa karaniwang <strong>self-consumption</strong> mode, ito ang pagkakasunod-sunod ng prayoridad:</p>
                <ol class="steps mt-3">
                    <li><strong>Solar muna sa mga load.</strong> Kapag sarili mong solar ang ginamit, hindi mo na kailangang bumili ng kWh sa buong presyo mula sa utility.</li>
                    <li><strong>Ang sobra, sa battery.</strong> Chine-charge ito hanggang sa current limit ng battery at ng inverter.</li>
                    <li><strong>Ang natitirang sobra, sa grid</strong>, kung pinapayagan ang export (at kinikredito ito sa ilalim ng net metering). Kapag zero ang export limit, <em>kinu-curtail</em> ang mga panel (inilalayo ng MPPT ang operating point mula sa maximum power point).</li>
                    <li><strong>Kapag kulang ang solar:</strong> ang battery ang pumupuno sa kulang hanggang sa minimum state of charge nito, saka ang grid ang magsusuplay ng natitira.</li>
                </ol>
                <p class="mt-3">Ang CT o smart meter sa linya ng grid ang nagpapatumpak dito: inaayos ng inverter ang output nito bawat bahagi ng segundo para manatiling halos zero ang power mula sa grid (o mas mababa sa export limit mo).</p>
            </section>

            <section aria-labelledby="day">
                <h2 id="day">Isang araw ng sistema</h2>
                <ul class="dots">
                    <li><strong>6–8 AM:</strong> nagsisimulang mag-produce ang mga panel; ang battery ang nagdadagdag sa hindi pa kaya ng mga panel.</li>
                    <li><strong>8 AM–3 PM:</strong> sakop ng solar ang load ng bahay sa araw; ang sobra ay napupunta sa battery, at ang labis pa ay ine-export (o kinu-curtail).</li>
                    <li><strong>3–6 PM:</strong> bumababa ang produksyon; nagsisimulang tumulong ang battery kapag mas mababa na ang solar kaysa sa demand.</li>
                    <li><strong>6 PM hanggang pagsikat ng araw:</strong> ang battery ang nagpapaandar sa bahay hanggang umabot ito sa reserve level; ang grid ang sasalo sa natitira.</li>
                    <li><strong>Brownout anumang oras:</strong> humihiwalay ang inverter sa grid at pinapaandar ang mga backup circuit mula sa battery at solar (tingnan sa ibaba).</li>
                </ul>
            </section>

            <section aria-labelledby="modes">
                <h2 id="modes">Mga operating mode</h2>
                <p>Iba-iba ang tawag depende sa brand, pero karamihan ng hybrid inverter ay may:</p>
                <ul class="dots mt-3">
                    <li><strong>Self-use / self-consumption:</strong> sulitin ang sarili mong solar at battery (ang default para sa karamihan ng bahay).</li>
                    <li><strong>Backup / UPS priority:</strong> panatilihin ang battery sa itaas ng nakatakdang reserve (halimbawa 40–60%) para laging may enerhiya para sa brownout.</li>
                    <li><strong>Feed-in / export priority:</strong> ipadala muna sa grid ang sobra bago i-charge ang battery; kapaki-pakinabang sa ilang net-metering setup.</li>
                    <li><strong>Time-of-use / economic:</strong> mag-charge o mag-discharge sa mga nakatakdang oras (halimbawa, mag-charge mula sa grid kapag mura ang kuryente).</li>
                    <li><strong>Zero export / export limit:</strong> huwag kailanman magpadala sa grid nang higit sa nakatakdang power, batay sa reading ng CT o meter.</li>
                    <li><strong>Off-grid:</strong> walang grid; laging ang inverter ang voltage source, at kaya nitong paandarin ang generator kapag mababa na ang battery.</li>
                </ul>
            </section>

            <section aria-labelledby="brownout">
                <h2 id="brownout">Ano ang nangyayari kapag brownout</h2>
                <ol class="steps">
                    <li><strong>Pagtukoy (detection).</strong> Nakikita ng inverter na lumabas sa limit ang voltage o frequency ng grid, o may natukoy itong islanding.</li>
                    <li><strong>Paghiwalay (anti-islanding).</strong> Bumubukas ang grid relay para walang kuryenteng maibalik sa mga linya ng utility, bilang proteksyon sa mga lineman. Mandatory ito sa bawat grid-connected inverter.</li>
                    <li><strong>Paglipat (transfer).</strong> Lumilipat ang inverter sa voltage-source mode at pinapaandar ang BACKUP port, karaniwang sa loob ng wala pang 10 ms (ang ilang model ay wala pang 4 ms). Sapat ang bilis na ito para tuloy-tuloy ang takbo ng karamihan ng appliance at computer.</li>
                    <li><strong>Island operation.</strong> Ang battery at solar ang nagsusuplay sa mga backup load. Ang surge capability (madalas 2× ng rated power sa loob ng ilang segundo) ay tumutulong magpaandar ng mga motor gaya ng water pump at compressor ng aircon. Puwedeng paandarin ang generator kapag paubos na ang battery.</li>
                    <li><strong>Pagbalik.</strong> Kapag stable na ulit ang grid sa loob ng nakatakdang oras, muling nagsi-synchronise ang inverter, isinasara ang grid relay at bumabalik sa normal na operasyon.</li>
                </ol>
                <div class="hg-note mt-4"><p><strong>Mahalaga:</strong> ang mga circuit lang na naka-wire sa backup panel ang may kuryente. Dapat pasok ang malalaking load sa backup rating ng inverter at sa maximum discharge current ng battery.</p></div>
            </section>

            <section aria-labelledby="battery">
                <h2 id="battery">Ang battery at ang BMS nito</h2>
                <ul class="dots">
                    <li><strong>Chemistry:</strong> ang lithium iron phosphate (LiFePO4) ang karaniwang gamit sa bahay: napaka-stable sa init, umaabot sa libu-libong cycle, at magagamit hanggang mga 90–95% depth of discharge.</li>
                    <li><strong>BMS (battery management system):</strong> binabalanse ang mga cell, pinoprotektahan ang battery laban sa over-voltage, under-voltage, over-current at over-temperature, at tinatantya ang <strong>state of charge</strong> (SOC).</li>
                    <li><strong>Closed-loop communication:</strong> sinasabi ng BMS sa inverter, sa pamamagitan ng CAN o RS485, ang pinapayagang charge voltage at ang maximum charge at discharge current nang real time. Kaya dapat nasa compatibility list ng inverter ang battery.</li>
                    <li><strong>Pag-charge:</strong> constant current (CC) hanggang maabot ang target na voltage, saka constant voltage (CV) habang unti-unting bumababa ang current hanggang mapuno.</li>
                    <li><strong>Enerhiya at power:</strong> enerhiya (kWh) = voltage × amp-hour; halimbawa, 51.2 V × 314 Ah ≈ 16 kWh. Ang power ay limitado ng current limit ng BMS, halimbawa 200 A × 51.2 V ≈ 10 kW.</li>
                    <li><strong>Temperatura:</strong> hindi dapat i-charge ang lithium battery kapag mas malamig sa mga 0 °C, at mas mabilis itong maluma kapag mainit, kaya ilagay ito sa malamig, malilim at maaliwalas na lugar.</li>
                </ul>
            </section>

            <section aria-labelledby="array">
                <h2 id="array">Ang solar array at MPPT</h2>
                <ul class="dots">
                    <li><strong>I–V curve:</strong> bumababa ang current ng panel habang tumataas ang voltage; pinakamataas ang power sa Vmp × Imp. Sumusunod ang current sa lakas ng sikat ng araw, at bumababa naman ang voltage habang umiinit ang panel.</li>
                    <li><strong>String voltage:</strong> nadadagdag ang voltage ng mga panel na naka-series. Ang open-circuit voltage (Voc) ng string sa pinakamalamig na umaga ay dapat mas mababa sa maximum DC input ng inverter, at ang working voltage nito ay dapat nasa loob ng MPPT range sa pinakamainit na hapon.</li>
                    <li><strong>Current bawat MPPT:</strong> dapat pasok sa limit ng bawat MPPT input ang string current (Imp, at Isc para sa kaligtasan).</li>
                    <li><strong>Maraming MPPT:</strong> ang mga bahagi ng bubong na magkaiba ang direksyon o lilim ay inilalagay sa magkahiwalay na MPPT input, para hindi mahila pababa ng isa ang isa.</li>
                    <li><strong>DC/AC oversizing:</strong> kapag mas malaki ang panel power na nakakonekta kaysa sa AC rating ng inverter (halimbawa 130–160%), tumataas ang output sa umaga, sa hapon at kapag maulap; sa peak ng tanghali, nililimitahan lang ng inverter ang sobra (“clipping”).</li>
                </ul>
                <p class="hg-formula">Max. string Voc ≈ Voc(STC) × [1 + temp. coefficient(Voc) × (T(min) − 25 °C)] × bilang ng panel</p>
            </section>

            <section aria-labelledby="protection">
                <h2 id="protection">Proteksyon at kaligtasan</h2>
                <ul class="dots">
                    <li><strong>PV side:</strong> DC isolator, DC surge protective device (SPD), string fuse kapag tatlo o higit pang string ang naka-parallel, at <a href="/brands/projoy" class="inline">rapid shutdown device</a> para mapatay ang kuryente sa mga kable sa bubong kapag may emergency.</li>
                    <li><strong>Battery side:</strong> DC breaker o fuse na akma sa battery current (madalas 125–250 A), maiikli at makakapal na kable, at tamang lug.</li>
                    <li><strong>AC side:</strong> breaker para sa GRID, BACKUP at GEN circuit, AC SPD, at (kung kinakailangan) residual-current protection.</li>
                    <li><strong>Earthing:</strong> naka-bond sa maayos na earth rod ang frame ng mga panel, mounting rail, inverter at mga enclosure; gumagana lang ang SPD kapag maayos ang earthing.</li>
                    <li><strong>Mga function ng inverter:</strong> anti-islanding, insulation-resistance at residual-current monitoring, at sa mga mas bagong model, arc-fault detection (AFCI).</li>
                </ul>
            </section>

            <section aria-labelledby="losses">
                <h2 id="losses">Efficiency at mga loss</h2>
                <p>Walang perpektong conversion. Ito ang mga karaniwang loss sa isang maayos na pagkakagawang sistema:</p>
                <div class="hg-table-wrap mt-3">
                    <table class="hg-table">
                        <caption class="sr-only">Mga karaniwang loss sa hybrid solar PV system</caption>
                        <thead><tr><th scope="col">Loss</th><th scope="col">Karaniwang laki</th><th scope="col">Paano ito mababawasan</th></tr></thead>
                        <tbody>
                            <tr><th scope="row">Init ng panel</th><td>8–15% sa tanghali sa Pilipinas</td><td>Mga panel na mababa ang temperature coefficient; may daloy ng hangin sa ilalim ng mga panel.</td></tr>
                            <tr><th scope="row">Alikabok at dumi</th><td>2–5%</td><td>Regular na paglilinis, lalo na sa tag-init.</td></tr>
                            <tr><th scope="row">Lilim at mismatch</th><td>0–10%+</td><td>Maayos na layout, magkahiwalay na MPPT, pag-iwas sa lilim.</td></tr>
                            <tr><th scope="row">DC at AC na kable</th><td>1–2%</td><td>Tamang laki ng kable at maiikling distansya.</td></tr>
                            <tr><th scope="row">Conversion ng inverter</th><td>2–4% (95–98% efficient)</td><td>Pumili ng de-kalidad na inverter na akma sa load.</td></tr>
                            <tr><th scope="row">Battery round trip</th><td>5–10% ng enerhiyang naipon</td><td>Gamitin nang direkta ang solar hangga’t maaari; ang sobra lang ang ipunin.</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-3">Kaya ang <strong>direktang paggamit ng solar</strong> ang laging pinaka-efficient na paraan, at kaya idinidisenyo muna ang sistema batay sa paggamit mo ng kuryente sa araw.</p>
            </section>

            <section aria-labelledby="compare">
                <h2 id="compare">Hybrid vs on-grid vs off-grid</h2>
                <div class="hg-table-wrap">
                    <table class="hg-table">
                        <caption class="sr-only">Paghahambing ng hybrid, on-grid at off-grid na solar PV system</caption>
                        <thead><tr><th scope="col"></th><th scope="col">On-grid</th><th scope="col">Hybrid</th><th scope="col">Off-grid</th></tr></thead>
                        <tbody>
                            <tr><th scope="row">Battery</th><td>Wala</td><td>Mayroon</td><td>Mayroon (kailangang-kailangan)</td></tr>
                            <tr><th scope="row">Kuryente kapag brownout</th><td>Wala (namamatay ayon sa batas)</td><td>Mayroon, sa mga backup circuit</td><td>Mayroon, walang grid na kasangkot</td></tr>
                            <tr><th scope="row">Net metering</th><td>Puwede</td><td>Puwede</td><td>Hindi</td></tr>
                            <tr><th scope="row">Paunang gastos</th><td>Pinakamababa</td><td>Katamtaman hanggang mataas</td><td>Pinakamataas bawat kWh na kailangan</td></tr>
                            <tr><th scope="row">Pinakaangkop sa</th><td>Gumagamit ng kuryente sa araw, maaasahang grid</td><td>Bahay at negosyong gustong makatipid at may backup</td><td>Isla, bukid at liblib na lugar</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-3">Basahin kung <a href="/grid-tied-pv-system-guide" class="inline">paano gumagana ang grid-tied solar PV system</a> (sa Ingles), at tingnan ang aming <a href="/services#system-types" class="inline">mga uri ng sistema</a>, ang <a href="/packages?type=complete" class="inline">complete hybrid packages</a> at ang mga brand ng inverter na dala namin, gaya ng <a href="/brands/goodwe" class="inline">GoodWe</a>, <a href="/brands/solis" class="inline">Solis</a> at <a href="/brands/sungrow" class="inline">Sungrow</a>.</p>
            </section>

            <section class="hg-note">
                <h2>Nagpaplano ng hybrid solar PV system?</h2>
                <p>Ipadala sa amin ang buwanang bill mo at ang mga appliance na gusto mong gumagana kapag brownout, at kami ang magdidisenyo ng array, inverter, battery at proteksyon para sa bahay o negosyo mo.</p>
                <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                    <a href="{{ Site::QUOTE_HREF }}" class="{{ Ui::button('primary', 'lg') }}">Humingi ng Quote</a>
                    <a href="/pv-calculator" class="{{ Ui::button('secondary', 'lg') }}">Subukan ang PV Calculator</a>
                </div>
            </section>

            <p class="border-t border-steel-200 pt-4 text-sm text-steel-600">Inilalarawan ng gabay na ito kung paano gumagana ang karaniwang residential hybrid inverter. Iba-iba ang eksaktong limit, timing at pangalan ng mode depende sa brand at model, kaya laging sundin ang datasheet at manual ng manufacturer at ang Philippine Electrical Code. Huling na-update noong Nobyembre 2026.</p>
        </div>
    </x-container>
</x-site-layout>
