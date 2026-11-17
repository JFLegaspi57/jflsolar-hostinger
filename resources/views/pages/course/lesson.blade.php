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
            /* Screen-capture shield: the lesson is blurred while a screenshot key is held or the window is not in front. */
            .lesson-protected, .hero-media { transition: filter .08s; }
            html.lesson-shield .lesson-protected, html.lesson-shield .hero-media { filter: blur(24px); }
            .lesson-shield-note { display: none; position: fixed; inset: auto 1rem 1rem; z-index: 60; margin: 0 auto; max-width: 30rem; padding: .9rem 1.1rem; border-radius: .75rem; background: var(--color-steel-950); color: #fff; font-weight: 600; text-align: center; box-shadow: 0 10px 30px rgb(0 0 0 / .3); }
            html.lesson-shield .lesson-shield-note { display: block; }
            /* Check your understanding: each question gets an answer box; the model answer opens below it. */
            .quiz-q { margin: 1.25rem 0; padding: 1rem 1.2rem; border: 1px solid var(--color-steel-200); border-radius: .75rem; background: #fff; position: relative; z-index: 3; }
            .quiz-q .quiz-question { margin: 0 0 .6rem; font-weight: 700; color: var(--color-steel-950); }
            .quiz-q textarea { display: block; width: 100%; min-height: 6rem; padding: .6rem .75rem; border: 1px solid var(--color-steel-400); border-radius: .5rem; background: var(--color-steel-50); font: inherit; font-size: 1rem; line-height: 1.5; color: var(--color-steel-900); resize: vertical; -webkit-user-select: text; -moz-user-select: text; user-select: text; }
            .quiz-q textarea:focus { outline: 3px solid var(--color-gold-500); outline-offset: 1px; background: #fff; }
            .quiz-q .quiz-saved { margin-top: .3rem; font-size: .8rem; color: var(--color-steel-500); min-height: 1em; }
            .quiz-q .quiz-btn { margin-top: .5rem; display: inline-flex; min-height: 2.75rem; align-items: center; padding: 0 1.1rem; border: 0; border-radius: .5rem; background: var(--color-steel-900); color: #fff; font-weight: 700; cursor: pointer; }
            .quiz-q .quiz-btn:hover { background: var(--color-steel-800); }
            .quiz-q .quiz-btn:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 2px; }
            .quiz-q .quiz-btn[disabled] { opacity: .6; cursor: wait; }
            .quiz-q .quiz-result:empty { display: none; }
            .quiz-q .quiz-result { margin-top: .75rem; padding: .75rem 1rem; border-radius: .5rem; border: 1px solid var(--color-steel-200); background: var(--color-steel-50); }
            .quiz-q .quiz-result.is-correct { background: #ecfdf3; border-color: #86efac; }
            .quiz-q .quiz-result.is-partly { background: #fff8e1; border-color: #f5d77a; }
            .quiz-q .quiz-result.is-wrong { background: #fef2f2; border-color: #fca5a5; }
            .quiz-summary { margin-top: 2.5rem; padding: 1.25rem 1.4rem; border: 2px solid var(--color-steel-200); border-radius: .9rem; background: var(--color-steel-50); color: var(--color-steel-800); }
            .quiz-summary h2 { margin: 0 0 .5rem; font-size: 1.3rem; font-weight: 800; color: var(--color-steel-950); }
            .quiz-summary p { margin: .4rem 0; }
            .quiz-summary .quiz-verdict { font-size: 1.6rem; font-weight: 800; letter-spacing: .02em; }
            .quiz-summary .quiz-summary-small { font-size: .85rem; color: var(--color-steel-600); }
            .quiz-summary.is-passed { background: #ecfdf3; border-color: #4ade80; } .quiz-summary.is-passed .quiz-verdict { color: #166534; }
            .quiz-summary.is-failed { background: #fef2f2; border-color: #f87171; } .quiz-summary.is-failed .quiz-verdict { color: #991b1b; }
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
                $wmText = \App\Support\Members::current()?->email ?? (\App\Support\Forum::staff()?->email ?? 'jflsolar.ph');
                $wmSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="360" height="200"><text x="0" y="120" transform="rotate(-25 180 100)" font-family="Arial" font-size="15" fill="#0f172a">'.e(\App\Support\Course::WATERMARK.' · '.$wmText).'</text></svg>';
            @endphp
            <article class="lesson-body lesson-protected" oncontextmenu="return false" ondragstart="return false">
                <span class="lesson-wm" aria-hidden="true" style="background-image: url('data:image/svg+xml;base64,{{ base64_encode($wmSvg) }}')"></span>
                {!! $body !!}
                @if (! str_contains((string) $lesson->body, '[[slide:') && $slides->isNotEmpty())
                    @foreach ($slides as $s)
                        @include('pages.course.slide', ['slide' => $s, 'number' => $loop->iteration, 'watermark' => \App\Support\Course::WATERMARK])
                    @endforeach
                @endif
            </article>
            <script>
                (function () {
                    // Paid-member lessons: block copying, saving, printing and viewing the source from the keyboard and mouse.
                    // A saved copy of the page (Save Page As, offline copy) wipes the lesson when it is opened anywhere but the JFL Solar website.
                    var hostOk = /(^|\.)jflsolar\.(ph|com)$/.test(location.hostname) || ['localhost', '127.0.0.1'].indexOf(location.hostname) > -1;
                    if (!hostOk) {
                        document.querySelectorAll('.lesson-protected, .hero-media, .course-slide, .quiz-q').forEach(function (el) { el.remove(); });
                        document.title = 'JFL Solar';
                        document.body.innerHTML = '<p style="max-width:32rem;margin:4rem auto;padding:1rem;font:600 1.1rem/1.5 Arial,sans-serif;text-align:center">This lesson can only be viewed on the JFL Solar website: <a href="https://www.jflsolar.ph/courses/solar-pv-system-design">jflsolar.ph</a></p>';
                        return;
                    }
                    var stop = function (e) { e.preventDefault(); return false; };
                    ['copy', 'cut', 'contextmenu', 'dragstart', 'selectstart'].forEach(function (t) {
                        document.addEventListener(t, function (e) { if (!e.target.closest || !e.target.closest('input, textarea, #jfl-chat')) stop(e); }, true);
                    });
                    document.addEventListener('keydown', function (e) {
                        var k = (e.key || '').toLowerCase(), mod = e.ctrlKey || e.metaKey;
                        if (e.target.closest && e.target.closest('input, textarea')) return;
                        if ((mod && ['c', 'x', 'a', 's', 'p', 'u'].indexOf(k) > -1) || (mod && (e.shiftKey || e.altKey) && ['i', 'j', 'c', 'k', 's', 'u'].indexOf(k) > -1) || k === 'f12' || k === 'printscreen') {
                            stop(e);
                            if (k === 'printscreen') { wipe(); }
                        }
                    }, true);
                    window.addEventListener('beforeprint', function () { document.body.style.visibility = 'hidden'; });
                    window.addEventListener('afterprint', function () { document.body.style.visibility = ''; });

                    // Screen capture: blur the lesson while the Windows / Command key or Print Screen is pressed
                    // (Win+Shift+S, Win+PrtSc, Cmd+Shift+3/4/5), and while the window is not in front (Snipping Tool, other capture apps).
                    var root = document.documentElement, held = false, away = false;
                    var update = function () { root.classList.toggle('lesson-shield', held || away); };
                    var wipe = function () { if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText('').catch(function () {}); } };
                    document.addEventListener('keydown', function (e) {
                        if (e.key === 'Meta' || e.key === 'OS' || e.key === 'PrintScreen' || (e.metaKey && e.shiftKey)) { held = true; update(); }
                    }, true);
                    document.addEventListener('keyup', function (e) {
                        if (e.key === 'PrintScreen') { wipe(); }
                        if (e.key === 'Meta' || e.key === 'OS' || e.key === 'PrintScreen' || !e.metaKey) { setTimeout(function () { held = false; update(); }, e.key === 'PrintScreen' ? 1500 : 300); }
                    }, true);
                    window.addEventListener('blur', function () { away = true; update(); });
                    window.addEventListener('focus', function () { away = false; held = false; update(); });
                    document.addEventListener('visibilitychange', function () { away = document.visibilityState !== 'visible'; update(); });
                    document.addEventListener('pointerdown', function () { if (document.hasFocus()) { away = false; update(); } }, true);
                })();
            </script>
            <script>
                (function () {
                    // "Check your understanding": the student writes an answer and the website checks it (right / partly / not quite) without showing the model answer.
                    // The text is also kept in this browser so it is still there next time.
                    var tl = @json((bool) $tl);
                    var t = tl ? { box: 'Ang sagot mo', ph: 'Isulat dito ang sagot mo…', btn: 'Suriin ang sagot ko', busy: 'Sinusuri…', ok: '✅ Tama!', part: '🟡 Halos tama', no: '❌ Hindi pa tama', saved: 'Naka-save sa browser na ito', err: 'Hindi masuri ang sagot ngayon. Subukan ulit mamaya.' }
                               : { box: 'Your answer', ph: 'Write your answer here…', btn: 'Check my answer', busy: 'Checking…', ok: '✅ Correct!', part: '🟡 Partly correct', no: '❌ Not quite', saved: 'Saved in this browser', err: 'Your answer can\'t be checked right now. Please try again later.' };
                    var slug = @json($lesson->slug), csrf = @json(csrf_token());
                    var store = { get: function (k) { try { return localStorage.getItem(k) || ''; } catch (e) { return ''; } }, set: function (k, v) { try { localStorage.setItem(k, v); return true; } catch (e) { return false; } } };
                    document.querySelectorAll('.lesson-body .quiz-q[data-q]').forEach(function (box) {
                        var i = box.getAttribute('data-q'), key = 'jfl-course-answer:' + slug + ':' + i;
                        var q = box.querySelector('.quiz-question'); q.id = 'quiz-q-' + i;
                        var ta = document.createElement('textarea'); ta.placeholder = t.ph; ta.setAttribute('aria-label', t.box); ta.setAttribute('aria-describedby', q.id); ta.value = store.get(key);
                        var note = document.createElement('p'); note.className = 'quiz-saved';
                        var btn = document.createElement('button'); btn.type = 'button'; btn.className = 'quiz-btn'; btn.textContent = t.btn;
                        var out = document.createElement('div'); out.className = 'quiz-result'; out.setAttribute('aria-live', 'polite');
                        var timer; ta.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(function () { if (store.set(key, ta.value)) note.textContent = ta.value ? t.saved : ''; }, 400); });
                        btn.addEventListener('click', function () {
                            if (!ta.value.trim()) { ta.focus(); return; }
                            btn.disabled = true; btn.textContent = t.busy; out.className = 'quiz-result'; out.textContent = '';
                            fetch('/courses/check-answer', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                                body: JSON.stringify({ lesson: slug, q: Number(i), tl: tl, answer: ta.value }) })
                                .then(function (r) { return r.json().catch(function () { return {}; }); })
                                .then(function (d) {
                                    if (!d || !d.verdict) { out.className = 'quiz-result is-err'; out.textContent = (d && d.error) || t.err; return; }
                                    out.className = 'quiz-result is-' + d.verdict;
                                    var head = document.createElement('strong'); head.textContent = d.verdict === 'correct' ? t.ok : (d.verdict === 'partly' ? t.part : t.no);
                                    var p = document.createElement('span'); p.textContent = ' ' + (d.feedback || '');
                                    out.appendChild(head); out.appendChild(p);
                                    var sum = document.getElementById('quiz-summary');
                                    if (sum && d.summary_html) { sum.outerHTML = d.summary_html; }
                                })
                                .catch(function () { out.className = 'quiz-result is-err'; out.textContent = t.err; })
                                .finally(function () { btn.disabled = false; btn.textContent = t.btn; });
                        });
                        box.appendChild(ta); box.appendChild(note); box.appendChild(btn); box.appendChild(out);
                    });
                })();
            </script>
            @if ($result && $result['total'] > 0)
                @include('pages.course.quiz-summary', ['r' => $result, 'tl' => $tl])
            @endif
            <p class="lesson-print-note" style="display: none">This lesson cannot be printed.</p>
            <p class="lesson-shield-note" aria-live="polite">{{ $tl ? 'Nakatago ang aralin habang hindi aktibo ang window o habang may screenshot. Pindutin ang pahina para magpatuloy.' : 'The lesson is hidden while this window is not active or a screenshot is being taken. Click the page to continue.' }}</p>
            <nav aria-label="Lessons" class="mt-12 flex flex-wrap justify-between gap-3 border-t border-steel-200 pt-6">
                @if ($prev)<a href="{{ $link($prev) }}" class="{{ Ui::button('secondary') }}">← {{ $name($prev) }}</a>@else<span></span>@endif
                @if ($next)<a href="{{ $link($next) }}" class="{{ Ui::button('primary') }}">{{ $tl ? 'Susunod' : 'Next' }}: {{ $name($next) }} →</a>@else<a href="{{ Course::PATH }}" class="{{ Ui::button('primary') }}">{{ $tl ? 'Bumalik sa kurso' : 'Back to the course' }}</a>@endif
            </nav>
            <p class="mt-6 text-xs text-steel-500">{{ $tl ? 'Ang kursong ito ay para sa sarili mong pag-aaral bilang member ng JFL Solar. Huwag kopyahin, i-share o i-record ang mga aralin at slide.' : 'This course is for your own learning as a JFL Solar member. Please don\'t copy, share or record the lessons or slides.' }}</p>
        </div>
    </x-container>
</x-site-layout>
