@props(['project', 'admin' => false])
@php
    use App\Support\Labels;
    use App\Support\Pricing;
    use App\Support\Site;
    use App\Support\Ui;
    $size = $project->system_size_confirmed ? Pricing::formatCapacity($project->system_size_kwp, 'kWp') : null;
    $videos = \App\Support\VideoEmbed::all($project->video_urls ?? null);
    $others = $project->photos->reject(fn ($p) => $project->cover && $p->id === $project->cover->id)->values();
    $facts = [
        ['Type', Labels::PROJECT_TYPE[$project->project_type], false],
        ['Location', $project->location ?? '—', false],
        ['System size', $size ?? 'To be confirmed', false],
        ['Scope', $project->scope ?? '—', true],
    ];
@endphp
<article aria-labelledby="project-title">
    <x-container class="py-8 sm:py-10">
        <x-breadcrumbs :items="[['href' => '/', 'label' => 'Home'], ['href' => '/projects', 'label' => 'Projects'], ['label' => $project->title]]" />
        @if ($project->is_sample && ! Site::isProduction())
            <p role="note" class="mt-6 rounded-[var(--radius-control)] border border-dashed border-steel-400 bg-steel-50 px-4 py-3 text-steel-800"><strong>Sample project.</strong> This entry is not a completed JFL Solar installation. It demonstrates how projects will be presented.</p>
        @endif
        <h1 id="project-title" class="mt-6 max-w-3xl text-3xl font-extrabold text-steel-950 sm:text-4xl">{{ $project->title }}</h1>
        <dl class="mt-5 grid max-w-3xl grid-cols-2 gap-4 border-y border-steel-200 py-4 sm:grid-cols-4">
            @foreach ($facts as [$label, $value, $wide])
                <div class="{{ $wide ? 'col-span-2 sm:col-span-1' : '' }}">
                    <dt class="text-xs font-semibold tracking-wide text-steel-600 uppercase">{{ $label }}</dt>
                    <dd class="mt-0.5 font-semibold text-steel-900">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        <figure class="mt-8 overflow-hidden rounded-[var(--radius-panel)] border border-steel-200">
            <div class="aspect-[16/9] bg-steel-100" @if ($project->cover) data-lightbox @endif>
                @if ($project->cover)
                    <x-media-image :asset="$project->cover" :admin="$admin" sizes="(min-width: 1216px) 1152px, 100vw" priority />
                @else
                    <x-placeholder-image :label="$project->is_sample ? 'Sample — placeholder image' : 'Project photo pending'" />
                @endif
            </div>
            @if ($project->cover?->caption)<figcaption class="px-4 py-2 text-sm text-steel-600">{{ $project->cover->caption }}</figcaption>@endif
        </figure>

        @if ($videos)
            <section aria-labelledby="videos-title" class="mt-12">
                <h2 id="videos-title" class="text-xl font-bold text-steel-950">{{ count($videos) === 1 ? 'Video' : 'Videos' }}</h2>
                <ul class="mt-4 grid gap-6 {{ count($videos) > 1 ? 'lg:grid-cols-2' : '' }}">
                    @foreach ($videos as $i => $v)
                        <li>
                            <div class="overflow-hidden rounded-[var(--radius-panel)] border border-steel-200 bg-steel-950"
                                style="aspect-ratio: {{ $v['vertical'] ? '9 / 16' : '16 / 9' }}; {{ $v['vertical'] ? 'max-width: 22rem; margin-inline: auto;' : '' }}">
                                <iframe src="{{ $v['src'] }}" title="{{ $project->title }} — video {{ $i + 1 }} ({{ $v['service'] }})" loading="lazy"
                                    allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share; fullscreen" allowfullscreen
                                    referrerpolicy="strict-origin-when-cross-origin" style="width: 100%; height: 100%; border: 0"></iframe>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <div class="mt-10 grid gap-10 lg:grid-cols-[1fr_20rem]">
            <x-paragraphs :text="$project->description ?: $project->summary" class="max-w-2xl text-lg text-steel-700" />
            <aside class="rounded-[var(--radius-panel)] bg-steel-50 p-6">
                <h2 class="text-lg font-bold text-steel-950">Planning something similar?</h2>
                <p class="mt-2 text-steel-600">Tell us about your site and we will recommend a system.</p>
                <a href="{{ Site::QUOTE_HREF }}" class="{{ Ui::button('primary', 'md', 'mt-4 w-full') }}">Inquire Now</a>
            </aside>
        </div>

        @if ($others->isNotEmpty())
            <section aria-labelledby="gallery-title" class="mt-12">
                <h2 id="gallery-title" class="text-xl font-bold text-steel-950">Photos</h2>
                <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($others as $photo)
                        <li>
                            <figure class="overflow-hidden rounded-[var(--radius-panel)] border border-steel-200">
                                <div class="aspect-[4/3] bg-steel-100" data-lightbox><x-media-image :asset="$photo" :admin="$admin" sizes="(min-width: 1024px) 380px, (min-width: 640px) 50vw, 100vw" /></div>
                                @if ($photo->caption)<figcaption class="px-3 py-2 text-sm text-steel-600">{{ $photo->caption }}</figcaption>@endif
                            </figure>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </x-container>
