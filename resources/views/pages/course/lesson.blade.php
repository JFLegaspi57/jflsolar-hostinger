@php
    use App\Support\Course;
    use App\Support\Ui;
    $tl = $tl ?? false;
    $title = $tl ? ($lesson->title_tl ?: $lesson->title) : $lesson->title;
    $summary = $tl ? ($lesson->summary_tl ?: $lesson->summary) : $lesson->summary;
    $base = Course::PATH.'/'.$lesson->slug;
    $link = fn ($l) => Course::PATH.'/'.$l->slug.($tl && ! empty($l->has_tl) ? '/tagalog' : '');
    $name = fn ($l) => $tl ? ($l->title_tl ?: $l->title) : $l->title;
@endphp
<x-site-layout :title="$title.' — '.Course::TITLE" :path="$base.($tl ? '/tagalog' : '')" :lang="$tl ? 'fil' : 'en-PH'" :noindex="true" :reviews-above-footer="false"
    description="A lesson of the free JFL Solar online course for members.">
    <x-slot:head>
        <style>
            .lesson-body { color: var(--color-steel-800); font-size: 1.0625rem; line-height: 1.7; }
            .lesson-body h2 { margin: 2.25rem 0 .75rem; font-size: 1.5rem; font-weight: 800; color: var(--color-steel-950); line-height: 1.25; }
            .lesson-body h3 { margin: 1.75rem 0 .5rem; font-size: 1.2rem; font-weight: 700; color: var(--color-steel-950); }
            .lesson-body p { margin: .9rem 0; }
            .lesson-body ul, .lesson-body ol { margin: .9rem 0; padding-left: 1.4rem; }
            .lesson-body ul { list-style: disc; } .lesson-body ol { list-style: decimal; }
            .lesson-body li { margin: .35rem 0; }
            .lesson-body strong { color: var(--color-steel-950); }
            .lesson-body table { width: 100%; margin: 1rem 0; border-collapse: collapse; font-size: .975rem; }
            .lesson-body th, .lesson-body td { border: 1px solid var(--color-steel-200); padding: .5rem .7rem; text-align: left; vertical-align: top; }
            .lesson-body th { background: var(--color-steel-50); color: var(--color-steel-950); }
            .lesson-body .calc { margin: 1rem 0; padding: 1rem 1.2rem; border-left: 4px solid var(--color-gold-500); border-radius: .5rem; background: var(--color-steel-50); font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .95rem; line-height: 1.8; overflow-x: auto; white-space: pre-wrap; }
            .lesson-body .key { margin: 1.25rem 0; padding: 1rem 1.2rem; border-radius: .75rem; background: #fff8e1; border: 1px solid #f5d77a; }
            .lesson-body sup { font-size: .7em; }
            .course-slide { margin: 1.5rem 0; }
            .course-slide-frame { position: relative; overflow: hidden; border-radius: .75rem; border: 1px solid var(--color-steel-200); background: var(--color-steel-100); user-select: none; -webkit-user-select: none; -webkit-touch-callout: none; }
            .course-slide img { display: block; width: 100%; height: auto; pointer-events: none; }
            .course-wm { position: absolute; right: .6rem; bottom: .45rem; padding: .1rem .45rem; border-radius: .3rem; background: rgb(255 255 255 / .55); color: rgb(15 23 42 / .55); font-size: .7rem; font-weight: 600; pointer-events: none; }
            .course-slide figcaption { margin-top: .4rem; font-size: .9rem; color: var(--color-steel-600); }
            @media print { .lesson-protected, .hero-media, img { display: none !important; } .lesson-print-note { display: block !important; } }
            /* Copy protection: no selecting, dragging or long-press saving of the lesson text and images. */
            .lesson-protected, .lesson-protected * { -webkit-user-select: none; -moz-user-select: none; user-select: none; -webkit-touch-callout: none; }
            .lesson-protected img, .lesson-protected svg { -webkit-user-drag: none; user-drag: none; pointer-events: none; }
            .lesson-protected { position: relative; }
            .lesson-wm { position: absolute; inset: 0; z-index: 2; pointer-events: none; background-repeat: repeat; opacity: .07; }
            /* Check your understanding: each question gets an answer box; the model answer opens below it. */
            .quiz-q { margin: 1.25rem 0; padding: 1rem 1.2rem; border: 1px solid var(--color-steel-200); border-radius: .75rem; background: #fff; position: relative; z-index: 3; }
            .quiz-q .quiz-question { margin: 0 0 .6rem; font-weight: 700; color: var(--color-steel-950); }
            .quiz-q textarea { display: block; width: 100%; min-height: 6rem; padding: .6rem .75rem; border: 1px solid var(--color-steel-400); border-radius: .5rem; background: var(--color-steel-50); font: inherit; font-size: 1rem; line-height: 1.5; color: var(--color-steel-900); resize: vertical; -webkit-user-select: text; -moz-user-select: text; user-select: text; }
            .quiz-q textarea:focus { outline: 3px solid var(--color-gold-500); outline-offset: 1px; background: #fff; }
            .quiz-q .quiz-saved { margin-top: .3rem; font-size: .8rem; color: var(--color-steel-500); min-height: 1em; }
            .quiz-q details { margin-top: .6rem; }
            .quiz-q details summary { display: inline-flex; min-height: 2.75rem; align-items: center; cursor: pointer; font-weight: 700; color: var(--color-steel-900); text-decoration: underline; text-underline-offset: .2em; }
            .quiz-q details[open] summary { margin-bottom: .25rem; }
        </style>
    </x-slot:head>
    @php
        $eyebrow = Course::TITLE.($position ? ($tl ? ' · Aralin '.$position.' ng '.$total : ' · Lesson '.$position.' of '.$total) : '');
        $banner = Course::banner('lesson-'.$lesson->id) ?? Course::banner('course');
    @endphp
    @if ($banner)
        <x-page-hero :eyebrow="$eyebrow" :title="$title" :intro="$summary ?: null" :image="$banner['src']" :srcset="$banner['srcset']" imageAlt="" :focus="$lesson->banner_focus ?? '50% 50%'" protect />
    @else
        <x-page-header :eyebrow="$eyebrow" :title="$title" :intro="$summary ?: null" />
    @endif
    <x-container class="py-10 sm:py-14">
        <div class="mx-auto max-w-3xl">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <a href="{{ Course::PATH }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-steel-700 underline hover:text-steel-950">← {{ $tl ? 'Lahat ng aralin' : 'All lessons' }}</a>
                @if ($tl)
                    <a href="{{ $base }}" hreflang="en" lang="en" class="inline-flex min-h-11 items-center text-sm font-semibold text-steel-700 underline hover:text-steel-950">Read this lesson in English</a>
                @elseif (Course::hasTagalog($lesson))
                    <a href="{{ $base }}/tagalog" hreflang="fil" lang="fil" class="inline-flex min-h-11 items-center text-sm font-semibold text-steel-700 underline hover:text-steel-950">Basahin ang araling ito sa Tagalog</a>
                @endif
            </div>
            @if (session('account_status') || session('status_error'))<div class="mb-4">@include('partials.account-flash')</div>@endif
            @include('pages.course.donate', ['tl' => $tl, 'class' => 'mb-8', 'inLesson' => true])
            @if ($staffPreview)
                <p class="mb-6 rounded-[var(--radius-control)] border border-[#f0d39a] bg-warn-50 p-3 text-sm font-semibold text-warn-800">Staff preview{{ $lesson->status !== 'published' ? ' (draft: not visible to members yet)' : '' }}{{ $tl ? ' · Tagalog version' : '' }}.</p>
            @endif
            @php
                $wmText = \App\Support\Members::current()?->email ?? (\App\Support\Forum::staff()?->email ?? 'JFL Solar');
                $wmSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="360" height="200"><text x="0" y="120" transform="rotate(-25 180 100)" font-family="Arial" font-size="15" fill="#0f172a">'.e($wmText).' · JFL Solar</text></svg>';
            @endphp
            <article class="lesson-body lesson-protected" oncontextmenu="return false" ondragstart="return false">
                <span class="lesson-wm" aria-hidden="true" style="background-image: url('data:image/svg+xml;base64,{{ base64_encode($wmSvg) }}')"></span>
                {!! $body !!}
                @if (! str_contains((string) $lesson->body, '[[slide:') && $slides->isNotEmpty())
                    @foreach ($slides as $s)
                        @include('pages.course.slide', ['slide' => $s, 'number' => $loop->iteration, 'watermark' => $slides->isNotEmpty() ? (\App\Support\Members::current()?->email ?? '') : ''])
                    @endforeach
                @endif
            </article>
            <script>
                (function () {
                    // Paid-member lessons: block copying, saving, printing and viewing the source from the keyboard and mouse.
                    var stop = function (e) { e.preventDefault(); return false; };
                    ['copy', 'cut', 'contextmenu', 'dragstart', 'selectstart'].forEach(function (t) {
                        document.addEventListener(t, function (e) { if (!e.target.closest || !e.target.closest('input, textarea, #jfl-chat')) stop(e); }, true);
                    });
                    document.addEventListener('keydown', function (e) {
                        var k = (e.key || '').toLowerCase(), mod = e.ctrlKey || e.metaKey;
                        if (e.target.closest && e.target.closest('input, textarea')) return;
                        if ((mod && ['c', 'x', 'a', 's', 'p', 'u'].indexOf(k) > -1) || (mod && e.shiftKey && ['i', 'j', 'c', 's'].indexOf(k) > -1) || k === 'f12' || k === 'printscreen') {
                            stop(e);
                            if (k === 'printscreen' && navigator.clipboard) { navigator.clipboard.writeText('').catch(function () {}); }
                        }
                    }, true);
                    window.addEventListener('beforeprint', function () { document.body.style.visibility = 'hidden'; });
                    window.addEventListener('afterprint', function () { document.body.style.visibility = ''; });
                })();
            </script>
            <script>
                (function () {
                    // Turn each "Check your understanding" question into: question, an answer box for the student, then "Show answer".
                    // What the student writes is kept in this browser only (not sent anywhere).
                    var showLabel = @json($tl ? 'Ipakita ang sagot' : 'Show answer');
                    var boxLabel = @json($tl ? 'Ang sagot mo' : 'Your answer');
                    var placeholder = @json($tl ? 'Isulat dito ang sagot mo bago tingnan ang sagot…' : 'Write your answer here before you look at the answer…');
                    var savedText = @json($tl ? 'Naka-save sa browser na ito' : 'Saved in this browser');
                    var keyBase = 'jfl-course-answer:' + @json($lesson->slug) + ':';
                    var store = { get: function (k) { try { return localStorage.getItem(k) || ''; } catch (e) { return ''; } }, set: function (k, v) { try { localStorage.setItem(k, v); return true; } catch (e) { return false; } } };
                    document.querySelectorAll('.lesson-body details').forEach(function (d, i) {
                        var summary = d.querySelector('summary');
                        if (!summary || d.closest('.quiz-q')) return;
                        var wrap = document.createElement('div'); wrap.className = 'quiz-q';
                        var q = document.createElement('p'); q.className = 'quiz-question'; q.id = 'quiz-q-' + i; q.innerHTML = summary.innerHTML;
                        var ta = document.createElement('textarea'); ta.id = 'quiz-a-' + i; ta.placeholder = placeholder; ta.setAttribute('aria-label', boxLabel); ta.setAttribute('aria-describedby', q.id);
                        var note = document.createElement('p'); note.className = 'quiz-saved'; note.setAttribute('aria-live', 'polite');
                        var key = keyBase + i; ta.value = store.get(key);
                        var t; ta.addEventListener('input', function () { clearTimeout(t); t = setTimeout(function () { if (store.set(key, ta.value)) { note.textContent = ta.value ? savedText : ''; } }, 400); });
                        d.parentNode.insertBefore(wrap, d);
                        summary.textContent = showLabel;
                        wrap.appendChild(q); wrap.appendChild(ta); wrap.appendChild(note); wrap.appendChild(d);
                    });
                })();
            </script>
            <p class="lesson-print-note" style="display: none">This lesson cannot be printed.</p>
            <nav aria-label="Lessons" class="mt-12 flex flex-wrap justify-between gap-3 border-t border-steel-200 pt-6">
                @if ($prev)<a href="{{ $link($prev) }}" class="{{ Ui::button('secondary') }}">← {{ $name($prev) }}</a>@else<span></span>@endif
                @if ($next)<a href="{{ $link($next) }}" class="{{ Ui::button('primary') }}">{{ $tl ? 'Susunod' : 'Next' }}: {{ $name($next) }} →</a>@else<a href="{{ Course::PATH }}" class="{{ Ui::button('primary') }}">{{ $tl ? 'Bumalik sa kurso' : 'Back to the course' }}</a>@endif
            </nav>
            <p class="mt-6 text-xs text-steel-500">{{ $tl ? 'Ang kursong ito ay para sa sarili mong pag-aaral bilang member ng JFL Solar. Huwag kopyahin, i-share o i-record ang mga aralin at slide.' : 'This course is for your own learning as a JFL Solar member. Please don\'t copy, share or record the lessons or slides.' }}</p>
        </div>
    </x-container>
</x-site-layout>
