@php
    use App\Support\PageText;
    use App\Support\Services;
    use App\Support\Site;
    use App\Support\Ui;
@endphp
<x-site-layout path="/" :reviews-above-footer="false" description="Solar PV installation, consultancy and equipment distribution for homes, businesses and installers across the Philippines. Request a quote from JFL Solar.">
    {{-- Hero — owner-supplied rooftop photo. Mobile/tablet: photo band above the text. Desktop: split, photo fading into the slate panel. --}}
    <section aria-labelledby="hero-title" class="hero-tilt on-dark relative isolate overflow-hidden bg-steel-950 text-white" data-hero-tilt>
        <div class="hero-media relative h-56 sm:h-80 lg:absolute lg:inset-y-0 lg:left-0 lg:-z-20 lg:h-auto lg:w-[62%]">
            <picture>
                <source type="image/webp" srcset="/images/home-roof2-640.webp 640w, /images/home-roof2-960.webp 960w, /images/home-roof2-1280.webp 1280w" sizes="(min-width: 1024px) 62vw, 100vw">
                <img src="/images/home-roof2-1280.jpg" srcset="/images/home-roof2-640.jpg 640w, /images/home-roof2-960.jpg 960w, /images/home-roof2-1280.jpg 1280w"
                    sizes="(min-width: 1024px) 62vw, 100vw" width="1280" height="577" fetchpriority="high" decoding="async"
                    alt="Two JFL Solar technicians in hard hats and safety vests installing panels on a large commercial rooftop"
                    class="absolute inset-0 h-full w-full object-cover" style="object-position: 5% 55%" />
            </picture>
            <div aria-hidden="true" class="hero-glare absolute inset-0"></div>
            <div aria-hidden="true" class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-steel-950 to-transparent lg:hidden"></div>
            <div aria-hidden="true" class="absolute inset-0 hidden bg-gradient-to-r from-transparent from-55% to-steel-950 lg:block"></div>
        </div>
        <x-container class="relative pt-4 pb-14 sm:pb-16 lg:py-28">
            <div class="max-w-xl lg:ml-auto lg:max-w-[33rem]">
                <p class="mb-5 flex items-center gap-3 text-sm font-semibold tracking-wide text-gold-400 uppercase">
                    <span class="rule-gold" aria-hidden="true"></span>{{ PageText::get('home.hero.eyebrow') }}
                </p>
                <h1 id="hero-title" class="text-[2.35rem] leading-[1.08] font-extrabold sm:text-5xl lg:text-[3.15rem]">{{ PageText::get('home.hero.title') }}</h1>
                <p class="mt-6 text-lg text-steel-100">{{ PageText::get('home.hero.intro') }}</p>
                @if ($stats = \App\Support\Visitors::publicStats())
                    <style>#live-stats { max-width: 27rem; } @media (min-width: 640px) { #live-stats { grid-template-columns: repeat(4, minmax(0, 1fr)); } }</style>
                    <dl id="live-stats" aria-label="Website visitors so far, updated live" class="mt-6 grid grid-cols-2 gap-1.5">
                        @foreach (['today' => 'Today’s visitors', 'first' => 'First-time visitors', 'returning' => 'Returning visitors', 'visits' => 'Total visits'] as $key => $label)
                            <div style="display: flex; flex-direction: column-reverse" class="rounded-[var(--radius-control)] border border-white/15 bg-white/5 px-2 py-1">
                                <dt class="leading-tight text-steel-300" style="font-size: .65rem">{{ $label }}</dt>
                                <dd data-stat="{{ $key }}" class="font-display text-base font-extrabold text-white tabular">{{ number_format($stats[$key]) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    <p class="mt-2 flex items-center gap-2 text-xs text-steel-300"><span aria-hidden="true" style="width: .5rem; height: .5rem; border-radius: 9999px; background: #34d399; display: inline-block"></span> Live</p>
                    <script>
                    (() => {
                        const box = document.getElementById('live-stats');
                        const fmt = new Intl.NumberFormat('en-PH');
                        const refresh = async () => {
                            if (document.hidden) return;
                            try {
                                const r = await fetch('/stats/visitors', { headers: { Accept: 'application/json' }, cache: 'no-store' });
                                if (!r.ok) return;
                                const d = await r.json();
                                for (const el of box.querySelectorAll('[data-stat]')) {
                                    const v = d[el.dataset.stat];
                                    if (Number.isFinite(v)) el.textContent = fmt.format(v);
                                }
                            } catch (e) {}
                        };
                        setInterval(refresh, 20000);
                        document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
                    })();
                    </script>
                @endif
            </div>
        </x-container>
    </section>
    @include('partials.hero-tilt')

    {{-- Customer reviews right under the banner (on other pages they sit above the footer). --}}
    @include('partials.reviews')

    {{-- Two pathways --}}
    <section aria-labelledby="paths-title" class="border-b border-steel-200 bg-white">
        <x-container class="py-14 sm:py-16">
            <h2 id="paths-title" class="sr-only">Choose your path</h2>
            <div class="grid gap-4 md:grid-cols-2 md:gap-6">
                @foreach ([
                    ['accent' => false, 'eyebrow' => PageText::get('home.path1.eyebrow'), 'title' => PageText::get('home.path1.title'), 'points' => PageText::lines(PageText::get('home.path1.points')), 'primary' => ['/services', PageText::get('home.path1.button')], 'secondary' => ['/packages?type=complete', PageText::get('home.path1.link')]],
                    ['accent' => true, 'eyebrow' => PageText::get('home.path2.eyebrow'), 'title' => PageText::get('home.path2.title'), 'points' => PageText::lines(PageText::get('home.path2.points')), 'primary' => ['/products', PageText::get('home.path2.button')], 'secondary' => ['/packages?type=materials', PageText::get('home.path2.link')]],
                ] as $panel)
                    <div class="flex flex-col rounded-[var(--radius-panel)] border p-6 sm:p-8 {{ $panel['accent'] ? 'border-gold-200 bg-gold-50' : 'border-steel-200 bg-steel-50' }}">
                        <p class="text-sm font-semibold tracking-wide text-steel-600 uppercase">{{ $panel['eyebrow'] }}</p>
                        <h3 class="mt-2 text-2xl font-extrabold text-steel-950 sm:text-3xl">{{ $panel['title'] }}</h3>
                        <ul class="mt-5 flex-1 space-y-2">
                            @foreach ($panel['points'] as $p)
                                <li class="flex items-start gap-2.5 text-steel-800"><x-icon name="check" class="mt-1 size-4 text-gold-800" />{{ $p }}</li>
                            @endforeach
                        </ul>
                        <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2">
                            <a href="{{ $panel['primary'][0] }}" class="{{ Ui::button($panel['accent'] ? 'dark' : 'primary') }}">{{ $panel['primary'][1] }}</a>
                            <a href="{{ $panel['secondary'][0] }}" class="inline-flex min-h-11 items-center gap-1.5 font-semibold text-steel-900 hover:underline">{{ $panel['secondary'][1] }} <x-icon name="arrow" class="size-4" /></a>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-container>
    </section>

    {{-- Services --}}
    <x-section labelledby="services-title">
        <x-section-heading id="services-title" :eyebrow="PageText::get('home.services.eyebrow')" :title="PageText::get('home.services.title')" />
        <ul class="mt-10 grid gap-px overflow-hidden rounded-[var(--radius-panel)] border border-steel-200 bg-steel-200 md:grid-cols-3">
            @foreach (Services::services() as $s)
                <li class="flex flex-col bg-white p-6 sm:p-8">
                    <span class="inline-flex size-11 items-center justify-center rounded-full bg-gold-500 text-steel-950"><x-icon :name="$s['icon']" class="size-5" /></span>
                    <h3 class="mt-5 text-xl font-bold text-steel-950">{{ $s['title'] }}</h3>
                    <p class="mt-2 flex-1 text-steel-600">{{ $s['body'] }}</p>
                    <a href="{{ $s['href'] }}" class="mt-5 inline-flex min-h-11 items-center gap-1.5 font-semibold text-steel-900 hover:underline">{{ $s['cta'] }} <x-icon name="arrow" class="size-4" /></a>
                </li>
            @endforeach
        </ul>
    </x-section>

    {{-- Featured packages --}}
    @if ($featuredPackages->isNotEmpty())
        <x-section tone="muted" labelledby="packages-title">
            <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                <x-section-heading id="packages-title" :eyebrow="PageText::get('home.packages.eyebrow')" :title="PageText::get('home.packages.title')" :intro="PageText::get('home.packages.intro')" />
                <a href="/packages" class="{{ Ui::button('secondary', 'md', 'self-start md:self-auto') }}">{{ PageText::get('home.packages.button') }}</a>
            </div>
            <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($featuredPackages as $pkg)
                    <x-package-card :pkg="$pkg" />
                @endforeach
            </div>
        </x-section>
    @endif

    {{-- Product categories --}}
    <x-section labelledby="categories-title">
        <div class="grid gap-10 lg:grid-cols-[1fr_1.4fr]">
            <x-section-heading id="categories-title" :eyebrow="PageText::get('home.equipment.eyebrow')" :title="PageText::get('home.equipment.title')" :intro="PageText::get('home.equipment.intro')" />
            <ul class="divide-y divide-steel-200 border-y border-steel-200">
                @foreach ($categories as $c)
                    <li>
                        <a href="/products?category={{ $c->slug }}" class="group flex min-h-16 items-center justify-between gap-4 py-3 hover:bg-steel-50 sm:px-2">
                            <span>
                                <span class="block text-lg font-bold text-steel-950 group-hover:underline">{{ $c->name }}</span>
                                @if ($c->description)<span class="block text-sm text-steel-600">{{ $c->description }}</span>@endif
                            </span>
                            <span class="flex shrink-0 items-center gap-3 text-sm text-steel-600">
                                <span class="tabular">{{ $products->where('category_id', $c->id)->count() }} {{ PageText::get('home.equipment.count') }}</span>
                                <x-icon name="arrow" class="size-4 text-steel-900" />
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </x-section>

    {{-- Process --}}
    <x-section tone="dark" labelledby="process-title">
        <x-section-heading dark id="process-title" :eyebrow="PageText::get('home.process.eyebrow')" :title="PageText::get('home.process.title')" :intro="PageText::get('home.process.intro')" />
        <ol class="mt-12 grid gap-x-8 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (Services::processSteps() as $i => $step)
                <li class="relative border-t border-white/15 pt-5">
                    <span class="font-display text-sm font-bold text-gold-400 tabular">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <h3 class="mt-1 text-xl font-bold text-white">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-steel-300">{{ $step['body'] }}</p>
                </li>
            @endforeach
        </ol>
    </x-section>

    {{-- Projects --}}
    @if ($featuredProjects->isNotEmpty())
        <x-section labelledby="projects-title">
            <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                <x-section-heading id="projects-title" :eyebrow="PageText::get('home.projects.eyebrow')" :title="PageText::get('home.projects.title')" />
                <a href="/projects" class="{{ Ui::button('secondary', 'md', 'self-start md:self-auto') }}">{{ PageText::get('home.projects.button') }}</a>
            </div>
            <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($featuredProjects as $p)
                    <x-project-card :project="$p" />
                @endforeach
            </div>
        </x-section>
    @endif

    {{-- Why JFL --}}
    @if ($why->isNotEmpty())
        <x-section tone="muted" labelledby="why-title">
            <x-section-heading id="why-title" :eyebrow="PageText::get('home.why.eyebrow')" :title="PageText::get('home.why.title')" />
            <dl class="mt-10 grid gap-x-10 gap-y-8 md:grid-cols-2">
                @foreach ($why as $b)
                    <div class="border-l-4 border-gold-500 pl-5">
                        <dt class="text-lg font-bold text-steel-950">{{ $b->title }}</dt>
                        <dd class="mt-1.5 text-steel-700">
                            <x-claim-marker :block="$b" class="mb-1.5" />
                            <x-paragraphs :text="$b->body" />
                        </dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-10"><a href="/about" class="inline-flex min-h-11 items-center gap-1.5 font-semibold text-steel-900 hover:underline">{{ PageText::get('home.why.link') }} <x-icon name="arrow" class="size-4" /></a></p>
        </x-section>
    @endif

</x-site-layout>