</article>

{{-- Photo pop-up: click any project photo to see it large; arrows / swipe go through all photos, Esc or a click outside closes. --}}
<style>
    [data-lightbox] { cursor: zoom-in; }
    [data-lightbox] img { transition: transform .25s ease; }
    [data-lightbox]:hover img { transform: scale(1.03); }
    #lightbox { position: fixed; inset: 0; margin: auto; padding: 0; border: 0; background: transparent; width: 100vw; height: 100vh; max-width: 100vw; max-height: 100vh; overflow: hidden; }
    #lightbox::backdrop { background: rgba(10, 15, 25, .9); }
    #lightbox .lb-stage { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .6rem; padding: 3.5rem 3.75rem 1.25rem; }
    #lightbox img { max-width: 100%; max-height: calc(100vh - 7rem); width: auto; height: auto; border-radius: .6rem; box-shadow: 0 20px 50px rgba(0, 0, 0, .5); background: #111; }
    #lightbox .lb-cap { color: #e5e7eb; font-size: .95rem; text-align: center; max-width: 60rem; min-height: 1.2em; }
    #lightbox button { position: absolute; display: grid; place-items: center; width: 2.9rem; height: 2.9rem; border-radius: 9999px; border: 0; background: rgba(255, 255, 255, .92); color: #111827; font-size: 1.6rem; line-height: 1; cursor: pointer; box-shadow: 0 4px 12px rgba(0, 0, 0, .3); }
    #lightbox button:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 2px; }
    #lightbox .lb-close { top: .75rem; right: .75rem; }
    #lightbox .lb-prev { left: .6rem; top: 50%; transform: translateY(-50%); }
    #lightbox .lb-next { right: .6rem; top: 50%; transform: translateY(-50%); }
    #lightbox .lb-count { position: absolute; top: 1.4rem; left: 1rem; color: #e5e7eb; font-size: .9rem; }
    @media (max-width: 40rem) { #lightbox .lb-stage { padding: 3.5rem .5rem 1rem; } #lightbox .lb-prev, #lightbox .lb-next { top: auto; bottom: 1rem; transform: none; } }
</style>
<dialog id="lightbox" aria-label="Project photos">
    <div class="lb-stage"><img src="" alt=""><p class="lb-cap"></p></div>
    <span class="lb-count" aria-live="polite"></span>
    <button type="button" class="lb-prev" aria-label="Previous photo">‹</button>
    <button type="button" class="lb-next" aria-label="Next photo">›</button>
    <button type="button" class="lb-close" aria-label="Close">×</button>
</dialog>
<script>
    (function () {
        var d = document.getElementById('lightbox'); if (!d || !d.showModal) return;
        var img = d.querySelector('img'), cap = d.querySelector('.lb-cap'), count = d.querySelector('.lb-count');
        var items = Array.prototype.slice.call(document.querySelectorAll('[data-lightbox]')), i = 0;
        var big = function (el) {
            var im = el.querySelector('img'); if (!im) return '';
            var best = im.currentSrc || im.src, w = 0;
            (im.getAttribute('srcset') || '').split(',').forEach(function (c) { var p = c.trim().split(/\s+/), n = parseInt(p[1], 10) || 0; if (p[0] && n > w) { w = n; best = p[0]; } });
            return best;
        };
        var show = function (n) {
            i = (n + items.length) % items.length;
            var el = items[i], im = el.querySelector('img'), fc = el.closest('figure') && el.closest('figure').querySelector('figcaption');
            img.src = big(el); img.alt = im ? im.alt : '';
            cap.textContent = fc ? fc.textContent : (im ? im.alt : '');
            count.textContent = (i + 1) + ' / ' + items.length;
            var many = items.length > 1; d.querySelector('.lb-prev').hidden = !many; d.querySelector('.lb-next').hidden = !many;
        };
        items.forEach(function (el, n) {
            el.setAttribute('role', 'button'); el.setAttribute('tabindex', '0'); el.setAttribute('aria-label', 'View photo ' + (n + 1) + ' larger');
            var open = function () { show(n); d.showModal(); };
            el.addEventListener('click', open);
            el.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); } });
        });
        d.querySelector('.lb-prev').addEventListener('click', function () { show(i - 1); });
        d.querySelector('.lb-next').addEventListener('click', function () { show(i + 1); });
        d.querySelector('.lb-close').addEventListener('click', function () { d.close(); });
        d.addEventListener('click', function (e) { if (e.target === d || e.target.classList.contains('lb-stage')) d.close(); });
        d.addEventListener('keydown', function (e) { if (e.key === 'ArrowLeft') show(i - 1); if (e.key === 'ArrowRight') show(i + 1); });
        var x0 = null;
        d.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
        d.addEventListener('touchend', function (e) { if (x0 === null) return; var dx = e.changedTouches[0].clientX - x0; if (Math.abs(dx) > 50) show(dx < 0 ? i + 1 : i - 1); x0 = null; });
    })();
</script>
