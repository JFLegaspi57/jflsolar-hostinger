@php
    use App\Support\Blog;
    use App\Support\Site;
    use App\Support\Ui;
    $description = $post->excerpt ?: \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $post->body), 155);
    $more = $post->photos->reject(fn ($m) => $post->cover && $m->id === $post->cover->id)->values();
    // Photo viewer (pop-up): the cover first, then the gallery photos.
    $viewer = collect([$post->cover])->filter()->concat($more)->values()
        ->map(fn ($m) => ['src' => $m->url($m->bestWidth(1280)), 'alt' => $m->alt, 'caption' => (string) $m->caption])->all();
    $offset = $post->cover ? 1 : 0;
    $jsonLd = [
        '@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $post->title, 'description' => $description,
        'datePublished' => $post->published_at?->toAtomString(), 'dateModified' => $post->updated_at?->toAtomString(),
        'author' => Blog::author($post) === Blog::AUTHOR ? ['@type' => 'Person', 'name' => Blog::AUTHOR] : ['@type' => 'Organization', 'name' => Site::NAME], 'publisher' => ['@type' => 'Organization', 'name' => Site::NAME],
        'mainEntityOfPage' => Site::url('/blog/'.$post->slug),
    ];
@endphp
<x-site-layout :title="$post->title" :path="'/blog/'.$post->slug" :description="$description" :noindex="$post->is_sample" :reviews-above-footer="false">
    <x-slot:head>
        <style>
            /* Photos: no right-click "Save image", dragging or long-press saving (a deterrent; screenshots cannot be blocked). */
            .bp-photo { -webkit-touch-callout: none; -webkit-user-select: none; user-select: none; }
            .bp-shield { position: absolute; inset: 0; }
            .bp-open { cursor: zoom-in; }
            .bp-viewer { max-width: none; max-height: none; width: 100%; height: 100%; margin: 0; padding: 0; border: 0; background: transparent; color: #fff; }
            .bp-viewer::backdrop { background: rgb(10 14 20 / .92); }
            .bp-viewer[open] { display: flex; flex-direction: column; align-items: center; justify-content: center; }
            .bp-stage { position: relative; display: flex; max-width: min(92vw, 60rem); }
            .bp-stage img { max-width: 100%; max-height: 80vh; width: auto; height: auto; border-radius: .5rem; }
            .bp-bar { display: flex; align-items: center; justify-content: center; gap: .75rem; margin-top: 1rem; padding: 0 1rem; text-align: center; }
            .bp-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 2.9rem; min-height: 2.9rem; padding: 0 .9rem; border: 1px solid rgb(255 255 255 / .45); border-radius: 999px; background: rgb(255 255 255 / .08); color: #fff; font-weight: 700; cursor: pointer; }
            .bp-btn:hover, .bp-btn:focus-visible { background: rgb(255 255 255 / .2); }
            .bp-close { position: fixed; top: 1rem; right: 1rem; }
            .bp-caption { margin-top: .5rem; max-width: 40rem; padding: 0 1rem; text-align: center; font-size: .95rem; color: rgb(255 255 255 / .85); }
        </style>
        <script type="application/ld+json">{!! json_encode(array_filter($jsonLd), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    </x-slot:head>
    <x-page-header eyebrow="Blog" :title="$post->title" />
    <x-container class="py-10 sm:py-14">
        <article class="max-w-3xl">
            <p class="text-sm font-semibold text-steel-600">By @if (Blog::author($post) === Blog::AUTHOR)<a href="/about" class="text-steel-900 underline">{{ Blog::AUTHOR }}</a>@else<span class="text-steel-900">{{ Blog::author($post) }}</span>@endif · <time datetime="{{ $post->published_at?->timezone('Asia/Manila')->toDateString() }}">{{ Blog::date($post) }}</time></p>
            @if ($post->is_sample && ! Site::isProduction())<div class="mt-3"><x-sample-badge label="Sample post" /></div>@endif
            @if ($post->cover)
                <figure class="mt-6">
                    {{-- Tall (portrait) photos are capped in height so they don't fill the whole screen. --}}
                    <button type="button" data-photo="0" class="bp-open bp-photo flex w-full justify-center overflow-hidden rounded-[var(--radius-panel)] bg-steel-100">
                        <span class="relative inline-flex">
                            <x-media-image :asset="$post->cover" sizes="(min-width: 768px) 768px, 100vw" priority fit="contain" draggable="false" style="width: auto; height: auto; max-width: 100%; max-height: 36rem" />
                            <span class="bp-shield" aria-hidden="true"></span>
                        </span>
                        <span class="sr-only">Enlarge photo</span>
                    </button>
                    @if ($post->cover->caption)<figcaption class="mt-2 text-sm text-steel-600">{{ $post->cover->caption }}</figcaption>@endif
                </figure>
            @endif
            <x-paragraphs :text="$post->body" class="mt-8 text-lg leading-relaxed text-steel-800" />
            @if ($more->isNotEmpty())
                {{-- Other photos as a gallery; each opens in the pop-up viewer. --}}
                <ul class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3" aria-label="Photos">
                    @foreach ($more as $i => $img)
                        <li>
                            <figure>
                                <button type="button" data-photo="{{ $i + $offset }}" class="bp-open bp-photo relative block w-full overflow-hidden rounded-[var(--radius-panel)] bg-steel-100" style="aspect-ratio: 3 / 4">
                                    <x-media-image :asset="$img" sizes="(min-width: 640px) 250px, 50vw" draggable="false" style="position: absolute; inset: 0; object-position: 50% 0%" />
                                    <span class="bp-shield" aria-hidden="true"></span>
                                    <span class="sr-only">Enlarge photo: {{ $img->alt }}</span>
                                </button>
                                @if ($img->caption)<figcaption class="mt-2 text-sm text-steel-600">{{ $img->caption }}</figcaption>@endif
                            </figure>
                        </li>
                    @endforeach
                </ul>
            @endif
            <div class="mt-12 flex flex-col gap-2 border-t border-steel-200 sm:flex-row" style="padding-top: 1.5rem">
                <a href="/blog" class="{{ Ui::button('secondary', 'lg') }}"><span aria-hidden="true">←</span> All posts</a>
                <a href="{{ Site::QUOTE_HREF }}" class="{{ Ui::button('primary', 'lg') }}">Request a Quote</a>
            </div>
        </article>
    </x-container>

    @if ($viewer)
        <dialog id="photo-viewer" class="bp-viewer" aria-label="Photo viewer">
            <button type="button" class="bp-btn bp-close" data-viewer="close" aria-label="Close photo viewer">✕</button>
            <div class="bp-stage bp-photo">
                <img id="photo-viewer-img" src="" alt="" draggable="false">
                <span class="bp-shield" aria-hidden="true"></span>
            </div>
            <p id="photo-viewer-caption" class="bp-caption" aria-live="polite"></p>
            @if (count($viewer) > 1)
                <div class="bp-bar">
                    <button type="button" class="bp-btn" data-viewer="prev" aria-label="Previous photo">←</button>
                    <span id="photo-viewer-count" class="text-sm"></span>
                    <button type="button" class="bp-btn" data-viewer="next" aria-label="Next photo">→</button>
                </div>
            @endif
        </dialog>
        <script>
            (function () {
                var photos = @json($viewer), at = 0;
                var dlg = document.getElementById('photo-viewer'), img = document.getElementById('photo-viewer-img');
                var cap = document.getElementById('photo-viewer-caption'), count = document.getElementById('photo-viewer-count');
                function show(i) {
                    at = (i + photos.length) % photos.length;
                    img.src = photos[at].src; img.alt = photos[at].alt;
                    cap.textContent = photos[at].caption || '';
                    if (count) count.textContent = (at + 1) + ' / ' + photos.length;
                }
                document.querySelectorAll('[data-photo]').forEach(function (b) {
                    b.addEventListener('click', function () { show(+b.dataset.photo); if (dlg.showModal) dlg.showModal(); });
                });
                dlg.addEventListener('click', function (e) {
                    var act = e.target.closest('[data-viewer]');
                    if (act) { act.dataset.viewer === 'close' ? dlg.close() : show(at + (act.dataset.viewer === 'next' ? 1 : -1)); }
                    else if (e.target === dlg) dlg.close(); // click on the dark background
                });
                dlg.addEventListener('keydown', function (e) {
                    if (e.key === 'ArrowRight') show(at + 1);
                    if (e.key === 'ArrowLeft') show(at - 1);
                });
                // Deterrent against saving: no context menu or dragging on the photos.
                document.querySelectorAll('.bp-photo').forEach(function (el) {
                    el.addEventListener('contextmenu', function (e) { e.preventDefault(); });
                    el.addEventListener('dragstart', function (e) { e.preventDefault(); });
                });
            })();
        </script>
    @endif
</x-site-layout>
