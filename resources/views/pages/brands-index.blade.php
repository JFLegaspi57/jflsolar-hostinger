@php
    use App\Support\Site;
    use App\Support\Ui;
@endphp
<x-site-layout title="Our Brands" path="/brands" :reviews-above-footer="false" description="The brands JFL Solar carries, by product type: solar panels, inverters, batteries, mounting hardware, cables and safety devices from GoodWe, Solis, Sungrow, Luxpower, Jinko Solar, Trina Solar, Dyness and more.">
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
            .ob-wordmark { font-size: 1.6rem; font-weight: 800; letter-spacing: .01em; color: var(--color-steel-900); text-align: center; line-height: 1.15; }
            .ob-items { list-style: disc; padding-left: 1.2rem; }
            .ob-items li + li { margin-top: .3rem; }
            .ob-items a { color: inherit; text-decoration: underline; text-underline-offset: .15em; }
            .ob-items a:hover, .ob-items a:focus-visible { color: var(--color-steel-950); }
            .ob-grid { display: grid; gap: 1.25rem; }
            @media (min-width: 40rem) { .ob-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            @media (min-width: 64rem) { .ob-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
            @media (prefers-reduced-motion: reduce) { .ob-card, .ob-card:hover { transition: none; transform: none; } }
        </style>
    </x-slot:head>
    <x-page-header eyebrow="About" title="Our brands"
        intro="Every brand we carry, by product type. We install and test what we sell ourselves. Our main brands have their own page that explains who they are, what we carry and how to choose." />
    <x-container class="space-y-10 py-10 sm:py-14">
        @foreach ($groups as $g)
            <section aria-labelledby="ob-{{ $g['key'] }}">
                <h2 id="ob-{{ $g['key'] }}" class="text-2xl font-bold text-steel-950">{{ $g['heading'] }}</h2>
                @if ($g['line'])<p class="mt-2 max-w-3xl text-steel-700">{{ $g['line'] }}</p>@endif
                <ul class="ob-grid mt-6">
                    @foreach ($g['items'] as $b)
                        @php $logo = $b['slug'] && is_file(public_path('images/brand-logo-'.$b['slug'].'.webp')); $count = $b['products']->count(); @endphp
                        <li class="ob-card">
                            <a href="{{ $b['article'] ?? $b['productsHref'] }}" class="ob-logo" tabindex="-1" aria-hidden="true">
                                @if ($logo)
                                    <img src="/images/brand-logo-{{ $b['slug'] }}.webp" alt="" loading="lazy" decoding="async">
                                @else
                                    <span class="ob-wordmark">{{ $b['name'] }}</span>
                                @endif
                            </a>
                            <div class="ob-body">
                                <h3 class="text-xl font-bold text-steel-950">{{ $b['name'] }}</h3>
                                @if ($b['tier'])<p><span class="ob-tier">{{ $b['tier'] }}</span></p>@endif
                                @if ($b['summary'])
                                    <p class="mt-3 text-steel-700">{{ $b['summary'] }}</p>
                                @else
                                    <ul class="ob-items mt-3 text-steel-700">
                                        @foreach ($b['products']->take(3) as $p)
                                            <li><a href="/products/{{ $p->slug }}">{{ $p->name }}</a></li>
                                        @endforeach
                                    </ul>
                                @endif
                                <p class="ob-links">
                                    @if ($b['article'])<a href="{{ $b['article'] }}">About {{ $b['name'] }} <span aria-hidden="true">→</span></a>@endif
                                    <a href="{{ $b['productsHref'] }}">{{ $count }} {{ $count === 1 ? 'product' : 'products' }}</a>
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
                <a href="/quotation" class="{{ Ui::button('secondary', 'lg') }}">Make a Quotation</a>
            </div>
        </section>
    </x-container>
</x-site-layout>
