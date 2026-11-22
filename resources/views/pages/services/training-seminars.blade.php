@php
    use App\Support\Course;
    $card = 'rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6';
    $topics = [
        'How on-grid, hybrid and off-grid solar PV systems work',
        'Sizing the solar PV array, inverter and battery from real bills and loads',
        'String design: voltages, currents and temperature checks',
        'DC and AC protection devices, grounding and rapid shutdown',
        'Inverter and battery parameter settings and BMS communication',
        'Safe installation practices and commissioning',
        'Net metering and grid connection',
    ];
@endphp
<x-site-layout title="Solar PV Training & Seminars" path="/services/training-seminars"
    description="Solar PV training and seminars with JF Legaspi: seminars for solar companies and organizations, a free online course, and hands-on guidance for new installers.">
    <x-page-hero eyebrow="Services" title="Training & Seminars"
        intro="Learn from the best... learn it the right way! Solar PV training for installers, engineers, students and anyone who wants to understand solar PV systems properly."
        image="/images/service-training-center-1280.jpg" srcset="/images/service-training-center-640.jpg 640w, /images/service-training-center-960.jpg 960w, /images/service-training-center-1280.jpg 1280w, /images/service-training-center-1920.jpg 1920w"
        imageAlt="Trainees in matching JFL Solar shirts giving a thumbs up in front of the JFL Solar Training Center" focus="50% 45%" />

    <x-section labelledby="trainer-title">
        <x-section-heading id="trainer-title" eyebrow="Your trainer" title="JF Legaspi" />
        {{-- Text with JF's YouTube Short beside it (stacked on phones). --}}
        <div class="mt-6 flex flex-col items-start gap-6 md:flex-row md:gap-8">
        <div class="max-w-3xl min-w-0 space-y-4 text-lg text-steel-700">
            <p>JF Legaspi has a background in Electrical and Renewable Energy Engineering and lived and worked in Europe for more than three decades, where he was CTO of a solar company designing and managing mostly commercial and industrial solar PV installations.</p>
            <p>Today he is a regular resource person at solar PV seminars across the Philippines, organized by various solar companies. He is an educator by nature: he explains the technical side in plain words, so that every participant goes home able to do the work the right way.</p>
            <p>We also have our own <strong class="text-steel-900">JFL Solar Training Center</strong>, at the same address as our warehouse@if ($settings->warehouse_address): {{ $settings->warehouse_address }}@endif. It is fitted with solar panels, hybrid inverters, batteries and a wiring demo wall, so participants learn hands-on with real equipment.</p>
            <p>Read more <a href="/about" class="font-semibold text-steel-900 underline">about JF Legaspi and JFL Solar</a> or follow his <a href="/blog" class="font-semibold text-steel-900 underline">blog</a>.</p>
        </div>
        <div class="mx-auto w-[13rem] shrink-0 overflow-hidden md:mx-0 rounded-[var(--radius-panel)] border border-steel-200 bg-steel-950" style="aspect-ratio: 9 / 16">
            <iframe src="https://www.youtube-nocookie.com/embed/KYYRJXnzyQk" title="JF Legaspi, your trainer (YouTube Short)" loading="lazy"
                allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin" style="width: 100%; height: 100%; border: 0"></iframe>
        </div>
        </div>
    </x-section>

    <x-section tone="muted" labelledby="offer-title">
        <x-section-heading id="offer-title" eyebrow="What we offer" title="Ways to learn with us" />
        <div class="mt-10 grid gap-6 md:grid-cols-2">
            <section class="{{ $card }}">
                <h3 class="text-xl font-bold text-steel-950">Seminars and talks</h3>
                <p class="mt-2 text-steel-700">Invite JF Legaspi as resource person for your company, dealers, installers, school, cooperative or organization. Topics and length are tailored to your audience, from beginners to experienced installers.</p>
            </section>
            <section class="{{ $card }}">
                <h3 class="text-xl font-bold text-steel-950">Online course</h3>
                <p class="mt-2 text-steel-700"><a href="{{ Course::PATH }}" class="font-semibold text-steel-900 underline">{{ Course::TITLE }}</a>: a free online course for JFL Solar members, lesson by lesson, with your answers checked along the way. A certificate of completion is available on request for a fee.</p>
            </section>
            <section class="{{ $card }}">
                <h3 class="text-xl font-bold text-steel-950">Hands-on guidance for new installers</h3>
                <p class="mt-2 text-steel-700">Doing your first bigger installation? When you buy your materials from us, we guide you and your team through the installation, commissioning, parameter settings and troubleshooting.</p>
            </section>
            <section class="{{ $card }}">
                <h3 class="text-xl font-bold text-steel-950">Free learning resources</h3>
                <p class="mt-2 text-steel-700">Our guides on <a href="/hybrid-pv-system-guide" class="font-semibold text-steel-900 underline">hybrid</a> and <a href="/grid-tied-pv-system-guide" class="font-semibold text-steel-900 underline">grid-tied</a> solar PV systems and <a href="/net-metering" class="font-semibold text-steel-900 underline">net metering</a>, in English and Tagalog, and the <a href="/forum" class="font-semibold text-steel-900 underline">forum</a> where members ask their questions.</p>
            </section>
        </div>
    </x-section>

    <x-section labelledby="topics-title">
        <x-section-heading id="topics-title" eyebrow="Topics" title="What we teach" />
        <ul class="mt-8 grid max-w-4xl gap-3 sm:grid-cols-2">
            @foreach ($topics as $t)
                <li class="flex items-start gap-2 text-steel-800"><x-icon name="check" class="mt-1 size-4 text-gold-800" />{{ $t }}</li>
            @endforeach
        </ul>
    </x-section>

    @php
        // Training photos: [number, available widths, alt text]. Files: /images/training-photo-NN-WIDTH.(jpg|webp)
        $photos = [
            [13, [640, 1280], 'Trainees in matching JFL Solar shirts posing in front of the JFL Solar Training Center building'],
            [14, [640, 1280], 'Training session inside the JFL Solar Training Center, with inverters and a meter on the demo wall'],
            [15, [640, 1280], 'Lecture on AC and DC circuit breakers at the JFL Solar Training Center'],
            [16, [640, 1280], 'Hands-on wiring practice on the inverter demo wall at the JFL Solar Training Center'],
            [17, [640, 854], 'Group photo of a training batch inside the JFL Solar Training Center'],
            [1, [640, 1280], 'Seminar participants giving a thumbs up around inverters and batteries on the training tables'],
            [2, [640, 1280], 'Hands-on training group posing beside a lithium battery and a wired inverter demo board'],
            [3, [640, 1280], 'Large seminar group photo in a hotel function room with a solar panel on display'],
            [4, [640, 1280], 'Training graduates holding their certificates in matching shirts'],
            [5, [640, 1280], 'Seminar participants seated and standing for a group photo in front of solar panels'],
            [6, [640, 1280], 'Seminar group giving thumbs up in a function room with a yellow wall and a solar panel'],
            [7, [640, 1280], 'Group photo at an in-depth seminar on solar energy, with the seminar banner on screen'],
            [8, [640, 1280], 'Full room of seminar participants at blue-covered tables posing for a group photo'],
            [9, [640, 1280], 'Seminar participants at blue tables listening to a talk'],
            [10, [640, 1224], 'JF Legaspi presenting commissioning steps on a screen to a standing audience'],
            [11, [640, 720], 'Small-group training photo in a classroom with wooden walls'],
            [12, [640, 1280], 'JF Legaspi taking a selfie with seminar participants at red tables behind him'],
        ];
        $tp = fn (int $n, int $w, string $ext = 'jpg') => sprintf('/images/training-photo-%02d-%d.%s', $n, $w, $ext);
    @endphp
    <x-section tone="muted" labelledby="gallery-title">
        <x-section-heading id="gallery-title" eyebrow="Gallery" title="From our trainings and seminars" />
        <ul class="mt-10 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
            @foreach ($photos as $k => [$n, $ws, $alt])
                <li>
                    <button type="button" class="tr-photo group block w-full overflow-hidden rounded-[var(--radius-panel)] bg-steel-200" data-i="{{ $k }}" aria-label="Open photo: {{ $alt }}">
                        <picture>
                            <source type="image/webp" srcset="{{ collect($ws)->map(fn ($w) => $tp($n, $w, 'webp').' '.$w.'w')->implode(', ') }}" sizes="(min-width: 64rem) 24rem, 50vw">
                            <img src="{{ $tp($n, $ws[0]) }}" srcset="{{ collect($ws)->map(fn ($w) => $tp($n, $w).' '.$w.'w')->implode(', ') }}" sizes="(min-width: 64rem) 24rem, 50vw"
                                alt="{{ $alt }}" loading="lazy" decoding="async" draggable="false" class="aspect-[4/3] h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        </picture>
                    </button>
                </li>
            @endforeach
        </ul>
    </x-section>

    <dialog id="tr-viewer" class="m-auto w-[min(96vw,80rem)] bg-transparent p-0 backdrop:bg-steel-950/90" aria-label="Training photos">
        <figure class="relative">
            <img src="" alt="" draggable="false" class="mx-auto max-h-[85vh] w-auto rounded-[var(--radius-panel)]">
            <figcaption class="mt-3 flex items-center justify-between gap-3 text-sm text-white">
                <button type="button" data-prev class="rounded-full bg-white/15 px-4 py-2 font-semibold hover:bg-white/25">← Previous</button>
                <span data-count></span>
                <span class="flex gap-2">
                    <button type="button" data-next class="rounded-full bg-white/15 px-4 py-2 font-semibold hover:bg-white/25">Next →</button>
                    <button type="button" data-close class="rounded-full bg-white px-4 py-2 font-semibold text-steel-950">Close</button>
                </span>
            </figcaption>
        </figure>
    </dialog>
    <script>
        (function () {
            var photos = @json(collect($photos)->map(fn ($p) => ['src' => $tp($p[0], end($p[1])), 'alt' => $p[2]])->values());
            var dlg = document.getElementById('tr-viewer'), img = dlg.querySelector('img'), count = dlg.querySelector('[data-count]'), at = 0;
            function show(i) { at = (i + photos.length) % photos.length; img.src = photos[at].src; img.alt = photos[at].alt; count.textContent = (at + 1) + ' / ' + photos.length; }
            document.querySelectorAll('.tr-photo').forEach(function (b) { b.addEventListener('click', function () { show(+b.dataset.i); dlg.showModal(); }); });
            dlg.querySelector('[data-prev]').addEventListener('click', function () { show(at - 1); });
            dlg.querySelector('[data-next]').addEventListener('click', function () { show(at + 1); });
            dlg.querySelector('[data-close]').addEventListener('click', function () { dlg.close(); });
            dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
            dlg.addEventListener('keydown', function (e) { if (e.key === 'ArrowRight') show(at + 1); if (e.key === 'ArrowLeft') show(at - 1); });
            // Deterrent against saving: no context menu or dragging on the photos.
            document.querySelectorAll('.tr-photo, #tr-viewer img').forEach(function (el) {
                el.addEventListener('contextmenu', function (e) { e.preventDefault(); });
                el.addEventListener('dragstart', function (e) { e.preventDefault(); });
            });
        })();
    </script>

    @include('partials.service-cta', ['ctaTitle' => 'Invite JF to your seminar', 'ctaText' => 'Tell us about your audience, the topics you need and the date and venue you have in mind.'])
</x-site-layout>
