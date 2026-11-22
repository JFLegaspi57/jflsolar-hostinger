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
    $img = fn (string $name) => ['src' => "/images/service-{$name}-sample-1600.jpg", 'webp' => "/images/service-{$name}-sample-800.webp 800w, /images/service-{$name}-sample-1600.webp 1600w", 'jpg' => "/images/service-{$name}-sample-800.jpg 800w, /images/service-{$name}-sample-1600.jpg 1600w"];
@endphp
<x-site-layout title="SLD & Block Diagram" path="/services/sld-block-diagram"
    description="Solar PV plans drawn the way they are done in the Philippines: single-line diagram, wiring and block diagram, design calculations and datasheets on A3 sheets, ready for the design professional's signature.">
    <x-page-hero eyebrow="Services" title="SLD & Block Diagram"
        intro="Complete solar PV plans on A3 sheets: single-line diagram, wiring and block diagram, design calculations and datasheets, laid out with a title block ready for your design professional's signature and seal."
        image="/images/service-sld-banner-1280.jpg" srcset="/images/service-sld-banner-640.jpg 640w, /images/service-sld-banner-960.jpg 960w, /images/service-sld-banner-1280.jpg 1280w, /images/service-sld-banner-1920.jpg 1920w"
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

    <x-section tone="muted" labelledby="sample-title">
        <x-section-heading id="sample-title" eyebrow="Samples" title="From one of our plan sets" intro="A 16 kW hybrid solar PV system with 3 strings, rapid shutdown, 2 lithium batteries and an external automatic transfer switch." />
        <div class="mt-10 space-y-8">
            @foreach ([['sld', 'Single-line diagram of a 16 kW hybrid solar PV system with rapid shutdown device, DC boxes, inverter, batteries, AC boxes, automatic transfer switch and utility service entrance'], ['wiring', 'Color-coded wiring diagram of a 16 kW hybrid solar PV system: three solar panel strings, rapid shutdown device, DC boxes, inverter, two batteries, AC boxes, ATS and service entrance']] as [$name, $alt])
                @php $i = $img($name); @endphp
                <figure class="overflow-hidden rounded-[var(--radius-panel)] border border-steel-200 bg-white">
                    {{-- Opens a larger view on this page (no direct link to the image file). A clear layer over the picture blocks right-click saving and dragging. --}}
                    <button type="button" class="sld-zoom relative block w-full cursor-zoom-in" data-src="{{ $i['src'] }}" data-alt="{{ $alt }}" aria-label="Enlarge: {{ $name === 'sld' ? 'single-line diagram' : 'wiring diagram' }}">
                        <picture>
                            <source type="image/webp" srcset="{{ $i['webp'] }}" sizes="(min-width: 76rem) 72rem, 100vw">
                            <img src="{{ $i['src'] }}" srcset="{{ $i['jpg'] }}" sizes="(min-width: 76rem) 72rem, 100vw" alt="{{ $alt }}" loading="lazy" draggable="false" class="sld-protect h-auto w-full">
                        </picture>
                        <span class="absolute inset-0" aria-hidden="true"></span>
                    </button>
                    <figcaption class="border-t border-steel-200 px-4 py-2 text-sm text-steel-600">{{ $name === 'sld' ? 'Single-line diagram (sheet PV-2)' : 'Wiring diagram (sheet PV-1)' }} · tap to enlarge</figcaption>
                </figure>
            @endforeach
        </div>
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

    <dialog id="sld-viewer" class="m-auto max-h-[95vh] w-[min(96vw,100rem)] overflow-auto rounded-[var(--radius-panel)] bg-white p-0 backdrop:bg-steel-950/85" aria-label="Enlarged drawing">
        <div class="sticky top-0 z-10 flex justify-end bg-white/90 p-2"><button type="button" data-close class="rounded-full bg-steel-900 px-4 py-1.5 text-sm font-semibold text-white">Close</button></div>
        <div class="relative"><img src="" alt="" draggable="false" class="sld-protect h-auto w-full"><span class="absolute inset-0" aria-hidden="true"></span></div>
    </dialog>

    <style>
        .sld-protect { -webkit-touch-callout: none; -webkit-user-select: none; user-select: none; pointer-events: none; }
        .sld-hide .sld-protect, .sld-hide .hero-media img { visibility: hidden; }
        @media print { .sld-protect, .hero-media img, .hero-media picture { display: none !important; } }
    </style>
    <script>
        (function () {
            // Deterrents only: a web page cannot fully stop screenshots or saving. The watermark is the real protection.
            var dlg = document.getElementById('sld-viewer'), big = dlg.querySelector('img');
            document.querySelectorAll('.sld-zoom').forEach(function (b) {
                b.addEventListener('click', function () { big.src = b.dataset.src; big.alt = b.dataset.alt; dlg.showModal(); });
            });
            dlg.querySelector('[data-close]').addEventListener('click', function () { dlg.close(); });
            dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
            // No right-click menu, dragging or long-press saving on the drawings and the banner.
            var guarded = function (t) { return t.closest && t.closest('.sld-zoom, #sld-viewer, .hero-media'); };
            document.addEventListener('contextmenu', function (e) { if (guarded(e.target)) e.preventDefault(); });
            document.addEventListener('dragstart', function (e) { if (guarded(e.target)) e.preventDefault(); });
            // Block "Save page" (Ctrl/Cmd+S); printing shows the page without the drawings.
            document.addEventListener('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) e.preventDefault();
            });
            // Print Screen key: hide the drawings for a moment and try to clear the copied screenshot.
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
