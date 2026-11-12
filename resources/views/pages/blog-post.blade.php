@php
    use App\Support\Blog;
    use App\Support\Site;
    use App\Support\Ui;
    $description = $post->excerpt ?: \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $post->body), 155);
    // The cover (thumbnail) is the banner at the top; all photos, cover first, are in the gallery and the pop-up viewer.
    $more = collect([$post->cover])->filter()->concat($post->photos->reject(fn ($m) => $post->cover && $m->id === $post->cover->id))->values();
    $viewer = $more->map(fn ($m) => ['src' => $m->url($m->bestWidth(1280)), 'alt' => $m->alt, 'caption' => (string) $m->caption])->all();
    $offset = 0;
    // Visitors who are not signed in: the first two paragraphs only, no photo gallery.
    $fullAccess = $fullAccess ?? true;
    $preview = null;
    if (! $fullAccess) {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $post->body)), fn ($l) => $l !== ''));
        $kept = []; $paras = 0;
        foreach ($lines as $l) {
            $kept[] = $l;
            if (! str_starts_with($l, '## ') && ++$paras >= 2) {
                break;
            }
        }
        $preview = implode("\n", $kept);
        $more = collect();
        $viewer = [];
    }
    $nextPath = rawurlencode('/blog/'.$post->slug);
    $heroFocus = $post->cover ? Blog::focus($post->cover) : null;
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
            /* Post + comments side by side on wide screens; comments below the post on phones. */
            .bp-layout { display: grid; gap: 2.5rem; }
            @media (min-width: 1024px) {
                .bp-layout { grid-template-columns: minmax(0, 1fr) 22rem; align-items: start; }
                .bp-aside { position: sticky; top: 6rem; }
                .bp-comment-list { max-height: 26rem; overflow-y: auto; padding-right: .25rem; }
            }
            .bp-panel { border: 1px solid var(--color-steel-200); border-radius: var(--radius-panel); background: #fff; padding: 1.25rem; }
            .bp-reactions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .75rem; }
            .bp-react { display: inline-flex; align-items: center; gap: .3rem; min-height: 2.75rem; padding: 0 .85rem; border: 1px solid var(--color-steel-300); border-radius: 999px; background: #fff; font-weight: 600; color: var(--color-steel-900); cursor: pointer; }
            .bp-react:hover { border-color: var(--color-steel-900); }
            .bp-react[aria-pressed="true"] { border-color: var(--color-gold-500); background: var(--color-gold-50, #fff8e6); box-shadow: inset 0 0 0 1px var(--color-gold-500); }
            .bp-flash { margin-top: 1rem; border-radius: var(--radius-control); background: var(--color-ok-50); padding: .6rem .75rem; font-size: .9rem; font-weight: 600; color: var(--color-ok-700); }
            .bp-comment-list { margin-top: 1rem; border-top: 1px solid var(--color-steel-200); padding-top: 1rem; display: grid; gap: 1rem; }
            .bp-comment { display: flex; gap: .65rem; }
            .bp-avatar { flex: none; display: grid; place-items: center; width: 2.25rem; height: 2.25rem; overflow: hidden; border-radius: 999px; background: var(--color-steel-900); color: var(--color-gold-400); font-weight: 800; }
            .bp-avatar img { width: 100%; height: 100%; object-fit: cover; }
            .bp-body { margin-top: .15rem; white-space: pre-line; overflow-wrap: anywhere; color: var(--color-steel-800); }
            .bp-waiting { margin-top: .2rem; display: inline-block; border-radius: 999px; background: var(--color-warn-50); padding: 0 .5rem; font-size: .75rem; font-weight: 600; color: var(--color-warn-800); }
            .bp-form { margin-top: 1.25rem; border-top: 1px solid var(--color-steel-200); padding-top: 1rem; }
            .bp-form textarea { margin-top: .4rem; width: 100%; min-height: 6rem; border: 1px solid var(--color-steel-500); border-radius: var(--radius-control); padding: .6rem .75rem; font-size: 1rem; color: var(--color-steel-950); }
            .bp-form textarea[aria-invalid="true"] { border-color: var(--color-danger-700); }
            .bp-trap { position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden; }
            .bp-signin { margin-top: 1.25rem; border-top: 1px solid var(--color-steel-200); padding-top: 1rem; font-size: .95rem; color: var(--color-steel-700); }
            /* Gallery: every photo shown whole (no cropping), in columns. */
            .bp-gallery { columns: 2; column-gap: 1rem; }
            @media (min-width: 640px) { .bp-gallery { columns: 3; } }
            .bp-gallery li { break-inside: avoid; margin-bottom: 1rem; }
            .bp-preview { position: relative; }
            .bp-preview::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 6rem; background: linear-gradient(to bottom, rgb(255 255 255 / 0), #fff); pointer-events: none; }
            .bp-gate { margin-top: 1.5rem; border: 1px solid var(--color-steel-200); border-top: 4px solid var(--color-gold-500); border-radius: var(--radius-panel); background: var(--color-steel-50); padding: 1.5rem; }
            .bp-gate h2 { font-size: 1.35rem; font-weight: 800; color: var(--color-steel-950); }
            .bp-gate ul { margin-top: .75rem; }
            .bp-gate li { margin-left: 1.25rem; list-style: disc; color: var(--color-steel-800); }
            .bp-gate li + li { margin-top: .25rem; }
            .bp-gate-actions { margin-top: 1.25rem; display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1.25rem; }
            .bp-caption { margin-top: .5rem; max-width: 40rem; padding: 0 1rem; text-align: center; font-size: .95rem; color: rgb(255 255 255 / .85); }
        </style>
        <script type="application/ld+json">{!! json_encode(array_filter($jsonLd) + ['isAccessibleForFree' => false], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    </x-slot:head>
    @if ($post->cover)
        <x-page-hero eyebrow="Blog" :title="$post->title" :image="$post->cover->url($post->cover->bestWidth(1280))" :srcset="$post->cover->srcset()"
            :image-alt="$post->cover->alt" :focus="$heroFocus" :flip="true" protect tall title-class="text-3xl leading-[1.08] font-extrabold sm:text-4xl" />
    @else
        <x-page-header eyebrow="Blog" :title="$post->title" />
    @endif
    <x-container class="py-10 sm:py-14">
        <div class="bp-layout">
        <article class="min-w-0 max-w-3xl">
            {{-- No author byline on posts (owner's choice); only the date is shown. --}}
            <p class="text-sm font-semibold text-steel-600"><time datetime="{{ $post->published_at?->timezone('Asia/Manila')->toDateString() }}">{{ Blog::date($post) }}</time></p>
            @if ($post->is_sample && ! Site::isProduction())<div class="mt-3"><x-sample-badge label="Sample post" /></div>@endif
            @if ($fullAccess)
                <x-paragraphs :text="$post->body" class="mt-8 text-lg leading-relaxed text-steel-800" />
            @else
                <div class="bp-preview"><x-paragraphs :text="$preview" class="mt-8 text-lg leading-relaxed text-steel-800" /></div>
                <section class="bp-gate" aria-labelledby="bp-gate-title">
                    <h2 id="bp-gate-title">Read the full post: free for members</h2>
                    <p class="mt-2 text-steel-800">Create a free JFL Solar account to read every blog post in full, with all the photos.</p>
                    <ul>
                        <li>Full articles and photo galleries</li>
                        <li>React to and comment on posts</li>
                        <li>Join the discussions in our forum</li>
                    </ul>
                    <div class="bp-gate-actions">
                        <a href="/signup?next={{ $nextPath }}" class="{{ Ui::button('primary', 'lg') }}">Sign up free</a>
                        <span class="text-steel-700">Already a member? <a href="/login?next={{ $nextPath }}" class="font-semibold text-steel-900 underline">Sign in</a></span>
                    </div>
                </section>
            @endif
            @if ($more->isNotEmpty())
                {{-- All photos (the banner photo first) as a gallery; each opens in the pop-up viewer. --}}
                <ul class="bp-gallery mt-10" aria-label="Photos">
                    @foreach ($more as $i => $img)
                        <li>
                            <figure>
                                <button type="button" data-photo="{{ $i + $offset }}" class="bp-open bp-photo relative block w-full overflow-hidden rounded-[var(--radius-panel)] bg-steel-100">
                                    <x-media-image :asset="$img" sizes="(min-width: 640px) 250px, 50vw" draggable="false" style="display: block; width: 100%; height: auto" />
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
            </div>
        </article>
        <aside id="comments" class="bp-aside" style="scroll-margin-top: 6rem">
            @include('partials.blog-comments')
        </aside>
        </div>
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
