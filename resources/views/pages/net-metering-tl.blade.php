@php
    use App\Support\Site;
    use App\Support\Ui;
    // Tagalog version of pages/net-metering.blade.php. Technical and official terms stay in English, as they appear on utility forms.
@endphp
<x-site-layout title="Net Metering sa Pilipinas" path="/net-metering/tagalog" lang="fil" :reviews-above-footer="false" description="Paano gumagana ang net metering sa Pilipinas: sino ang puwedeng mag-apply, paano kinukuwenta ang kredito sa bill, ang karaniwang hakbang sa pag-apply, at paano sukatin ang solar PV system mo para pinakamalaki ang matipid.">
    <x-slot:head>
        @include('partials.net-metering-styles')
        @include('partials.guide-hreflang', ['en' => '/net-metering'])
    </x-slot:head>
    <x-page-header eyebrow="Gabay" title="Net metering sa Pilipinas" intro="Paano kumita ng kredito sa bill mula sa solar na hindi mo nagagamit, at paano sukatin ang sistema mo para pinakamalaki ang matipid." />
    <x-container class="py-10 sm:py-14">
        <div class="nm max-w-3xl space-y-10 text-steel-800">
            <p class="text-sm"><a href="/net-metering" hreflang="en" lang="en" class="inline">Read this guide in English</a></p>

            <section>
                <h2>Ano ang net metering?</h2>
                <p>Sa net metering, ang bahay o negosyong may solar panel na nakakonekta sa grid ay puwedeng magpadala ng kuryenteng hindi nito nagagamit sa electric utility nito (Meralco o ang electric cooperative mo). Nagkakabit ang utility ng two-way (bi-directional) meter na nagtatala ng kuryenteng kinukuha mo sa grid at ng kuryenteng ibinabalik mo.</p>
                <p>Bawat kilowatt-hour (kWh) na ipinapadala mo sa grid ay may katumbas na kredito sa piso, at ginagamit ang mga kreditong ito para bumaba ang bill mo sa kuryente.</p>
            </section>

            <section>
                <h2>Sino ang puwedeng mag-apply?</h2>
                <ul class="dots">
                    <li>Mga customer ng distribution utility na maayos ang record sa pagbabayad (nababayaran ang bill sa oras).</li>
                    <li>Renewable energy system na <strong>100 kW o mas maliit</strong>. Sakop nito ang halos lahat ng bahay at karamihan ng maliliit na negosyo.</li>
                    <li>Dapat nakakonekta sa grid ang sistema, kaya pasok ang <strong>on-grid o hybrid</strong> na sistema. Ang off-grid na sistema ay walang koneksyon sa grid at hindi makapag-export ng kuryente.</li>
                </ul>
                <p class="mt-3">Nilikha ang net metering sa ilalim ng Renewable Energy Act of 2008 (Republic Act No. 9513), at ang Energy Regulatory Commission (ERC) ang nagtatakda ng mga patakaran nito.</p>
            </section>

            <section>
                <h2>Paano kinukuwenta ang kredito</h2>
                <p>Kinikredito ang na-export na kuryente sa <strong>blended generation cost</strong> ng utility mo. Generation charge lang iyon sa bill mo, kaya mas mababa ang halaga ng isang kWh na ine-export mo kaysa sa isang kWh na bibilhin mo sana, na may kasama pang distribution, transmission, buwis at iba pang singil.</p>
                <p>Sa madaling salita, <strong>mas malaki ang natitipid mo sa solar na ikaw mismo ang gumagamit kaysa sa solar na ine-export mo.</strong> Kaya idinidisenyo namin ang sistema batay sa aktwal mong paggamit ng kuryente:</p>
                <ul class="dots mt-3">
                    <li>Itugma ang dami ng panel sa paggamit mo sa araw, sa halip na palakihin ang sistema hangga’t maaari.</li>
                    <li>Paandarin ang malalaking load gaya ng washing machine, water pump o aircon habang may araw.</li>
                    <li>Magdagdag ng battery (hybrid system) para maipon ang sobrang solar sa tanghali para sa gabi at sa brownout.</li>
                </ul>
                <p class="mt-3">Gusto mo ng mas teknikal na paliwanag? Basahin kung paano gumagana ang <a href="/grid-tied-pv-system-guide/tagalog" class="inline">grid-tied</a> at ang <a href="/hybrid-pv-system-guide/tagalog" class="inline">hybrid</a> na solar PV system.</p>
            </section>

            <section>
                <h2>Ang karaniwang hakbang sa pag-apply</h2>
                <p class="mb-5">May sariling form at checklist ang bawat utility, kaya laging kunin ang pinakabagong listahan mula sa utility mo. Ganito ang takbo ng karamihan ng application:</p>
                <ol class="nm-steps">
                    <li><h3>Idisenyo ang sistema</h3><p>Magplano ng on-grid o hybrid na sistema. Hihingi ang utility ng electrical plan na pirmado ng isang licensed electrical engineer.</p></li>
                    <li><h3>Permit at pagkakabit</h3><p>Kumuha ng electrical permit sa city o municipal office ninyo. Pagkatapos ng installation, iinspeksyunin ng opisina ang trabaho at maglalabas ito ng Certificate of Final Electrical Inspection (CFEI).</p></li>
                    <li><h3>Mag-apply sa utility mo</h3><p>Isumite ang net-metering application kasama ang mga dokumentong nakalista ng utility mo.</p></li>
                    <li><h3>Evaluation at inspeksyon</h3><p>Rerepasuhin ng utility ang disenyo at kagamitan, at iinspeksyunin ang installation.</p></li>
                    <li><h3>Pumirma sa kasunduan</h3><p>Pipirma ka ng Net-Metering Agreement sa utility.</p></li>
                    <li><h3>Pagkakabit ng metro</h3><p>Ikakabit ng utility ang bi-directional meter, at magsisimula nang magkaroon ng kredito ang mga export mo.</p></li>
                </ol>
            </section>

            <section class="rounded-[var(--radius-panel)] border border-[#f0d39a] bg-warn-50 p-5">
                <h3>Nagbabago ang mga patakaran</h3>
                <p>Nagsisikap ang Department of Energy at ang ERC na paikliin at pasimplehin ang pag-apply sa net metering, at nagmungkahi pa ang ERC ng karagdagang pagbabago noong 2026. Alamin muna sa utility mo ang pinakabagong requirement bago ka mag-apply.</p>
            </section>

            <section>
                <h2>Nagpaplano ng sistemang nakakonekta sa grid?</h2>
                <p>Gamitin ang libre naming <a href="/pv-calculator" class="inline">PV Calculator</a> para sa unang tantya batay sa buwanang bill mo, basahin ang tungkol sa <a href="/services#system-types" class="inline">on-grid, hybrid at off-grid na sistema</a>, o kausapin kami tungkol sa plano mo.</p>
                <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                    <a href="{{ Site::QUOTE_HREF }}" class="{{ Ui::button('primary', 'lg') }}">Humingi ng Quote</a>
                    <a href="{{ Site::telHref($settings->phone_e164) }}" class="{{ Ui::button('secondary', 'lg') }}"><x-icon name="phone" class="size-4" /> Tumawag sa {{ $settings->phone_display }}</a>
                </div>
            </section>

            <p class="border-t border-steel-200 pt-4 text-sm text-steel-600">Mga pinagkunan: Department of Energy, <a href="https://prod-cms.doe.gov.ph/documents/d/guest/the-5-ws-of-net-metering-primer-" target="_blank" rel="noopener" class="inline">The 5 W’s of Net Metering</a> (primer); Republic Act No. 9513. Huling sinuri noong Oktubre 2026. Pangkalahatang impormasyon ang gabay na ito; ang kasalukuyang patakaran ng utility mo ang masusunod.</p>
        </div>
    </x-container>
</x-site-layout>
