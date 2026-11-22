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
        image="/images/service-training-banner-1280.jpg" srcset="/images/service-training-banner-640.jpg 640w, /images/service-training-banner-960.jpg 960w, /images/service-training-banner-1280.jpg 1280w, /images/service-training-banner-1920.jpg 1920w"
        imageAlt="A full seminar hall of solar PV installers and engineers listening to a talk, with the presentation on screen at the front" focus="40% 55%" />

    <x-section labelledby="trainer-title">
        <x-section-heading id="trainer-title" eyebrow="Your trainer" title="JF Legaspi" />
        <div class="mt-6 max-w-3xl space-y-4 text-lg text-steel-700">
            <p>JF Legaspi has a background in Electrical and Renewable Energy Engineering and lived and worked in Europe for more than three decades, where he was CTO of a solar company designing and managing mostly commercial and industrial solar PV installations.</p>
            <p>Today he is a regular resource person at solar PV seminars across the Philippines, organized by various solar companies. He is an educator by nature: he explains the technical side in plain words, so that every participant goes home able to do the work the right way.</p>
            <p>Read more <a href="/about" class="font-semibold text-steel-900 underline">about JF Legaspi and JFL Solar</a> or follow his <a href="/blog" class="font-semibold text-steel-900 underline">blog</a>.</p>
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

    @include('partials.service-cta', ['ctaTitle' => 'Invite JF to your seminar', 'ctaText' => 'Tell us about your audience, the topics you need and the date and venue you have in mind.'])
</x-site-layout>
