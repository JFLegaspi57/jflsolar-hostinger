@php
    $card = 'rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6';
    $sheets = [
        ['Sheet PV-1', ['Wiring diagram: every DC, AC, grounding and communication cable, color-coded', 'Block diagram of the whole system', 'Legends of all symbols and equipment']],
        ['Sheet PV-2', ['Single-line diagram (SLD) with breaker, SPD and cable ratings', 'Service entrance post details', 'General notes based on the Philippine Electrical Code', 'Vicinity map of the site']],
        ['Sheet PV-3', ['System design analysis: DC and AC protection and cable sizing', 'Datasheets and specifications of the inverter, battery and solar panels', 'Schedule of loads', 'Short-circuit analysis and voltage drop calculation']],
    ];
    $needs = [
        'The equipment: inverter, batteries, solar panels and how the strings are arranged',
        'The owner\'s name and the site address',
        'The service entrance: kWh meter and main breaker',
        'Approximate cable runs: roof to inverter, inverter to panel board',
        'The loads, if you need a schedule of loads',
    ];
@endphp
<x-site-layout title="SLD & Block Diagram" path="/services/sld-block-diagram" :reviews-above-footer="false"
    description="Solar PV plans drawn the way they are done in the Philippines: single-line diagram, wiring and block diagram, design calculations and datasheets on A3 sheets, ready for the design professional's signature.">
    <x-page-hero eyebrow="Services" title="SLD & Block Diagram"
        intro="Complete solar PV plans on A3 sheets: single-line diagram, wiring and block diagram, design calculations and datasheets, laid out with a title block ready for your design professional's signature and seal."
        image="/images/service-sld-plan-1280.jpg" srcset="/images/service-sld-plan-640.jpg 640w, /images/service-sld-plan-960.jpg 960w, /images/service-sld-plan-1280.jpg 1280w, /images/service-sld-plan-1920.jpg 1920w"
        imageAlt="Wiring diagram and block diagram of a 16 kW Solis hybrid solar PV system with three PV strings, rapid shutdown device, Dyness batteries and automatic transfer switch" focus="30% 45%" :tilt="false" protect />

    <x-section labelledby="sheets-title">
        <x-section-heading id="sheets-title" eyebrow="What you get" title="A complete plan set" />
        <div class="mt-10 grid gap-6 lg:grid-cols-3">
            @foreach ($sheets as [$t, $items])
                <section class="{{ $card }}">
                    <h3 class="text-xl font-bold text-steel-950">{{ $t }}</h3>
                    <ul class="mt-4 space-y-1.5">
                        @foreach ($items as $p)
                            <li class="flex items-start gap-2 text-steel-800"><x-icon name="check" class="mt-1 size-4 shrink-0 text-gold-800" />{{ $p }}</li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
        <p class="mt-6 max-w-3xl text-steel-700">Every sheet has a title block with the project title, owner, location, installer and a space for the design professional. You receive the plans as a PDF, ready to print.</p>
    </x-section>


    <x-section labelledby="for-title">
        <div class="grid gap-10 lg:grid-cols-2">
            <div>
                <x-section-heading id="for-title" eyebrow="Who it is for" title="Installers, owners and engineers" />
                <ul class="mt-6 space-y-3 text-steel-700">
                    <li><strong class="text-steel-900">Installers</strong> who need proper plans for a client, a permit or a net-metering application.</li>
                    <li><strong class="text-steel-900">Homeowners and businesses</strong> who want their solar PV system documented the right way.</li>
                    <li><strong class="text-steel-900">Engineers</strong> who want drafting support for their own designs.</li>
                </ul>
            </div>
            <div>
                <x-section-heading eyebrow="What we need from you" title="To draw your plans" />
                <ul class="mt-6 space-y-1.5">
                    @foreach ($needs as $n)
                        <li class="flex items-start gap-2 text-steel-800"><x-icon name="check" class="mt-1 size-4 shrink-0 text-gold-800" />{{ $n }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <p class="mt-8 max-w-3xl rounded-[var(--radius-control)] border border-gold-500 bg-gold-50 px-4 py-3 text-steel-800"><strong>Note:</strong> the plans must be reviewed, signed and sealed by a licensed design professional where your LGU or utility requires it. We prepare the drawings and calculations; the signing engineer verifies the design.</p>
    </x-section>

    <style>
        .sld-hide .hero-media img { visibility: hidden; }
        @media print { .hero-media img, .hero-media picture { display: none !important; } }
    </style>
    <script>
        (function () {
            // Deterrents only: a web page cannot fully stop screenshots or saving.
            // No right-click menu, dragging or long-press saving on the banner.
            var guarded = function (t) { return t.closest && t.closest('.hero-media'); };
            document.addEventListener('contextmenu', function (e) { if (guarded(e.target)) e.preventDefault(); });
            document.addEventListener('dragstart', function (e) { if (guarded(e.target)) e.preventDefault(); });
            // Block "Save page" (Ctrl/Cmd+S); printing leaves the banner out.
            document.addEventListener('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) e.preventDefault();
            });
            // Print Screen key: hide the banner for a moment and try to clear the copied screenshot.
            document.addEventListener('keyup', function (e) {
                if (e.key !== 'PrintScreen') return;
                document.documentElement.classList.add('sld-hide');
                try { navigator.clipboard && navigator.clipboard.writeText('Drawings © JFL Solar · www.jflsolar.ph'); } catch (err) {}
                setTimeout(function () { document.documentElement.classList.remove('sld-hide'); }, 1500);
            });
        })();
    </script>

    @include('partials.service-cta', ['ctaTitle' => 'Need plans for your solar PV system?', 'ctaText' => 'Send us your system details and the site address, and we will tell you what we need to draw your plans.'])
</x-site-layout>
