@props([
    'slug',                 // e.g. 'goodwe' → /brands/goodwe
    'brand',                // display name, e.g. 'GoodWe'
    'filter',               // brand value used by the /products filter, e.g. 'Jinko Solar'
    'title',
    'intro',
    'description',
    'toc' => [],            // [section id => label] for the article's own sections
    'products',             // this brand's published products (live prices and availability)
    'settings',
    'productsIntro' => 'Prices and availability are kept up to date in our shop.',
    'cta' => null,
])
@php
    use App\Support\Site;
    use App\Support\Ui;
    $hasQuestions = isset($questions) && $questions->isNotEmpty();
    $toc = $toc + ['products' => $brand.' products we sell'] + ($hasQuestions ? ['questions' => 'Common questions'] : []);
@endphp
{{-- Shared layout of the brand articles (/brands/{slug}); each brand logo on /products opens its article. --}}
<x-site-layout :title="$title" :path="'/brands/'.$slug" :reviews-above-footer="false" :description="$description">
    <x-slot:head>
        <style>
            .ba h2 { margin-bottom: .5rem; font-size: 1.5rem; font-weight: 800; color: var(--color-steel-950); scroll-margin-top: 6rem; }
            .ba h3 { margin-bottom: .25rem; font-size: 1.125rem; font-weight: 700; color: var(--color-steel-950); }
            .ba p + p { margin-top: .75rem; }
            .ba ul.dots li { margin-left: 1.25rem; list-style: disc; }
            .ba ul.dots li + li { margin-top: .35rem; }
            .ba a.inline { font-weight: 600; color: var(--color-steel-900); text-decoration: underline; }
            .ba a.inline:hover { color: var(--color-gold-800); }
            .ba-facts { display: grid; gap: .75rem; grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr)); }
            .ba-facts div { border: 1px solid var(--color-steel-200); border-radius: var(--radius-panel); background: #fff; padding: .9rem 1rem; }
            .ba-facts dt { font-size: .75rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--color-steel-600); }
            .ba-facts dd { margin-top: .2rem; font-weight: 700; color: var(--color-steel-950); }
            .ba-facts dd small { display: block; font-weight: 400; font-size: .85rem; color: var(--color-steel-700); }
            .ba-family + .ba-family { margin-top: 1rem; }
            .ba-family { border-left: 4px solid var(--color-gold-500); padding-left: 1rem; }
            .ba-note { border: 1px solid var(--color-steel-200); border-radius: var(--radius-panel); background: var(--color-steel-50); padding: 1.25rem; }
            .ba-table-wrap { overflow-x: auto; border: 1px solid var(--color-steel-200); border-radius: var(--radius-panel); }
            .ba-table { width: 100%; min-width: 34rem; border-collapse: collapse; font-size: .9rem; background: #fff; }
            .ba-table th, .ba-table td { padding: .6rem .75rem; text-align: left; border-bottom: 1px solid var(--color-steel-200); white-space: nowrap; }
            .ba-table thead th { background: var(--color-steel-50); font-size: .75rem; letter-spacing: .03em; text-transform: uppercase; color: var(--color-steel-700); }
            .ba-table tbody tr:last-child td, .ba-table tbody tr:last-child th { border-bottom: 0; }
            .ba-table tbody th { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 700; color: var(--color-steel-950); }
            .ba-toc a { display: inline-flex; min-height: 2.75rem; align-items: center; font-weight: 600; color: var(--color-steel-900); text-decoration: underline; }
            .ba-qa + .ba-qa { margin-top: 1.25rem; }
            .ba-tier { border: 1px solid var(--color-steel-200); border-left: 6px solid var(--color-steel-400); border-radius: var(--radius-panel); background: #fff; padding: 1.1rem 1.25rem; }
            .ba-tier.is-tier1 { border-left-color: var(--color-gold-500); }
            .ba-tier-head { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; }
            .ba .ba-tier h3 { margin: 0; font-size: 1.15rem; }
            .ba-tier-badge { display: inline-flex; align-items: center; min-height: 1.9rem; padding: 0 .8rem; border-radius: 999px; font-size: .85rem; font-weight: 800; letter-spacing: .02em; background: var(--color-steel-100); color: var(--color-steel-800); }
            .ba-tier.is-tier1 .ba-tier-badge { background: var(--color-gold-500); color: var(--color-steel-950); }
            .ba-brands a { display: inline-flex; min-height: 2.75rem; align-items: center; padding: 0 .9rem; border: 1px solid var(--color-steel-300); border-radius: 999px; font-size: .875rem; font-weight: 600; color: var(--color-steel-800); }
            .ba-brands a:hover { border-color: var(--color-steel-900); }
            /* Banner: the brand's logo on a white card, over the site's dark slate with a soft sun-gold glow and a faint solar-cell grid. */
            .ba-logo { position: relative; overflow: hidden; padding: 2rem 1rem; text-align: center;
                background:
                    radial-gradient(ellipse 55% 90% at 85% 0%, rgb(255 176 4 / .28), transparent 70%),
                    radial-gradient(ellipse 45% 80% at 10% 100%, rgb(255 176 4 / .12), transparent 70%),
                    linear-gradient(135deg, var(--color-steel-950), var(--color-steel-800)); }
            .ba-logo::before { content: ""; position: absolute; inset: 0; pointer-events: none; opacity: .07;
                background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 2.25rem 2.25rem;
                mask-image: linear-gradient(to bottom, transparent, #000 35%, #000 65%, transparent); -webkit-mask-image: linear-gradient(to bottom, transparent, #000 35%, #000 65%, transparent); }
            .ba-logo::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 4px; background: linear-gradient(90deg, transparent, var(--color-gold-500), transparent); }
            .ba-logo-card { position: relative; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-panel); background: #fff; padding: 1rem 1.5rem; box-shadow: 0 18px 40px -16px rgb(0 0 0 / .55), 0 0 0 1px rgb(255 255 255 / .1); }
            .ba-logo img { display: block; width: auto; height: 3.5rem; max-width: min(17rem, 72vw); object-fit: contain; }
            @media (min-width: 40rem) { .ba-logo { padding: 3rem 1rem; } .ba-logo-card { padding: 1.25rem 2.25rem; } .ba-logo img { height: 4.5rem; max-width: 21rem; } }
        </style>
    </x-slot:head>
    @if (is_file(public_path('images/brand-logo-'.$slug.'.webp')))
        <div class="ba-logo">
            <span class="ba-logo-card"><img src="/images/brand-logo-{{ $slug }}.webp" alt="{{ $brand }} logo" fetchpriority="high" decoding="async" /></span>
        </div>
    @endif
    <x-page-header eyebrow="Brand guide" :title="$title" :intro="$intro" />
    <x-container class="py-10 sm:py-14">
        <div class="ba space-y-10 text-steel-800" style="max-width: 48rem">
            <nav aria-label="On this page" class="ba-toc ba-note">
                <p class="font-bold text-steel-950">On this page</p>
                <ul class="mt-1 grid sm:grid-cols-2" style="column-gap: 1.5rem">
                    @foreach ($toc as $id => $label)<li><a href="#{{ $id }}">{{ $label }}</a></li>@endforeach
                </ul>
            </nav>

            {{ $slot }}

            <section aria-labelledby="products">
                <h2 id="products">{{ $brand }} products we sell</h2>
                @if ($products->isNotEmpty())
                    <p>{{ $productsIntro }}</p>
                    <ul class="mt-4 grid gap-4 sm:grid-cols-2">
                        @foreach ($products as $p)
                            <li class="flex"><x-product-card :product="$p" /></li>
                        @endforeach
                    </ul>
                    <a href="/products?brand={{ urlencode($filter) }}#results" class="{{ Ui::button('secondary', 'md', 'mt-5') }}">See all {{ $brand }} products</a>
                @else
                    <p>Our {{ $brand }} stock changes often. Contact us for current models, prices and availability.</p>
                @endif
            </section>

            @if ($hasQuestions)
                <section aria-labelledby="questions">
                    <h2 id="questions">Common questions</h2>
                    {{ $questions }}
                </section>
            @endif

            <section class="ba-note">
                <h2>{{ $cta ?? 'Planning a solar PV system with '.$brand.'?' }}</h2>
                <p>Tell us your monthly bill and what you want on backup, and we’ll recommend the right inverter, panels, battery and protection.</p>
                <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                    <a href="{{ Site::QUOTE_HREF }}" class="{{ Ui::button('primary', 'lg') }}">Request a Quote</a>
                    <a href="{{ Site::telHref($settings->phone_e164) }}" class="{{ Ui::button('secondary', 'lg') }}"><x-icon name="phone" class="size-4" /> Call {{ $settings->phone_display }}</a>
                </div>
            </section>

            <nav aria-label="Other brands we carry" class="ba-brands">
                <p class="font-bold text-steel-950">Other brands we carry</p>
                <ul class="mt-2 flex flex-wrap gap-2">
                    @foreach (\App\Support\BrandArticles::available() as $s => $a)
                        @continue($s === $slug)
                        <li><a href="/brands/{{ $s }}">{{ $a['brand'] }}</a></li>
                    @endforeach
                </ul>
            </nav>

            @isset($sources)
                <p class="border-t border-steel-200 pt-4 text-sm text-steel-600">Sources: {{ $sources }}
                    Last checked November 2026. {{ $brand }} is a trademark of its owner; JFL Solar is an independent seller.</p>
            @endisset
        </div>
    </x-container>
</x-site-layout>
