@php
    $gcash = \App\Support\Course::gcash();
    $tl = $tl ?? false;
    $donor = \App\Support\Members::current();
    $donated = \App\Support\Course::hasDonated($donor);
@endphp
{{-- GCash donation box: the course is free; donations help keep it going. Once a member reports
     a donation (with the GCash reference number), the box no longer shows for them. --}}
@if ($gcash && $donated && ! ($inLesson ?? false))
    <p class="rounded-[var(--radius-panel)] border border-[#b9d3f5] bg-[#eef5ff] p-4 font-semibold text-steel-900 {{ $class ?? '' }}">{{ $tl ? 'Salamat sa iyong donasyon! Nakakatulong ito para makagawa pa kami ng mga bagong aralin.' : 'Thank you for your donation! It helps us keep making new lessons.' }}</p>
@elseif ($gcash && ! $donated)
    @once
    <style>
        /* Floating donation box: it leaves its place and floats at the bottom of the screen while the page is scrolled down,
           and goes back into its place when the page is scrolled up to it. */
        .donate-float { position: fixed; z-index: 44; left: 1rem; bottom: 1rem; width: min(46rem, calc(100vw - 13rem)); max-height: calc(100vh - 6rem); overflow: auto; gap: 1rem !important; padding: .9rem 1.1rem !important; box-shadow: 0 14px 40px rgb(15 23 42 / .28); animation: donate-in .2s ease-out; }
        @keyframes donate-in { from { opacity: 0; transform: translateY(1rem); } to { opacity: 1; transform: none; } }
        .donate-float .donate-qr { width: 112px !important; }
        .donate-float .donate-desc, .donate-float .donate-help { display: none; }
        .donate-float #donate-h { font-size: 1.05rem; padding-right: 2.5rem; }
        .donate-float form { margin-top: .6rem !important; }
        .donate-min > :not(.donate-body):not(.donate-toggle), .donate-min .donate-body > :not(#donate-h) { display: none !important; }
        .donate-min #donate-h { font-size: .95rem; padding-right: 0 !important; }
        .donate-toggle { display: none; position: absolute; top: .45rem; right: .45rem; width: 2.25rem; height: 2.25rem; align-items: center; justify-content: center; border: 1px solid #b9d3f5; border-radius: 999px; background: #fff; color: var(--color-steel-900); font-size: 1.1rem; font-weight: 700; line-height: 1; cursor: pointer; }
        .donate-float .donate-toggle { display: inline-flex; }
        .donate-min .donate-toggle { position: static; display: inline-flex !important; order: 2; margin-left: .6rem; flex: none; }
        .donate-min { display: flex !important; flex-wrap: nowrap !important; align-items: center; width: auto !important; }
        .donate-min .donate-body { flex: 0 1 auto !important; min-width: 0 !important; }
        @media (max-width: 639.98px) {
            .donate-float { left: .5rem; right: .5rem; bottom: .5rem; width: auto; max-width: none !important; }
            .donate-float .donate-qr { display: none; }
            html.donate-floating #jfl-chat-btn { bottom: calc(var(--donate-float-h, 0px) + 1rem); }
            html.donate-floating #jfl-chat-btn + .jc-say { display: none; }
        }
        .donate-thanks { position: fixed; z-index: 46; left: 50%; bottom: 1.25rem; transform: translateX(-50%); max-width: calc(100vw - 2rem); padding: .8rem 1.1rem; border-radius: .75rem; background: var(--color-steel-950); color: #fff; font-weight: 600; box-shadow: 0 10px 30px rgb(0 0 0 / .3); }
        @media print { [data-donate-slot], .donate-thanks { display: none !important; } }
    </style>
    @endonce
    <div data-donate-slot class="{{ $class ?? '' }}">
    <section aria-labelledby="donate-h" data-donate-box class="relative flex flex-wrap items-center gap-5 rounded-[var(--radius-panel)] border border-[#b9d3f5] bg-[#eef5ff] p-5 sm:p-6">
        <button type="button" class="donate-toggle" data-donate-toggle aria-expanded="true" aria-label="{{ $tl ? 'Paliitin' : 'Minimize' }}" title="{{ $tl ? 'Paliitin' : 'Minimize' }}">–</button>
        @if ($gcash['qr'])
            <img src="{{ $gcash['qr'] }}" alt="GCash QR code for donations to JFL Solar" width="200" height="200" class="donate-qr shrink-0 rounded-[var(--radius-control)] border border-[#b9d3f5] bg-white p-1" style="width: 200px; height: auto">
        @endif
        <div class="donate-body min-w-0 flex-1" style="min-width: 15rem">
            <h2 id="donate-h" class="text-xl font-bold text-steel-950">{{ $tl ? 'Libre ang kursong ito. Suportahan kami sa GCash' : 'This course is free. Support it with GCash' }}</h2>
            <p class="donate-desc mt-2 text-steel-800">{{ $tl ? 'Kung nakakatulong sa iyo ang mga aralin, maaari kang magbigay ng kahit anong halaga sa GCash. I-scan ang QR code gamit ang GCash app.' : 'If the lessons help you, you can donate any amount by GCash. Scan the QR code with your GCash app.' }}</p>
            @if ($gcash['number'])
                <p class="mt-2 font-semibold text-steel-950">GCash: <span class="tabular">{{ $gcash['number'] }}</span>@if ($gcash['name']) <span class="font-normal text-steel-700">· {{ $gcash['name'] }}</span>@endif</p>
            @endif
            @if ($donor)
                <form method="post" action="/courses/donated" data-donate-form class="mt-4 flex flex-wrap items-end gap-3">
                    @csrf
                    <label class="grid gap-1 text-sm font-semibold text-steel-900">{{ $tl ? 'GCash reference no.' : 'GCash reference no.' }}
                        <input name="reference" required inputmode="numeric" autocomplete="off" placeholder="e.g. 1234 567 890123" class="rounded-[var(--radius-control)] border border-steel-300 bg-white px-3 py-2 font-normal" style="width: 13rem">
                    </label>
                    <label class="grid gap-1 text-sm font-semibold text-steel-900">{{ $tl ? 'Halaga (₱, opsyonal)' : 'Amount (₱, optional)' }}
                        <input name="amount" inputmode="decimal" autocomplete="off" placeholder="2,000" class="rounded-[var(--radius-control)] border border-steel-300 bg-white px-3 py-2 font-normal" style="width: 7rem">
                    </label>
                    <button type="submit" class="{{ \App\Support\Ui::button('primary') }}">{{ $tl ? 'Naipadala ko na' : "I've sent my donation" }}</button>
                </form>
                <p class="donate-help mt-2 text-sm text-steel-600">{{ $tl ? 'Pagkatapos magpadala, ilagay ang reference number mula sa GCash receipt. Mawawala na ang box na ito sa iyong mga aralin. Salamat!' : 'After sending, enter the reference number from your GCash receipt and this box will no longer show in your lessons. Thank you!' }}</p>
            @endif
        </div>
    </section>
    </div>
    @once
    <script>
        (function () {
            var slot = document.querySelector('[data-donate-slot]'), box = slot && slot.querySelector('[data-donate-box]');
            if (!box) return;
            var root = document.documentElement, toggle = box.querySelector('[data-donate-toggle]'), floating = false;
            var ss = { get: function (k) { try { return sessionStorage.getItem(k); } catch (e) { return null; } }, set: function (k, v) { try { sessionStorage.setItem(k, v); } catch (e) {} } };
            var setMin = function (min) {
                box.classList.toggle('donate-min', min && floating);
                toggle.textContent = min ? '+' : '–';
                toggle.setAttribute('aria-expanded', min ? 'false' : 'true');
                measure();
            };
            var measure = function () { root.style.setProperty('--donate-float-h', floating ? box.offsetHeight + 'px' : '0px'); };
            var setFloat = function (on) {
                if (on === floating) return;
                if (on) { slot.style.minHeight = box.offsetHeight + 'px'; }
                floating = on;
                box.classList.toggle('donate-float', on);
                root.classList.toggle('donate-floating', on);
                if (!on) { slot.style.minHeight = ''; box.classList.remove('donate-min'); }
                setMin(on && ss.get('jfl-donate-min') === '1');
            };
            // Float once the box's own place has scrolled up out of view; go back into place when it comes back.
            var check = function () { setFloat(slot.getBoundingClientRect().bottom < 0); };
            window.addEventListener('scroll', check, { passive: true });
            window.addEventListener('resize', function () { check(); measure(); });
            check();
            toggle.addEventListener('click', function () { var min = !box.classList.contains('donate-min'); ss.set('jfl-donate-min', min ? '1' : '0'); setMin(min); });

            // Sending the donation: the box disappears straight away (the server also stops showing it).
            var form = box.querySelector('[data-donate-form]');
            if (form && window.fetch) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var btn = form.querySelector('button[type=submit]'); btn.disabled = true;
                    fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
                        .then(function (res) {
                            if (!res.ok || res.d.error) { btn.disabled = false; alertBox(res.d.error || res.d.message || 'Please check the reference number.'); return; }
                            setFloat(false); slot.remove(); root.classList.remove('donate-floating');
                            var t = document.createElement('p'); t.className = 'donate-thanks'; t.setAttribute('role', 'status'); t.textContent = res.d.message; document.body.appendChild(t);
                            setTimeout(function () { t.remove(); }, 6000);
                        })
                        .catch(function () { form.submit(); });
                });
            }
            function alertBox(msg) {
                var p = form.querySelector('.donate-error');
                if (!p) { p = document.createElement('p'); p.className = 'donate-error'; p.setAttribute('role', 'alert'); p.style.cssText = 'flex-basis:100%;margin:0;color:#991b1b;font-weight:600;font-size:.9rem'; form.appendChild(p); }
                p.textContent = msg;
            }
        })();
    </script>
    @endonce
@endif
