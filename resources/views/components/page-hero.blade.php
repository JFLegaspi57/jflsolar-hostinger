@props(['eyebrow' => null, 'title', 'intro' => null, 'image', 'srcset' => null, 'imageAlt' => '', 'focus' => '35% 60%', 'flip' => false, 'tilt' => false])
{{-- Photo banner for inner pages, same design as the home page hero:
     mobile/tablet = photo band above the text; desktop = photo fading into the slate panel.
     flip = photo on the right and text on the left (desktop only).
     tilt = the photo leans in 3D toward the mouse (mouse/trackpad only; off for reduced motion). --}}
@if ($flip)
    <style>@media (width >= 64rem) { .hero-flip .hero-media { left: auto; right: 0 } .hero-flip .hero-fade { background-image: linear-gradient(to left, transparent 55%, var(--color-steel-950)) } .hero-flip .hero-text { margin-left: 0 } }</style>
@endif
@if ($tilt)
    <style>
        .hero-tilt .hero-media { overflow: hidden; perspective: 1100px; }
        .hero-tilt .hero-glare { opacity: 0; }
        @media (hover: hover) and (pointer: fine) and (prefers-reduced-motion: no-preference) {
            .hero-tilt .hero-media img {
                transform: rotateX(var(--tilt-y, 0deg)) rotateY(var(--tilt-x, 0deg)) translate3d(var(--shift-x, 0px), var(--shift-y, 0px), 0) scale(var(--tilt-zoom, 1));
                transition: transform .7s cubic-bezier(.2, .7, .2, 1);
                will-change: transform;
            }
            .hero-tilt.is-tilting .hero-media img { transition-duration: .15s; }
            .hero-tilt .hero-glare {
                background: radial-gradient(circle at var(--glare-x, 50%) var(--glare-y, 50%), rgb(255 255 255 / .22), transparent 45%);
                mix-blend-mode: soft-light; transition: opacity .5s;
            }
            .hero-tilt.is-tilting .hero-glare { opacity: 1; }
        }
    </style>
@endif
<section aria-labelledby="page-hero-title" class="on-dark relative isolate overflow-hidden bg-steel-950 text-white {{ $flip ? 'hero-flip' : '' }} {{ $tilt ? 'hero-tilt' : '' }}" @if ($tilt) data-hero-tilt @endif>
    <div class="hero-media relative h-56 sm:h-80 lg:absolute lg:inset-y-0 lg:left-0 lg:-z-20 lg:h-auto lg:w-[62%]">
        <img src="{{ $image }}" @if ($srcset) srcset="{{ $srcset }}" sizes="(min-width: 1024px) 62vw, 100vw" @endif width="1280" height="577"
            fetchpriority="high" decoding="async" alt="{{ $imageAlt }}" style="object-position: {{ $focus }}" class="absolute inset-0 h-full w-full object-cover" />
        @if ($tilt)<div aria-hidden="true" class="hero-glare absolute inset-0" style="pointer-events: none"></div>@endif
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
            <h1 id="page-hero-title" class="text-[2.35rem] leading-[1.08] font-extrabold sm:text-5xl lg:text-[3.15rem]">{{ $title }}</h1>
            @if ($intro)
                <p class="mt-6 text-lg text-steel-100">{{ $intro }}</p>
            @endif
            {{ $slot }}
        </div>
    </x-container>
</section>
@if ($tilt)
    <script>
        (function () {
            var hero = document.querySelector('[data-hero-tilt]');
            if (!hero || !window.matchMedia('(hover: hover) and (pointer: fine) and (prefers-reduced-motion: no-preference)').matches) return;
            var frame = 0, px = 0, py = 0;
            function apply() {
                frame = 0;
                hero.style.setProperty('--tilt-x', (px * 7).toFixed(2) + 'deg');
                hero.style.setProperty('--tilt-y', (-py * 5).toFixed(2) + 'deg');
                hero.style.setProperty('--shift-x', (-px * 18).toFixed(1) + 'px');
                hero.style.setProperty('--shift-y', (-py * 12).toFixed(1) + 'px');
                hero.style.setProperty('--glare-x', ((px + 0.5) * 100).toFixed(1) + '%');
                hero.style.setProperty('--glare-y', ((py + 0.5) * 100).toFixed(1) + '%');
            }
            hero.addEventListener('pointermove', function (e) {
                var r = hero.getBoundingClientRect();
                px = (e.clientX - r.left) / r.width - 0.5;   // -0.5 (left) … 0.5 (right)
                py = (e.clientY - r.top) / r.height - 0.5;   // -0.5 (top) … 0.5 (bottom)
                hero.classList.add('is-tilting');
                hero.style.setProperty('--tilt-zoom', '1.1');
                if (!frame) frame = requestAnimationFrame(apply);
            });
            hero.addEventListener('pointerleave', function () {
                px = py = 0;
                hero.classList.remove('is-tilting');
                hero.style.setProperty('--tilt-zoom', '1');
                apply();
            });
        })();
    </script>
@endif
