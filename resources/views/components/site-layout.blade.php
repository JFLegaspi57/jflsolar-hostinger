@props(['title' => null, 'description' => \App\Support\Site::DESCRIPTION, 'path' => '/', 'noindex' => false])
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
</head>
<body class="flex min-h-dvh flex-col antialiased">
    <a href="#main" class="sr-only z-50 rounded bg-gold-500 px-4 py-3 font-semibold text-steel-950 focus:not-sr-only focus:fixed focus:top-2 focus:left-2">Skip to main content</a>
    @include('partials.preview-ribbon')
    @include('partials.site-header')
    <main id="main" class="flex-1">
        {{ $slot }}
    </main>
    @include('partials.site-footer')
</body>
</html>
