@props(['eyebrow' => null, 'title', 'intro' => null, 'image', 'srcset' => null, 'imageAlt' => '', 'focus' => '35% 60%', 'flip' => false, 'tilt' => true, 'titleClass' => null, 'protect' => false, 'tall' => false])
{{-- Photo banner for inner pages, same design as the home page hero:
     mobile/tablet = photo band above the text; desktop = photo fading into the slate panel.
     flip = photo on the right and text on the left (desktop only).
     tilt = the photo leans in 3D toward the mouse (on by default; see partials/hero-tilt).
     titleClass = smaller title for long titles (blog posts); protect = no right-click saving or dragging of the photo.
     tall = a taller banner that shows more of the photo (blog). --}}
@if ($tall)
    @once
        <style>
            /* Taller banner: shows more of the photo (it still fills the space). */
            .hero-tall .hero-media { height: 18rem; }
            @media (min-width: 40rem) { .hero-tall .hero-media { height: 26rem; } }
            @media (min-width: 64rem) { .hero-tall { min-height: 32rem; display: flex; align-items: center; } .hero-tall .hero-media { height: auto; } }
        </style>
    @endonce
@endif
@if ($flip)
    <style>@media (width >= 64rem) { .hero-flip .hero-media { left: auto; right: 0 } .hero-flip .hero-fade { background-image: linear-gradient(to left, transparent 55%, var(--color-steel-950)) } .hero-flip .hero-text { margin-left: 0 } }</style>
@endif
<section aria-labelledby="page-hero-title" class="on-dark relative isolate overflow-hidden bg-steel-950 text-white {{ $flip ? 'hero-flip' : '' }} {{ $tilt ? 'hero-tilt' : '' }} {{ $tall ? 'hero-tall' : '' }}" @if ($tilt) data-hero-tilt @endif>
    <div class="hero-media relative h-56 sm:h-80 lg:absolute lg:inset-y-0 lg:left-0 lg:-z-20 lg:h-auto lg:w-[62%]">
        @php $webpSrcset = \App\Support\Ui::webpSrcset($srcset ?: $image.' 1280w'); @endphp
        <picture>
            @if ($webpSrcset)<source type="image/webp" srcset="{{ $webpSrcset }}" sizes="(min-width: 1024px) 62vw, 100vw">@endif
            <img src="{{ $image }}" @if ($srcset) srcset="{{ $srcset }}" sizes="(min-width: 1024px) 62vw, 100vw" @endif width="1280" height="577"
                fetchpriority="high" decoding="async" alt="{{ $imageAlt }}" style="object-position: {{ $focus }}@if ($protect); -webkit-touch-callout: none; user-select: none @endif" class="absolute inset-0 h-full w-full object-cover"
                @if ($protect) draggable="false" oncontextmenu="return false" @endif />
        </picture>
        @if ($tilt)<div aria-hidden="true" class="hero-glare absolute inset-0"></div>@endif
        <div aria-hidden="true" class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-steel-950 to-transparent lg:hidden"></div>
        <div aria-hidden="true" class="hero-fade absolute inset-0 hidden bg-gradient-to-r from-transparent from-55% to-steel-950 lg:block"></div>
    </div>
    <x-container class="relative pt-4 pb-14 sm:pb-16 lg:py-28">
        <div class="hero-text max-w-xl lg:ml-auto lg:max-w-[33rem]">
            @if ($eyebrow)
                <p class="mb-5 flex items-center gap-3 text-sm font-semibold tracking-wide text-gold-400 uppercase">
                    <span class="rule-gold" aria-hidden="true"></span>{{ $eyebrow }}
                </p>
            @endif
            <h1 id="page-hero-title" class="{{ $titleClass ?? 'text-[2.35rem] leading-[1.08] font-extrabold sm:text-5xl lg:text-[3.15rem]' }}">{{ $title }}</h1>
            @if ($intro)
                <p class="mt-6 text-lg text-steel-100">{{ $intro }}</p>
            @endif
            {{ $slot }}
        </div>
    </x-container>
</section>
@if ($tilt)
    @include('partials.hero-tilt')
@endif
