@php
    use App\Support\Site;
    use App\Support\Ui;
@endphp
<x-site-layout title="Net Metering in the Philippines" path="/net-metering" :reviews-above-footer="false" description="How net metering works in the Philippines: who can apply, how bill credits are computed, the usual application steps, and how to size your solar system for the most savings.">
    <x-slot:head>
        <style>
            .nm h2 { margin-bottom: .5rem; font-size: 1.5rem; font-weight: 800; color: var(--color-steel-950); }
            .nm h3 { margin-bottom: .25rem; font-size: 1.125rem; font-weight: 700; color: var(--color-steel-950); }
            .nm p + p { margin-top: .75rem; }
            .nm ul.dots li { margin-left: 1.25rem; list-style: disc; }
            .nm ul.dots li + li { margin-top: .35rem; }
            .nm-steps { counter-reset: step; }
            .nm-steps li { counter-increment: step; position: relative; padding-left: 3.25rem; min-height: 2.5rem; }
            .nm-steps li + li { margin-top: 1.1rem; }
            .nm-steps li::before { content: counter(step); position: absolute; left: 0; top: 0; width: 2.25rem; height: 2.25rem; border-radius: 999px; background: var(--color-gold-500); color: var(--color-steel-950); font-weight: 800; display: grid; place-items: center; }
            .nm a.inline { font-weight: 600; color: var(--color-steel-900); text-decoration: underline; }
            .nm a.inline:hover { color: var(--color-gold-800); }
        </style>
    </x-slot:head>
    <x-page-header eyebrow="Guide" title="Net metering in the Philippines" intro="How to earn bill credits from the solar power you don’t use, and how to size your system to save the most." />
    <x-container class="py-10 sm:py-14">
        <div class="nm max-w-3xl space-y-10 text-steel-800">
            <section>
                <h2>What is net metering?</h2>
                <p>Net metering lets a home or business with grid-connected solar panels send the electricity it doesn’t use to its electric utility (Meralco or your electric cooperative). The utility installs a two-way (bi-directional) meter that records the power you take from the grid and the power you send back.</p>
                <p>Each kilowatt-hour (kWh) you send to the grid earns a peso credit, and the credits are used to reduce your electric bill.</p>
            </section>

            <section>
                <h2>Who can apply?</h2>
                <ul class="dots">
                    <li>Customers of a distribution utility who are in good credit standing (bills paid on time).</li>
                    <li>A renewable energy system of <strong>100 kW or smaller</strong>. That covers practically every home and most small businesses.</li>
                    <li>The system must be connected to the grid, so an <strong>on-grid or hybrid</strong> system qualifies. An off-grid system has no grid connection and cannot export power.</li>
                </ul>
                <p class="mt-3">Net metering was created under the Renewable Energy Act of 2008 (Republic Act No. 9513), and its rules are set by the Energy Regulatory Commission (ERC).</p>
            </section>

            <section>
                <h2>How the credits are computed</h2>
                <p>Exported power is credited at your utility’s <strong>blended generation cost</strong>. That is only the generation part of your bill, so a kWh you export is worth less than a kWh you would otherwise buy, which also carries distribution, transmission, taxes and other charges.</p>
                <p>In practice, <strong>solar power you use yourself saves you more than solar power you export.</strong> That is why we size systems around how you actually use electricity:</p>
                <ul class="dots mt-3">
                    <li>Match the panels to your daytime use instead of making the system as big as possible.</li>
                    <li>Run heavy loads such as the washing machine, water pump or air-conditioning while the sun is up.</li>
                    <li>Add a battery (a hybrid system) to store the midday surplus for the evening and for brownouts.</li>
                </ul>
            </section>

            <section>
                <h2>The usual application steps</h2>
                <p class="mb-5">Each utility has its own forms and checklist, so always get the current list from your utility. Most applications go like this:</p>
                <ol class="nm-steps">
                    <li><h3>Design the system</h3><p>Plan an on-grid or hybrid system. The utility will ask for an electrical plan signed by a licensed electrical engineer.</p></li>
                    <li><h3>Permits and installation</h3><p>Get an electrical permit from your city or municipal office. After installation, the office inspects the work and issues the Certificate of Final Electrical Inspection (CFEI).</p></li>
                    <li><h3>Apply to your utility</h3><p>Submit the net-metering application with the documents your utility lists.</p></li>
                    <li><h3>Evaluation and inspection</h3><p>The utility reviews the design and equipment and inspects the installation.</p></li>
                    <li><h3>Sign the agreement</h3><p>You sign a Net-Metering Agreement with the utility.</p></li>
                    <li><h3>Meter installation</h3><p>The utility installs the bi-directional meter, and your exports start earning credits.</p></li>
                </ol>
            </section>

            <section class="rounded-[var(--radius-panel)] border border-[#f0d39a] bg-warn-50 p-5">
                <h3>The rules are changing</h3>
                <p>The Department of Energy and the ERC have been working to shorten and simplify net-metering applications, and the ERC proposed further changes in 2026. Check the latest requirements with your utility before you apply.</p>
            </section>

            <section>
                <h2>Planning a grid-connected system?</h2>
                <p>Use our free <a href="/pv-calculator" class="inline">PV Calculator</a> for a first estimate based on your monthly bill, read about <a href="/services#system-types" class="inline">on-grid, hybrid and off-grid systems</a>, or talk to us about your plans.</p>
                <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                    <a href="{{ Site::QUOTE_HREF }}" class="{{ Ui::button('primary', 'lg') }}">Request a Quote</a>
                    <a href="{{ Site::telHref($settings->phone_e164) }}" class="{{ Ui::button('secondary', 'lg') }}"><x-icon name="phone" class="size-4" /> Call {{ $settings->phone_display }}</a>
                </div>
            </section>

            <p class="border-t border-steel-200 pt-4 text-sm text-steel-600">Sources: Department of Energy, <a href="https://prod-cms.doe.gov.ph/documents/d/guest/the-5-ws-of-net-metering-primer-" target="_blank" rel="noopener" class="inline">The 5 W’s of Net Metering</a> (primer); Republic Act No. 9513. Last checked October 2026. This guide is general information; your utility’s current rules apply.</p>
        </div>
    </x-container>
</x-site-layout>
