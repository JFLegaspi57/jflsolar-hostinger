@php
    use App\Support\PageText;
    use App\Support\Site;
    use App\Support\Ui;
    $get = fn ($key) => $blocks->firstWhere('key', $key);
    $intro = $get('about.intro');
    $history = $get('about.history');
    $mission = $get('about.mission');
    $vision = $get('about.vision');
    $founder = $get('about.founder');
    $why = $blocks->where('section', 'why');
    $img = fn ($b) => $b ? $blockImages->get($b->key)?->first() : null;
    $founderPhoto = $img($founder);
    // Any other "about" blocks staff add appear after the standard ones.
    $known = ['about.intro', 'about.history', 'about.mission', 'about.vision', 'about.founder'];
    $extra = $blocks->where('section', 'about')->reject(fn ($b) => in_array($b->key, $known, true));
@endphp
<x-site-layout title="About JFL Solar" path="/about" description="JFL Solar provides solar PV installation, consultancy and equipment distribution in the Philippines. Our mission, vision and approach.">
    <x-page-header :eyebrow="PageText::get('about.header.eyebrow')" :title="$intro?->title ?? 'About JFL Solar'" :intro="$settings->service_area" />

    <x-section labelledby="story-title">
        <div class="grid gap-10 lg:grid-cols-2">
            @if ($intro)
                <div>
                    <h2 id="story-title" class="sr-only">Who we are</h2>
                    @if ($img($intro))
                        <div class="mb-6 aspect-[16/9] overflow-hidden rounded-[var(--radius-panel)] bg-steel-100"><x-media-image :asset="$img($intro)" sizes="(min-width: 1024px) 560px, 100vw" /></div>
                    @endif
                    <x-paragraphs :text="$intro->body" class="text-lg text-steel-700" />
                </div>
            @endif
            @if ($history)
                <x-block-panel :block="$history" :image="$img($history)" />
            @endif
        </div>
    </x-section>

    @if ($mission || $vision)
        <x-section tone="dark" labelledby="mv-title">
            <h2 id="mv-title" class="sr-only">Mission and vision</h2>
            <div class="grid gap-10 md:grid-cols-2">
                @foreach (array_filter([$mission, $vision]) as $b)
                    <figure>
                        @if ($img($b))
                            <div class="mb-5 aspect-[16/9] overflow-hidden rounded-[var(--radius-panel)] bg-steel-900"><x-media-image :asset="$img($b)" sizes="(min-width: 768px) 560px, 100vw" /></div>
                        @endif
                        <figcaption class="flex flex-wrap items-center gap-3 text-sm font-semibold tracking-wide text-gold-400 uppercase">
                            <span class="rule-gold" aria-hidden="true"></span>{{ $b->title }}<x-claim-marker :block="$b" />
                        </figcaption>
                        <blockquote class="mt-4 text-lg leading-relaxed text-steel-100 sm:text-xl">{{ $b->body }}</blockquote>
                    </figure>
                @endforeach
            </div>
        </x-section>
    @endif

    @if ($founder)
        <x-section labelledby="founder-title">
            <div class="grid items-start gap-8 md:grid-cols-[14rem_1fr]">
                <div class="aspect-square w-40 overflow-hidden rounded-[var(--radius-panel)] bg-steel-900 md:w-full">
                    @if ($founderPhoto)
                        <x-media-image :asset="$founderPhoto" sizes="224px" />
                    @else
                        <div role="img" aria-label="Founder photo pending" class="flex h-full items-center justify-center font-display text-5xl font-extrabold text-gold-500">
                            {{ mb_substr(collect(preg_split('/\s+/', $founder->title))->map(fn ($w) => mb_substr($w, 0, 1))->implode(''), 0, 3) }}
                        </div>
                    @endif
                </div>
                <div>
                    <p class="flex flex-wrap items-center gap-3 text-sm font-semibold tracking-wide text-gold-800 uppercase">
                        <span class="rule-gold" aria-hidden="true"></span> {{ PageText::get('about.founder.eyebrow') }} <x-claim-marker :block="$founder" />
                    </p>
                    <h2 id="founder-title" class="mt-3 text-3xl font-extrabold text-steel-950">{{ $founder->title }}</h2>
                    @if ($founder->subtitle)<p class="mt-1 font-semibold text-steel-600">{{ $founder->subtitle }}</p>@endif
                    <x-paragraphs :text="$founder->body" class="mt-4 max-w-2xl text-lg text-steel-700" />
                </div>
            </div>
        </x-section>
    @endif

    @if ($why->isNotEmpty())
        <x-section tone="muted" labelledby="why-title">
            <x-section-heading id="why-title" :eyebrow="PageText::get('about.why.eyebrow')" :title="PageText::get('about.why.title')" />
            <div class="mt-10 grid gap-6 md:grid-cols-2">
                @foreach ($why as $b)
                    <x-block-panel :block="$b" :image="$img($b)" />
                @endforeach
            </div>
        </x-section>
    @endif

    @if ($extra->isNotEmpty())
        <x-section labelledby="more-title">
            <h2 id="more-title" class="sr-only">More about us</h2>
            <div class="grid gap-6 md:grid-cols-2">
                @foreach ($extra as $b)
                    <x-block-panel :block="$b" :image="$img($b)" />
                @endforeach
            </div>
        </x-section>
    @endif

    <x-section labelledby="warranty-title">
        <div class="grid gap-10 lg:grid-cols-2">
            <div>
                <x-section-heading id="warranty-title" :eyebrow="PageText::get('about.warranty.eyebrow')" :title="PageText::get('about.warranty.title')" />
                <p class="mt-4 text-steel-700">{{ $settings->general_warranty ?: 'Warranty terms to be confirmed.' }}</p>
            </div>
            <div>
                <h2 class="mb-4 text-xl font-bold text-steel-950">{{ PageText::get('about.location.title') }}</h2>
                <x-addresses :settings="$settings" />
            </div>
        </div>
    </x-section>
</x-site-layout>
