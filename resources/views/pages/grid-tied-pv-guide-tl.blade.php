@php
    use App\Support\Site;
    use App\Support\Ui;
    // Tagalog version of pages/grid-tied-pv-guide.blade.php: same sections and ids. Technical terms stay in English, as Filipino installers use them.
    $toc = [
        'short' => 'Ang maikling paliwanag', 'parts' => 'Mga bahagi ng sistema', 'inverter' => 'Sa loob ng grid-tied inverter',
        'flow' => 'Paano hinahati ang kuryente', 'day' => 'Isang araw ng sistema', 'grid' => 'Pakikisabay sa grid',
        'brownout' => 'Bakit ito namamatay kapag brownout', 'export' => 'Net metering at export limit', 'array' => 'Ang solar array at MPPT',
        'variants' => 'String, micro at optimizer system', 'protection' => 'Proteksyon at kaligtasan', 'losses' => 'Efficiency at mga loss',
        'compare' => 'Grid-tied vs hybrid vs off-grid',
    ];
@endphp
<x-site-layout title="Paano Gumagana ang Grid-Tied Solar PV System" path="/grid-tied-pv-system-guide/tagalog" lang="fil" :reviews-above-footer="false"
    description="Teknikal na gabay sa Tagalog kung paano gumagana ang grid-tied (on-grid) solar PV system: solar array at MPPT, mga converter ng inverter, grid synchronisation, anti-islanding, pakikihati ng kuryente sa grid, net metering, export limit, proteksyon at mga loss.">
    <x-slot:head>
        @include('partials.guide-styles')
        @include('partials.guide-hreflang', ['en' => '/grid-tied-pv-system-guide'])
    </x-slot:head>
    <x-page-header eyebrow="Teknikal na gabay" title="Paano gumagana ang grid-tied solar PV system"
        intro="Ang pinakasimple at pinakatipid na paraan para bumaba ang bill mo sa kuryente: paano nagtutulungan ang mga panel, ang inverter at ang grid, ano ang nangyayari sa loob ng inverter, at bakit namamatay ang sistema kapag brownout." />
    <x-container class="py-10 sm:py-14">
        <div class="hg space-y-10 text-steel-800" style="max-width: 48rem">
            <p class="text-sm"><a href="/grid-tied-pv-system-guide" hreflang="en" lang="en" class="inline">Read this guide in English</a></p>

            <nav aria-label="Sa pahinang ito" class="hg-toc hg-note">
                <p class="font-bold text-steel-950">Sa pahinang ito</p>
                <ul class="mt-1 grid sm:grid-cols-2" style="column-gap: 1.5rem">
                    @foreach ($toc as $id => $label)<li><a href="#{{ $id }}">{{ $label }}</a></li>@endforeach
                </ul>
            </nav>

            <section aria-labelledby="short">
                <h2 id="short">Ang maikling paliwanag</h2>
                <p>Ang <strong>grid-tied solar PV system</strong> (tinatawag ding on-grid) ay binubuo ng mga solar panel at isang <strong>grid-tied inverter</strong> na nakakonekta sa main panel ng bahay mo, kasabay ng utility grid. Wala itong battery. Sa araw, inuuna ng mga appliance mo na gamitin ang solar; kapag mas malaki ang nagagawa ng mga panel kaysa sa ginagamit mo, lumalabas ang sobra papunta sa grid, at kapag mas maliit (o sa gabi), awtomatikong ang grid ang pumupuno sa kulang.</p>
                <p>Sa teknikal na paliwanag, ang inverter ay gumagana bilang <strong>grid-following current source</strong>: sinusukat nito ang voltage at frequency ng grid at nagpapasok ng current na eksaktong sabay dito. Hindi ito gumagana nang walang grid, kaya namamatay ito kapag brownout.</p>
            </section>

            <section aria-labelledby="parts">
                <h2 id="parts">Mga bahagi ng sistema</h2>
                <div class="hg-diagram mt-4">
                    <svg viewBox="0 0 760 380" role="img" aria-labelledby="dg-title dg-desc">
                        <title id="dg-title">Diagram ng daloy ng kuryente sa grid-tied solar PV system</title>
                        <desc id="dg-desc">Ang mga solar panel ay nakakonekta sa grid-tied inverter sa pamamagitan ng DC isolator at rapid shutdown device. Ang AC output ng inverter ay dumadaan sa breaker papunta sa main panel, na nagpapakain sa mga load ng bahay at nakakonekta sa utility grid sa pamamagitan ng bi-directional meter. Nagpapadala ng data sa inverter ang current sensor sa linya ng grid para sa export limit.</desc>
                        <rect class="box" x="20" y="40" width="170" height="80" rx="10"/>
                        <text class="t" x="105" y="72" text-anchor="middle">Solar PV array</text>
                        <text class="s" x="105" y="92" text-anchor="middle">mga string ng panel</text>
                        <text class="s" x="105" y="108" text-anchor="middle">200–1,000 V DC</text>
                        <rect class="box" x="20" y="200" width="170" height="64" rx="10"/>
                        <text class="t" x="105" y="227" text-anchor="middle">DC protection</text>
                        <text class="s" x="105" y="247" text-anchor="middle">isolator · SPD · RSD</text>
                        <rect class="box-main" x="240" y="40" width="250" height="300" rx="12"/>
                        <text class="t" x="365" y="66" text-anchor="middle">Grid-tied inverter</text>
                        <rect class="inner" x="260" y="82" width="210" height="44" rx="6"/>
                        <text class="s" x="365" y="100" text-anchor="middle">MPPT DC–DC converter</text>
                        <text class="s" x="365" y="116" text-anchor="middle">(isa bawat MPPT input)</text>
                        <rect class="inner" x="260" y="142" width="210" height="36" rx="6"/>
                        <text class="s" x="365" y="165" text-anchor="middle">DC link (mga capacitor)</text>
                        <rect class="inner" x="260" y="194" width="210" height="44" rx="6"/>
                        <text class="s" x="365" y="212" text-anchor="middle">Inverter bridge (PWM)</text>
                        <text class="s" x="365" y="228" text-anchor="middle">+ output filter</text>
                        <rect class="inner" x="260" y="254" width="210" height="44" rx="6"/>
                        <text class="s" x="365" y="272" text-anchor="middle">Controller: PLL, anti-islanding,</text>
                        <text class="s" x="365" y="288" text-anchor="middle">grid protection, mga relay</text>
                        <text class="s" x="365" y="325" text-anchor="middle">AC output: 230 V, 60 Hz</text>
                        <rect class="box" x="560" y="40" width="180" height="70" rx="10"/>
                        <text class="t" x="650" y="70" text-anchor="middle">Main panel</text>
                        <text class="s" x="650" y="90" text-anchor="middle">PV breaker + AC SPD</text>
                        <rect class="box" x="560" y="150" width="180" height="60" rx="10"/>
                        <text class="t" x="650" y="177" text-anchor="middle">Mga load sa bahay</text>
                        <text class="s" x="650" y="196" text-anchor="middle">lahat ng circuit</text>
                        <rect class="box" x="560" y="270" width="180" height="70" rx="10"/>
                        <text class="t" x="650" y="298" text-anchor="middle">Utility grid</text>
                        <text class="s" x="650" y="318" text-anchor="middle">sa bi-directional meter</text>
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
                    <p class="hg-legend" style="position: sticky; left: 0"><span class="l-dc">DC na kuryente</span><span class="l-ac">AC na kuryente</span><span class="l-data">Data / sukat</span></p>
                </div>
                <div class="hg-table-wrap mt-6">
                    <table class="hg-table">
                        <caption class="sr-only">Mga pangunahing bahagi ng grid-tied solar PV system</caption>
                        <thead><tr><th scope="col">Bahagi</th><th scope="col">Ano ang ginagawa</th><th scope="col">Teknikal na detalye</th></tr></thead>
                        <tbody>
                            <tr><th scope="row">Solar PV array</th><td>Ginagawang DC na kuryente ang sikat ng araw.</td><td>Naka-series ang mga panel sa mga “string”; dapat pasok ang voltage sa MPPT range at maximum DC input ng inverter kahit sa pinakamalamig na umaga.</td></tr>
                            <tr><th scope="row">Grid-tied inverter</th><td>Kino-convert ang DC sa AC na naka-synchronise sa grid.</td><td>Single-phase para sa bahay, three-phase para sa negosyo; hanggang mga 98% efficient.</td></tr>
                            <tr><th scope="row">Main panel</th><td>Pinagsasama ang solar at ang kuryente ng grid at ipinapamahagi ito sa mga circuit mo.</td><td>May sariling breaker ang inverter; tuloy-tuloy na kumukuha ang mga load sa solar at sa grid nang hindi napapansin.</td></tr>
                            <tr><th scope="row">CT o smart meter (opsyonal)</th><td>Sinusukat ang kuryenteng papunta o galing sa grid.</td><td>Kailangan para sa export limit o zero export, at para makita sa app ang konsumo ng bahay.</td></tr>
                            <tr><th scope="row">Bi-directional meter</th><td>Itinatala ang enerhiyang na-import at na-export.</td><td>Ikinakabit ng utility mo para sa <a href="/net-metering/tagalog" class="inline">net metering</a>.</td></tr>
                            <tr><th scope="row">Mga protection device</th><td>Pinapanatiling ligtas ang tao at ang kagamitan.</td><td>DC isolator, PV at AC surge protector, breaker, rapid shutdown device at earthing.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section aria-labelledby="inverter">
                <h2 id="inverter">Sa loob ng grid-tied inverter</h2>
                <p>Mas simple ang grid-tied inverter kaysa sa hybrid: walang battery converter at walang backup port. May dalawa itong power stage na nakapalibot sa isang DC link, at isang mabilis na controller na nagpapanatiling sabay nito sa grid.</p>
                <h3>1. MPPT DC–DC converter</h3>
                <p>Inaayos ng bawat MPPT input ang voltage ng string nito papunta sa <strong>maximum power point</strong>, kung saan pinakamataas ang voltage × current. Tuloy-tuloy na tinutulak nang kaunti ng tracker ang operating point at tinitingnan kung tumaas o bumaba ang power (“perturb and observe”), at paminsan-minsan ay sinusuyod nito ang buong curve para hindi ito maipit sa maling peak dahil sa bahagyang lilim. Maraming inverter ang may dalawa o higit pang MPPT para magkahiwalay na ma-optimise ang mga bahagi ng bubong na magkaiba ang harap.</p>
                <h3>2. Ang DC link</h3>
                <p>Pinapanatili ng malalaking capacitor ang stable na DC voltage sa pagitan ng dalawang stage at nagsisilbi silang buffer ng enerhiya. Kailangan ito dahil ang single-phase inverter ay nagpapadala ng power sa grid nang pa-pulso, sa dobleng frequency ng grid (120 Hz), samantalang tuloy-tuloy ang power na galing sa mga panel.</p>
                <h3>3. Inverter bridge at filter</h3>
                <p>Hinahati-hati ng mga semiconductor switch (IGBT o MOSFET) ang DC sa napakataas na frequency gamit ang <strong>pulse-width modulation</strong> (PWM). Ginagawa naman ng inductor–capacitor filter na makinis na sinusoidal current ang mga pulse, na mababa ang harmonic distortion (karaniwang wala pang 3%), gaya ng hinihingi ng grid code.</p>
                <h3>4. Controller</h3>
                <ul class="dots">
                    <li><strong>Phase-locked loop (PLL):</strong> ikinakandado (lock) ang output current sa frequency (60 Hz) at phase ng grid voltage, para lumabas ang power sa tamang power factor (karaniwang mga 1).</li>
                    <li><strong>Current control:</strong> hindi ang inverter ang nagtatakda ng voltage, kundi ang grid; ang kinokontrol ng inverter ay kung gaano kalaking current ang itinutulak nito, batay sa power na nakukuha ng MPPT stage.</li>
                    <li><strong>Grid protection:</strong> tuloy-tuloy na ikinukumpara ang voltage at frequency sa mga limit ng grid code, at binubuksan ang output relay kapag lumabas ang mga ito sa range.</li>
                    <li><strong>Insulation at residual-current monitoring:</strong> sinusuri ang insulation ng PV array sa earth bago kumonekta, at binabantayan ang leakage current habang tumatakbo.</li>
                </ul>
            </section>

            <section aria-labelledby="flow">
                <h2 id="flow">Paano hinahati ang kuryente</h2>
                <p>Ang grid ay parang napakalaking battery na laging nakakonekta. Physics ang sinusunod ng kuryente, hindi iskedyul: kumukuha ang mga appliance mo sa pinakamalapit na source, at itinutulak ng inverter ang lahat ng solar power na kaya nitong makuha.</p>
                <p class="hg-formula">P(solar) + P(import mula sa grid) = P(load) + P(export sa grid) + loss</p>
                <ul class="dots mt-3">
                    <li><strong>Solar &gt; load:</strong> ang sobra ay dumadaloy pabalik sa metro mo papunta sa grid (export).</li>
                    <li><strong>Solar &lt; load:</strong> awtomatikong ang grid ang pumupuno sa kulang (import).</li>
                    <li><strong>Gabi:</strong> natutulog ang inverter; lahat ng kuryente ay galing sa grid gaya ng dati.</li>
                </ul>
                <p class="mt-3">Ang solar na direktang ginagamit sa bahay mo ay nakakatipid ng buong retail rate, samantalang mas mababang rate ang kredito sa na-export na kuryente. Kaya pinakamabilis bumalik ang puhunan sa grid-tied system kapag idinisenyo ito batay sa konsumo mo sa <strong>araw</strong>, halimbawa sa opisina, tindahan, pabrika, paaralan at bahay na naka-aircon sa araw.</p>
            </section>

            <section aria-labelledby="day">
                <h2 id="day">Isang araw ng sistema</h2>
                <ul class="dots">
                    <li><strong>Mga 6 AM:</strong> tumataas ang voltage ng array nang lampas sa start-up voltage ng inverter; sinusuri nito ang insulation at ang grid, hinihintay ang kinakailangang reconnection time, saka nagsisimulang mag-export ng maliit na current.</li>
                    <li><strong>Umaga:</strong> tumataas ang output kasabay ng araw; inuuna itong gamitin ng mga appliance mo, kaya mas kaunti ang kinukuha sa grid.</li>
                    <li><strong>Tanghali:</strong> peak ng output; kapag lampas ito sa ginagamit mo, ine-export ang sobra (o nililimitahan kung may export cap ka). Sa mga oversized na array, puwedeng panandaliang mag-“clip” ang inverter sa AC rating nito.</li>
                    <li><strong>Hapon:</strong> bumababa ang output; unti-unting dumarami ang kinukuha sa grid.</li>
                    <li><strong>Gabi:</strong> humihiwalay ang inverter at nagsi-standby; lahat ng kuryente ay galing sa grid.</li>
                </ul>
            </section>

            <section aria-labelledby="grid">
                <h2 id="grid">Pakikisabay sa grid</h2>
                <ul class="dots">
                    <li><strong>Synchronisation:</strong> bago kumonekta, kailangang makita ng inverter na stable at pasok sa limit ang grid sa loob ng nakatakdang oras, saka nito itinutugma ang output dito.</li>
                    <li><strong>Voltage rise:</strong> bahagyang tumataas ang voltage sa connection point mo kapag nag-e-export. Sa mahahabang service drop o mahinang linya sa probinsya, puwede nitong itulak ang voltage lampas sa limit at mag-trip ang inverter; nakakatulong ang tamang laki ng kable at, kung pinapayagan, ang reactive-power settings.</li>
                    <li><strong>Power quality:</strong> kailangang mababa ang harmonics ng inverter, at depende sa grid code, puwede nitong i-adjust ang power factor o bawasan ang output kapag mataas ang frequency o voltage ng grid.</li>
                    <li><strong>Grid code settings:</strong> pinipili ng installer ang profile ng bansa o utility para tugma sa lokal na requirement ang mga trip limit at reconnection time.</li>
                </ul>
            </section>

            <section aria-labelledby="brownout">
                <h2 id="brownout">Bakit ito namamatay kapag brownout</h2>
                <p>Kapag nawalan ng kuryente ang grid, kailangang huminto ang grid-tied inverter sa loob ng isang iglap, kahit maaraw. Tinatawag itong <strong>anti-islanding</strong> protection, at required ito sa bawat grid-tied inverter.</p>
                <ol class="steps mt-3">
                    <li><strong>Kaligtasan ng mga lineman:</strong> kung patuloy na tatakbo ang inverter, puwede nitong kuryentehin ang mga “patay” na linya ng utility na kinukumpuni ng mga technician.</li>
                    <li><strong>Pagtukoy (detection):</strong> binabantayan ng inverter kung lumalabas sa range ang voltage at frequency, at gumagamit ito ng mga active method (maliliit na sinasadyang paggalaw ng frequency o current) na mabilis na magdudulot ng paglihis kapag wala ang grid.</li>
                    <li><strong>Paghiwalay:</strong> bumubukas ang relay nito, karaniwang sa loob ng hindi hihigit sa mga dalawang segundo, at madalas ay mas mabilis pa.</li>
                    <li><strong>Muling pagkonekta:</strong> kapag bumalik ang grid at nanatili sa limit sa loob ng kinakailangang oras (madalas ilang minuto), awtomatiko itong kumokonekta ulit.</li>
                </ol>
                <div class="hg-note mt-4"><p><strong>Kailangan mo ba ng kuryente kapag brownout?</strong> Magdagdag ng battery gamit ang <a href="/hybrid-pv-system-guide/tagalog" class="inline">hybrid solar PV system</a>, o magkabit ng AC-coupled battery inverter sa kasalukuyang grid-tied system.</p></div>
            </section>

            <section aria-labelledby="export">
                <h2 id="export">Net metering at export limit</h2>
                <ul class="dots">
                    <li><strong>Net metering:</strong> nagkakabit ang utility mo ng bi-directional meter; ang bawat na-export na kWh ay nagiging kredito na nagpapababa ng bill mo. Puwedeng mag-apply ang mga sistemang 100 kW pababa. Basahin ang aming <a href="/net-metering/tagalog" class="inline">gabay sa net metering</a>.</li>
                    <li><strong>Kung walang net metering:</strong> puwedeng masayang lang ang na-export na kuryente, o mali pang mabilang ito ng lumang metro mo, kaya maraming may-ari ang nagse-set ng <strong>zero-export</strong> limit habang hinihintay ang approval ng net metering.</li>
                    <li><strong>Paano gumagana ang export limit:</strong> sinasabi ng CT o smart meter sa grid connection kung gaano kalaki ang lumalabas; inilalayo ng inverter ang MPPT mula sa maximum power point para bawasan ang output, kaya nananatili ang export sa nakatakdang limit.</li>
                    <li><strong>Commercial na site:</strong> madalas gumamit ng zero export ang mga negosyo at idinidisenyo ang array batay sa base load nila sa araw, kaya halos lahat ng kWh ay nagagamit sa mismong lugar.</li>
                </ul>
            </section>

            <section aria-labelledby="array">
                <h2 id="array">Ang solar array at MPPT</h2>
                <ul class="dots">
                    <li><strong>I–V curve:</strong> sumusunod ang current sa lakas ng sikat ng araw; bumababa ang voltage habang umiinit ang mga panel. Pinakamataas ang power sa Vmp × Imp.</li>
                    <li><strong>String voltage:</strong> ang open-circuit voltage sa pinakamalamig na umaga ay dapat mas mababa sa maximum DC input ng inverter, at ang working voltage sa mainit na hapon ay dapat nasa loob ng MPPT range.</li>
                    <li><strong>Current:</strong> dapat pasok ang string current sa maximum at short-circuit rating ng bawat MPPT input.</li>
                    <li><strong>DC/AC ratio:</strong> karaniwan ang 1.1 hanggang 1.3 (at hanggang mga 1.5 sa ilang model), para mas maraming oras na malapit sa full power ang takbo ng inverter; kadalasan ay mas malaki ang dagdag na enerhiya sa umaga at hapon kaysa sa maliit na clipping loss sa tanghali.</li>
                </ul>
                <p class="hg-formula">Max. string Voc ≈ Voc(STC) × [1 + temp. coefficient(Voc) × (T(min) − 25 °C)] × bilang ng panel</p>
            </section>

            <section aria-labelledby="variants">
                <h2 id="variants">String, micro at optimizer system</h2>
                <div class="hg-table-wrap">
                    <table class="hg-table">
                        <caption class="sr-only">Mga uri ng grid-tied inverter setup</caption>
                        <thead><tr><th scope="col">Uri</th><th scope="col">Paano gumagana</th><th scope="col">Pinakaangkop sa</th></tr></thead>
                        <tbody>
                            <tr><th scope="row">String inverter</th><td>Isang central inverter na may ilang MPPT input, bawat isa ay para sa isang string ng panel.</td><td>Karamihan ng bahay at negosyo na simple at walang lilim ang bubong; pinakamababang gastos bawat kW.</td></tr>
                            <tr><th scope="row">Microinverter</th><td>Maliit na inverter sa ilalim ng bawat panel (o bawat pares ng panel), na ginagawang AC ang DC mismo sa bubong.</td><td>Masalimuot o may bahagyang lilim na bubong; monitoring bawat panel; low-voltage DC lang sa bubong.</td></tr>
                            <tr><th scope="row">String inverter + optimizer</th><td>May DC–DC optimizer sa bawat panel na may sariling MPPT, at nagpapakain ito sa isang central inverter.</td><td>Bubong na may lilim pero gusto pa rin ng central inverter; monitoring at shutdown bawat panel.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section aria-labelledby="protection">
                <h2 id="protection">Proteksyon at kaligtasan</h2>
                <ul class="dots">
                    <li><strong>DC side:</strong> DC isolator, DC surge protective device (SPD), string fuse kapag tatlo o higit pang string ang naka-parallel, at <a href="/brands/projoy" class="inline">rapid shutdown device</a> para mapatay ang kuryente sa mga kable sa bubong kapag may emergency.</li>
                    <li><strong>AC side:</strong> sariling breaker para sa inverter sa main panel, AC SPD, at residual-current protection kung kinakailangan.</li>
                    <li><strong>Earthing:</strong> naka-bond sa maayos na earth rod ang frame ng mga panel, mga rail, inverter at mga enclosure.</li>
                    <li><strong>Mga function ng inverter:</strong> anti-islanding, grid voltage at frequency protection, insulation at residual-current monitoring, at arc-fault detection (AFCI) sa maraming mas bagong model.</li>
                </ul>
            </section>

            <section aria-labelledby="losses">
                <h2 id="losses">Efficiency at mga loss</h2>
                <div class="hg-table-wrap">
                    <table class="hg-table">
                        <caption class="sr-only">Mga karaniwang loss sa grid-tied solar PV system</caption>
                        <thead><tr><th scope="col">Loss</th><th scope="col">Karaniwang laki</th><th scope="col">Paano ito mababawasan</th></tr></thead>
                        <tbody>
                            <tr><th scope="row">Init ng panel</th><td>8–15% sa tanghali sa Pilipinas</td><td>Mga panel na mababa ang temperature coefficient; may daloy ng hangin sa ilalim ng mga panel.</td></tr>
                            <tr><th scope="row">Alikabok at dumi</th><td>2–5%</td><td>Regular na paglilinis, lalo na sa tag-init.</td></tr>
                            <tr><th scope="row">Lilim at mismatch</th><td>0–10%+</td><td>Maayos na layout, magkahiwalay na MPPT, optimizer o microinverter.</td></tr>
                            <tr><th scope="row">DC at AC na kable</th><td>1–2%</td><td>Tamang laki ng kable at maiikling distansya.</td></tr>
                            <tr><th scope="row">Conversion ng inverter</th><td>1.5–3% (97–98.5% efficient)</td><td>De-kalidad na inverter na akma sa array.</td></tr>
                            <tr><th scope="row">Clipping at export limit</th><td>0–5%</td><td>Makatwirang DC/AC ratio; net metering sa halip na zero export.</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-3">Dahil walang battery na kailangang i-charge at i-discharge, ang grid-tied system ang pinaka-efficient na paraan para gawing magagamit na kuryente ang sikat ng araw.</p>
            </section>

            <section aria-labelledby="compare">
                <h2 id="compare">Grid-tied vs hybrid vs off-grid</h2>
                <div class="hg-table-wrap">
                    <table class="hg-table">
                        <caption class="sr-only">Paghahambing ng grid-tied, hybrid at off-grid na solar PV system</caption>
                        <thead><tr><th scope="col"></th><th scope="col">Grid-tied</th><th scope="col">Hybrid</th><th scope="col">Off-grid</th></tr></thead>
                        <tbody>
                            <tr><th scope="row">Battery</th><td>Wala</td><td>Mayroon</td><td>Mayroon (kailangang-kailangan)</td></tr>
                            <tr><th scope="row">Kuryente kapag brownout</th><td>Wala (namamatay ayon sa batas)</td><td>Mayroon, sa mga backup circuit</td><td>Mayroon, walang grid na kasangkot</td></tr>
                            <tr><th scope="row">Net metering</th><td>Puwede</td><td>Puwede</td><td>Hindi</td></tr>
                            <tr><th scope="row">Paunang gastos</th><td>Pinakamababa</td><td>Katamtaman hanggang mataas</td><td>Pinakamataas bawat kWh na kailangan</td></tr>
                            <tr><th scope="row">Pinakaangkop sa</th><td>Gumagamit ng kuryente sa araw, maaasahang grid, pinakamabilis bumawi</td><td>Tipid sa bill at may backup kapag brownout</td><td>Isla, bukid at liblib na lugar</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-3">Basahin kung <a href="/hybrid-pv-system-guide/tagalog" class="inline">paano gumagana ang hybrid solar PV system</a>, ihambing ang aming <a href="/services#system-types" class="inline">mga uri ng sistema</a>, o tingnan ang mga grid-tied inverter na dala namin mula sa <a href="/brands/solis" class="inline">Solis</a>.</p>
            </section>

            <section class="hg-note">
                <h2>Nagpaplano ng grid-tied solar PV system?</h2>
                <p>Ipadala sa amin ang mga buwanang bill mo at kung kailan ka pinakamalakas gumamit ng kuryente sa araw, at kami ang magsusukat ng array at inverter para pinakamabilis bumawi ang puhunan, may net metering man o wala.</p>
                <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                    <a href="{{ Site::QUOTE_HREF }}" class="{{ Ui::button('primary', 'lg') }}">Humingi ng Quote</a>
                    <a href="/pv-calculator" class="{{ Ui::button('secondary', 'lg') }}">Subukan ang PV Calculator</a>
                </div>
            </section>

            <p class="border-t border-steel-200 pt-4 text-sm text-steel-600">Inilalarawan ng gabay na ito kung paano gumagana ang karaniwang grid-tied inverter. Iba-iba ang eksaktong limit, timing at settings depende sa brand, model at utility, kaya laging sundin ang datasheet at manual ng manufacturer, ang requirement ng utility mo at ang Philippine Electrical Code. Huling na-update noong Nobyembre 2026.</p>
        </div>
    </x-container>
</x-site-layout>
