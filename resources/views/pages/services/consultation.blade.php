@php
    $card = 'rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6';
    $helps = [
        ['Bill and load review', 'We go through your electricity bills and the appliances you run during the day and at night, so the system is sized to how you really use power.'],
        ['System type and sizing', 'On-grid, hybrid or off-grid: we explain the trade-offs and size the inverter, the solar PV array and the battery to your needs and budget.'],
        ['Equipment selection', 'Inverters, batteries, solar panels and protection devices that are compatible with each other and right for your site.'],
        ['Second opinion on a quotation', 'Bring a quotation from any supplier. We explain what is in it, what is missing and whether the design makes sense.'],
        ['Existing solar PV systems', 'Troubleshooting, inverter and battery parameter settings, adding batteries, and upgrading or expanding a system.'],
        ['Net metering', 'Guidance on connecting your system to the grid and applying for net metering with Meralco or your electric cooperative.'],
    ];
    $steps = [
        ['Tell us about your project', 'Call us or send an inquiry with your location, your latest electricity bills and what you want the system to do.'],
        ['Consultation', 'We talk it through with you by phone, online or in person, and answer your questions in plain words.'],
        ['Site assessment', 'When needed, we inspect the roof or site (ocular inspection) to confirm what is feasible.'],
        ['Proposal and quotation', 'You receive a written proposal with the system design, equipment list, inclusions, exclusions and price.'],
    ];
@endphp
<x-site-layout title="Solar PV Consultation" path="/services/consultation"
    description="Talk to a solar PV engineer before you buy: bill and load review, system sizing, equipment selection, a second opinion on quotations, help with existing systems and net metering.">
    <x-page-header eyebrow="Services" title="Solar PV Consultation"
        intro="Talk to an engineer before you spend. We help you choose the right solar PV system, size it to your bills and loads, and avoid costly mistakes." />

    <x-section labelledby="help-title">
        <x-section-heading id="help-title" eyebrow="What we can help you with" title="Clear answers before you invest" />
        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($helps as [$t, $b])
                <section class="{{ $card }}">
                    <h3 class="text-xl font-bold text-steel-950">{{ $t }}</h3>
                    <p class="mt-2 text-steel-700">{{ $b }}</p>
                </section>
            @endforeach
        </div>
    </x-section>

    <x-section tone="muted" labelledby="who-title">
        <x-section-heading id="who-title" eyebrow="Who we advise" title="Homes, businesses and installers" />
        <div class="mt-10 grid gap-6 md:grid-cols-3">
            <section class="{{ $card }}">
                <h3 class="text-xl font-bold text-steel-950">Homeowners</h3>
                <p class="mt-2 text-steel-700">Lower bills and, with a battery, power during brownouts. We help you decide how much solar and battery you really need.</p>
            </section>
            <section class="{{ $card }}">
                <h3 class="text-xl font-bold text-steel-950">Businesses and industry</h3>
                <p class="mt-2 text-steel-700">JF Legaspi designed and managed mostly commercial and industrial solar PV installations as CTO of a solar company in Europe. That experience goes into every commercial project we advise on.</p>
            </section>
            <section class="{{ $card }}">
                <h3 class="text-xl font-bold text-steel-950">Installers and new solar PV companies</h3>
                <p class="mt-2 text-steel-700">Starting out or taking on a bigger project? Clients who buy their materials from us get free technical support, from installation guidance and commissioning to parameter settings and troubleshooting.</p>
            </section>
        </div>
    </x-section>

    <x-section labelledby="steps-title">
        <x-section-heading id="steps-title" eyebrow="How it works" title="From first call to proposal" />
        <ol class="mt-10 space-y-0 border-l-2 border-steel-300">
            @foreach ($steps as $i => [$t, $b])
                <li class="relative pb-8 pl-8 last:pb-0">
                    <span aria-hidden="true" class="absolute top-0 -left-[1.05rem] inline-flex size-8 items-center justify-center rounded-full bg-steel-900 font-display text-sm font-bold text-gold-400 tabular">{{ $i + 1 }}</span>
                    <h3 class="text-xl font-bold text-steel-950"><span class="sr-only">Step {{ $i + 1 }}: </span>{{ $t }}</h3>
                    <p class="mt-1 max-w-2xl text-steel-700">{{ $b }}</p>
                </li>
            @endforeach
        </ol>
        <p class="mt-8 max-w-3xl text-steel-700">Want a first estimate on your own? Try our free <a href="/pv-calculator" class="font-semibold text-steel-900 underline">PV Calculator</a>, build a quotation with <a href="/account/quotation" class="font-semibold text-steel-900 underline">Make a Quotation</a>, or read our guides on <a href="/hybrid-pv-system-guide" class="font-semibold text-steel-900 underline">hybrid</a> and <a href="/grid-tied-pv-system-guide" class="font-semibold text-steel-900 underline">grid-tied</a> solar PV systems and <a href="/net-metering" class="font-semibold text-steel-900 underline">net metering</a>.</p>
    </x-section>

    @include('partials.service-cta', ['ctaTitle' => 'Book a consultation', 'ctaText' => 'Tell us about your property, your bills and what you want your solar PV system to do.'])
</x-site-layout>
