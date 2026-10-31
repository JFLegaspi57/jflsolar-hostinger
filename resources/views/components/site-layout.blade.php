@props(['title' => null, 'description' => \App\Support\Site::DESCRIPTION, 'path' => '/', 'noindex' => false, 'reviewsAboveFooter' => true])
@php
    use App\Support\Site;
    $fullTitle = $path === '/' || ! $title ? Site::NAME.' — '.Site::TAGLINE : $title.' | '.Site::NAME;
    $canonical = Site::url($path === '/' ? '/' : $path);
    $share = Site::url('/og-image.png');
    $robots = ($noindex || ! Site::allowsIndexing()) ? 'noindex, nofollow' : 'index, follow';
@endphp
<!DOCTYPE html>
<html lang="en-PH">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#333f4f">
    <meta name="format-detection" content="telephone=no">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="robots" content="{{ $robots }}">
    <link rel="canonical" href="{{ $canonical }}">
    @if (config('site.google_verification') !== '')<meta name="google-site-verification" content="{{ config('site.google_verification') }}">@endif
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:site_name" content="{{ Site::NAME }}">
    <meta property="og:locale" content="en_PH">
    <meta property="og:image" content="{{ $share }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ Site::NAME }} — {{ Site::TAGLINE }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $fullTitle }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $share }}">
    <link rel="icon" href="/icon.png" sizes="192x192" type="image/png">
    <link rel="icon" href="/favicon-32.png" sizes="32x32" type="image/png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png" sizes="180x180">
    @vite(['resources/css/app.css', 'resources/js/site.js'])
    <script type="application/ld+json">{!! \App\Support\Seo::organizationJsonLd(\App\Support\PublicContent::settings()) !!}</script>
    {{ $head ?? '' }}
    @isset($pageviewId)
        {{-- Visitors report: checks in when the page shows (only real browsers are counted), then reports time on screen when the visitor leaves or switches away. --}}
        <script>
            (function () {
                var id = '{{ $pageviewId }}', total = 0, since = document.visibilityState === 'visible' ? Date.now() : 0, sent = 0;
                function pause() { if (since) { total += Date.now() - since; since = 0; } }
                function send() {
                    pause();
                    var s = Math.round(total / 1000);
                    if (s < 1 || s === sent || !navigator.sendBeacon) return;
                    sent = s;
                    var d = new FormData(); d.append('id', id); d.append('s', s);
                    navigator.sendBeacon('/pv', d);
                }
                // Check in once the page has been on screen for a second: only real browsers showing the page are
                // counted (robots and uptime checkers either don't run this script or close the page instantly).
                var started = false, timer = 0;
                function checkIn() {
                    if (started || !navigator.sendBeacon) return;
                    started = true;
                    var h = new FormData(); h.append('id', id); h.append('start', '1'); navigator.sendBeacon('/pv', h);
                }
                function arm() { if (!started && !timer && document.visibilityState === 'visible') timer = setTimeout(checkIn, 1000); }
                function disarm() { clearTimeout(timer); timer = 0; }
                arm();
                document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'hidden') { disarm(); send(); } else { since = Date.now(); arm(); } });
                window.addEventListener('pagehide', send);
                window.addEventListener('pageshow', function (e) { if (e.persisted) since = Date.now(); });
            })();
        </script>
    @endisset
</head>
<body class="flex min-h-dvh flex-col antialiased">
    <a href="#main" class="sr-only z-50 rounded bg-gold-500 px-4 py-3 font-semibold text-steel-950 focus:not-sr-only focus:fixed focus:top-2 focus:left-2">Skip to main content</a>
    @include('partials.preview-ribbon')
    @include('partials.site-header')
    <main id="main" class="flex-1">
        {{ $slot }}
    </main>
    @if ($reviewsAboveFooter) @include('partials.reviews') @endif
    @include('partials.site-footer')
    @if (\App\Support\Chatbot::available()) @include('partials.chatbot') @endif
    @if (Site::tracking()) @include('partials.tracking') @endif
</body>
</html>
