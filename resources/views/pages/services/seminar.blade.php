@php
    use App\Support\Seminars;
    use App\Support\Site;
    use App\Support\Ui;
    $over = Seminars::isOver($s);
    $img = fn (int $w, string $ext = 'jpg') => '/images/'.$s['poster'].'-'.$w.'.'.$ext;
    $card = 'rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6';
    $join = '/contact?from=seminar&seminar='.$s['slug'].'#quote';
@endphp
<x-site-layout :title="$s['title'].' – '.$s['kind'].', '.$s['when']" :path="$s['url']" :reviews-above-footer="false"
    :description="$s['kind'].': '.$s['title'].', '.$s['when'].' in Surigao City. Guest speaker JF Legaspi of JFL Solar. '.$s['who'].'.'">
    <style>
        .sem-top { padding-top: 2rem !important; }
        .sem-grid { display: grid; gap: 2.5rem; align-items: start; }
        @media (min-width: 64rem) { .sem-grid { grid-template-columns: minmax(0, 1fr) 26rem; } }
        .sem-kind { border-radius: .5rem; letter-spacing: .1em; }
        .sem-fact { border-top: 4px solid var(--color-gold-400); padding-top: .75rem; }
        .sem-label { letter-spacing: .12em; }
        .sem-tagline { font-style: italic; }
        .sem-poster { box-shadow: 0 10px 30px rgb(15 23 42 / .18); }
        .sem-num { background: var(--color-gold-400); }
    </style>
    <x-section labelledby="sem-title" class="sem-top">
        <nav aria-label="Breadcrumb" class="text-sm text-steel-600">
            <a href="/services" class="underline">Services</a> <span aria-hidden="true">›</span>
            <a href="{{ Seminars::BASE }}" class="underline">Training &amp; Seminars</a> <span aria-hidden="true">›</span>
            <span aria-current="page">{{ $s['title'] }}</span>
        </nav>
        <div class="sem-grid mt-6">
            <div>
                <p class="inline-block sem-kind bg-steel-900 px-3 py-1.5 text-sm font-extrabold uppercase text-white">{{ $s['kind'] }}</p>
                <h1 id="sem-title" class="mt-4 text-4xl font-extrabold leading-tight text-steel-950 sm:text-5xl">{{ $s['title'] }}</h1>
                <p class="sem-tagline mt-3 text-xl text-steel-700">“{{ $s['tagline'] }}”</p>
                @if ($over)
                    <p class="mt-6 rounded-[var(--radius-panel)] border border-steel-300 bg-steel-50 p-4 font-semibold text-steel-800">This seminar has ended. See our <a href="{{ Seminars::BASE }}" class="underline">Training &amp; Seminars</a> page for upcoming ones.</p>
                @endif
                <dl class="mt-8 grid gap-5 sm:grid-cols-3">
                    @foreach (['When' => $s['when'], 'Where' => $s['where'], 'Who' => $s['who']] as $label => $val)
                        <div class="sem-fact">
                            <dt class="text-sm sem-label font-bold uppercase text-steel-600">{{ $label }}</dt>
                            <dd class="mt-1 text-lg font-bold text-steel-950">{{ $val }}</dd>
                        </div>
                    @endforeach
                </dl>
                <p class="mt-8 text-lg text-steel-700">JFL Solar, in partnership with {{ $s['partner'] }}, brings a two-day seminar and workshop on installing solar PV systems the right way: from the basics of solar energy and choosing the components, to a hands-on installation demonstration and the safety rules every installer must follow.</p>
                @unless ($over)
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ $join }}" class="{{ Ui::button('primary', 'lg') }}">Reserve a Slot</a>
                        <a href="{{ Site::telHref($settings->phone_e164) }}" class="{{ Ui::button('secondary', 'lg') }}">Call {{ $settings->phone_display }}</a>
                    </div>
                @endunless
            </div>
            <a href="{{ $img(1080) }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-[var(--radius-panel)] border border-steel-200 sem-poster" aria-label="Open the seminar poster in full size">
                <picture>
                    <source type="image/webp" srcset="{{ $img(640, 'webp') }} 640w, {{ $img(1080, 'webp') }} 1080w" sizes="(min-width: 64rem) 26rem, 100vw">
                    <img src="{{ $img(640) }}" srcset="{{ $img(640) }} 640w, {{ $img(1080) }} 1080w" sizes="(min-width: 64rem) 26rem, 100vw" width="1080" height="1350"
                        alt="Poster of the {{ $s['kind'] }} on {{ $s['title'] }}, {{ $s['when'] }}, {{ $s['where'] }}" class="h-auto w-full" decoding="async">
                </picture>
            </a>
        </div>
    </x-section>

    <x-section tone="muted" labelledby="sem-speakers">
        <x-section-heading id="sem-speakers" eyebrow="Speakers" title="Learn from the best" />
        <div class="mt-10 grid gap-6 md:grid-cols-2">
            @foreach ($s['speakers'] as [$role, $name, $about])
                <section class="{{ $card }}">
                    <p class="text-sm sem-label font-extrabold uppercase text-gold-800">{{ $role }}</p>
                    <h3 class="mt-1 text-2xl font-bold text-steel-950">{{ $name }}</h3>
                    <p class="mt-2 text-steel-700">{{ $about }}</p>
                </section>
            @endforeach
        </div>
        <p class="mt-6 max-w-3xl text-steel-700">Read more <a href="/about" class="font-semibold text-steel-900 underline">about JF Legaspi and JFL Solar</a>.</p>
    </x-section>

    <x-section labelledby="sem-topics">
        <x-section-heading id="sem-topics" eyebrow="Program" title="Topics and activities" />
        <ol class="mt-10 grid max-w-5xl gap-5 sm:grid-cols-2">
            @foreach ($s['topics'] as $i => [$topic, $detail])
                <li class="flex gap-4">
                    <span class="flex size-10 shrink-0 items-center justify-center sem-num rounded-full font-extrabold text-steel-950" aria-hidden="true">{{ $i + 1 }}</span>
                    <span><span class="block text-lg font-bold text-steel-950">{{ $topic }}</span><span class="text-steel-700">{{ $detail }}</span></span>
                </li>
            @endforeach
        </ol>
    </x-section>

    <x-section tone="muted" labelledby="sem-more">
        <x-section-heading id="sem-more" eyebrow="Keep learning" title="Before and after the seminar" />
        <p class="mt-6 max-w-3xl text-lg text-steel-700">Get a head start with our free online course <a href="{{ \App\Support\Course::PATH }}" class="font-semibold text-steel-900 underline">{{ \App\Support\Course::TITLE }}</a>, or read our guides on <a href="/hybrid-pv-system-guide" class="font-semibold text-steel-900 underline">hybrid</a> and <a href="/grid-tied-pv-system-guide" class="font-semibold text-steel-900 underline">grid-tied</a> solar PV systems.</p>
    </x-section>

    @include('partials.service-cta', ['ctaTitle' => $over ? 'Invite JF to your seminar' : 'Join the seminar', 'ctaText' => $over ? 'Tell us about your audience, the topics you need and the date and venue you have in mind.' : 'Send us an inquiry to reserve your slot or ask about the seminar.'])
</x-site-layout>
