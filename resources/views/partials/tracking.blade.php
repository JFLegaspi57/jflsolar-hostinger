@php
    use App\Support\Ui;
    $ga = (string) config('site.ga4_id');
    $px = (string) config('site.meta_pixel_id');
    $lead = (bool) session('inquiry_sent');
@endphp
{{-- Google Analytics / Meta Pixel: live site only, never with Do Not Track or Global Privacy Control,
     and only after the visitor chooses "Allow" (the choice is kept in this browser). --}}
<div id="track-consent" hidden role="region" aria-label="Cookie choice"
    style="position: fixed; left: 1rem; right: 1rem; bottom: 1rem; z-index: 60; max-width: 30rem"
    class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-5 text-steel-800 shadow-2xl">
    <p class="font-bold text-steel-950">Help us improve this website?</p>
    <p class="mt-1 text-sm">With your OK, we use {{ $ga && $px ? 'Google Analytics and the Meta (Facebook) Pixel' : ($ga ? 'Google Analytics' : 'the Meta (Facebook) Pixel') }} to see which pages{{ $px ? ' and ads' : '' }} help visitors. <a href="/privacy#tracking" class="font-semibold underline">Details</a></p>
    <div class="mt-4 flex gap-2">
        <button type="button" data-track-yes class="{{ Ui::button('primary', 'md', 'flex-1') }}">Allow</button>
        <button type="button" data-track-no class="{{ Ui::button('secondary', 'md', 'flex-1') }}">No, thanks</button>
    </div>
</div>
<script>
    (function () {
        var GA = @json($ga), PX = @json($px), LEAD = @json($lead), KEY = 'jfl-track';
        var box = document.getElementById('track-consent');
        function get() { try { return localStorage.getItem(KEY); } catch (e) { return null; } }
        function set(v) { try { localStorage.setItem(KEY, v); } catch (e) {} }
        function optedOut() { return navigator.globalPrivacyControl === true || navigator.doNotTrack === '1' || window.doNotTrack === '1'; }
        var loaded = false;
        function load() {
            if (loaded) return;
            loaded = true;
            if (GA) {
                var s = document.createElement('script');
                s.async = true;
                s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(GA);
                document.head.appendChild(s);
                window.dataLayer = window.dataLayer || [];
                window.gtag = function () { window.dataLayer.push(arguments); };
                window.gtag('js', new Date());
                window.gtag('config', GA);
                if (LEAD) window.gtag('event', 'generate_lead');
            }
            if (PX) {
                !function (f, b, e, v, n, t, s) { if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); }; if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = []; t = b.createElement(e); t.async = !0; t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s); }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
                window.fbq('init', PX);
                window.fbq('track', 'PageView');
                if (LEAD) window.fbq('track', 'Lead');
            }
        }
        function show() { box.hidden = false; }
        function hide() { box.hidden = true; }
        box.querySelector('[data-track-yes]').addEventListener('click', function () { set('yes'); hide(); load(); });
        box.querySelector('[data-track-no]').addEventListener('click', function () { set('no'); hide(); });
        // Privacy page: "Change your choice".
        window.jflTrackChoice = function () { if (optedOut()) return false; try { localStorage.removeItem(KEY); } catch (e) {} show(); return true; };
        if (optedOut()) return;
        var choice = get();
        if (choice === 'yes') load(); else if (choice !== 'no') show();
    })();
</script>
