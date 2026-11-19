@php
    use App\Support\Site;
    use App\Support\Ui;
@endphp
<x-site-layout title="Our Brands" path="/brands" :reviews-above-footer="false" description="The solar PV brands JFL Solar carries: GoodWe, Solis, Sungrow and Luxpower inverters, Jinko Solar, AE Solar and Seraphim panels, Dyness and Alltopelec batteries, and Projoy protection.">
    <x-slot:head>
        <style>
            .ob-card { display: flex; flex-direction: column; border: 1px solid var(--color-steel-200); border-radius: var(--radius-panel); background: #fff; overflow: hidden; transition: box-shadow .2s, transform .2s; }
            .ob-card:hover, .ob-card:focus-within { box-shadow: 0 12px 28px -12px rgba(31, 41, 55, .35); transform: translateY(-2px); }
            .ob-logo { display: flex; align-items: center; justify-content: center; height: 7rem; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-steel-100); background: #fff; }
            .ob-logo img { max-height: 3.25rem; max-width: 100%; width: auto; object-fit: contain; }
            .ob-body { display: flex; flex: 1; flex-direction: column; padding: 1.25rem 1.5rem 1.5rem; }
            .ob-tier { display: inline-block; margin-top: .4rem; padding: .15rem .6rem; border-radius: 999px; background: var(--color-gold-50); color: var(--color-steel-900); font-size: .8rem; font-weight: 600; }
            .ob-links { display: flex; flex-wrap: wrap; gap: .5rem 1.25rem; margin-top: auto; padding-top: 1rem; }
            .ob-links a { display: inline-flex; min-height: 2.75rem; align-items: center; gap: .35rem; font-weight: 600; color: var(--color-steel-900); text-decoration: underline; text-underline-offset: .2em; }
            .ob-links a:hover, .ob-links a:focus-visible { color: var(--color-gold-800); }
            .ob-grid { display: grid; gap: 1.25rem; }
            @media (min-width: 40rem) { .ob-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            @media (min-width: 64rem) { .ob-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
            @media (prefers-reduced-motion: reduce) { .ob-card, .ob-card:hover { transition: none; transform: none; } }
        </style>
    </x-slot:head>
    <x-page-hero eyebrow="About" title="Our brands"
        intro="We carry trusted solar PV brands that we install and test ourselves. Every brand below has its own page that explains who they are, what we carry and how to choose."
        image="/images/brands-banner-1280.jpg" srcset="/images/brands-banner-640.jpg 640w, /images/brands-banner-960.jpg 960w, /images/brands-banner-1280.jpg 1280w"
        imageAlt="Illustration: solar panels, an inverter, a battery and a breaker box lined up under a glowing sun" focus="75% 65%" flip />
    <x-container class="space-y-10 py-10 sm:py-14">
        @foreach ($groups as $g)
            <section aria-labelledby="ob-{{ $g['key'] }}">
                <h2 id="ob-{{ $g['key'] }}" class="text-2xl font-bold text-steel-950">{{ $g['heading'] }}</h2>
                <p class="mt-2 max-w-3xl text-steel-700">{{ $g['line'] }}</p>
                <ul class="ob-grid mt-6">
                    @foreach ($g['items'] as $b)
                        <li class="ob-card">
                            <a href="{{ $b['article'] ?? $b['productsHref'] }}" class="ob-logo" tabindex="-1" aria-hidden="true">
                                @if ($b['slug'] && is_file(public_path('images/brand-logo-'.$b['slug'].'.webp')))
                                    <img src="/images/brand-logo-{{ $b['slug'] }}.webp" alt="" loading="lazy" decoding="async">
                                @else
                                    <span class="text-2xl font-extrabold text-steel-900">{{ $b['brand'] }}</span>
                                @endif
                            </a>
                            <div class="ob-body">
                                <h3 class="text-xl font-bold text-steel-950">{{ $b['brand'] }}</h3>
                                @if ($b['tier'])<p><span class="ob-tier">{{ $b['tier'] }}</span></p>@endif
                                <p class="mt-3 text-steel-700">{{ $b['summary'] }}</p>
                                <p class="ob-links">
                                    @if ($b['article'])<a href="{{ $b['article'] }}">About {{ $b['brand'] }} <span aria-hidden="true">→</span></a>@endif
                                    @if ($b['count'] > 0)
                                        <a href="{{ $b['productsHref'] }}">{{ $b['count'] }} {{ $b['count'] === 1 ? 'product' : 'products' }}</a>
                                    @endif
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
        <section aria-labelledby="ob-help" class="rounded-[var(--radius-panel)] border border-steel-200 bg-steel-50 p-6 sm:p-8">
            <h2 id="ob-help" class="text-xl font-bold text-steel-950">Not sure which brand fits your project?</h2>
            <p class="mt-2 max-w-3xl text-steel-700">Tell us about your home or business and we will suggest the inverter, panels and battery that suit it. Brands can be mixed: for example a GoodWe inverter with Jinko Solar panels and a Dyness battery.</p>
            <div class="mt-5 flex flex-wrap gap-3">
                <a href="{{ Site::QUOTE_HREF }}" class="{{ Ui::button('primary', 'lg') }}">Inquire Now</a>
                <a href="/account/quotation" class="{{ Ui::button('secondary', 'lg') }}">Make a Quotation</a>
            </div>
        </section>
    </x-container>
</x-site-layout>
