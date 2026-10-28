@props(['title'])
@php
    $user = auth()->user();
    $items = [
        ['/admin', 'Overview', false],
        ['/admin/inquiries', 'Inquiries', false],
        ['/admin/packages', 'Packages', false],
        ['/admin/products', 'Products', false],
        ['/admin/categories', 'Categories', false],
        ['/admin/projects', 'Projects', false],
        ['/admin/text', 'Page text', false],
        ['/admin/content', 'Site content', false],
        ['/admin/settings', 'Contact & settings', false],
        ['/admin/staff', 'Staff', true],
    ];
    $active = fn ($href) => $href === '/admin' ? request()->is('admin') : request()->is(ltrim($href, '/'), ltrim($href, '/').'/*');
    $newInquiries = \App\Models\Inquiry::query()->where('status', 'new')->count();
@endphp
<!DOCTYPE html>
<html lang="en-PH">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} | JFL Solar admin</title>
    <link rel="icon" href="/icon.png" sizes="192x192" type="image/png">
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
</head>
<body class="min-h-dvh bg-steel-50 antialiased">
    <div class="lg:pl-64">
        <a href="#admin-main" class="sr-only z-50 rounded bg-gold-500 px-4 py-3 font-semibold text-steel-950 focus:not-sr-only focus:fixed focus:top-2 focus:left-2">Skip to content</a>
        <header class="on-dark bg-steel-950 text-white lg:fixed lg:inset-y-0 lg:left-0 lg:flex lg:w-64 lg:flex-col">
            <div class="flex items-center justify-between gap-3 px-4 py-3 lg:block lg:px-5 lg:py-6">
                <a href="/admin" class="flex items-center gap-3">
                    <img src="/brand/jfl-symbol-96.png" alt="" width="40" height="40" class="size-10 shrink-0">
                    <span class="leading-tight">
                        <span class="block font-display font-bold">JFL Solar</span>
                        <span class="block text-xs text-steel-300">Website admin</span>
                    </span>
                </a>
                <a href="/" target="_blank" rel="noopener" class="text-sm text-steel-300 underline hover:text-white lg:mt-4 lg:inline-block">View website<span class="sr-only"> (opens in a new tab)</span></a>
            </div>
            <div class="lg:flex-1 lg:overflow-y-auto lg:px-3">
                <nav aria-label="Admin">
                    <ul class="flex flex-wrap gap-1 px-3 pb-3 lg:flex-col lg:flex-nowrap lg:px-0 lg:pb-0">
                        @foreach ($items as [$href, $label, $ownerOnly])
                            @continue($ownerOnly && ! $user->isOwner())
                            <li>
                                <a href="{{ $href }}" @if ($active($href)) aria-current="page" @endif
                                    class="flex min-h-11 items-center gap-2 rounded-[var(--radius-control)] px-3 text-[0.95rem] font-semibold whitespace-nowrap text-steel-200 hover:bg-white/10 hover:text-white aria-[current=page]:bg-gold-500 aria-[current=page]:text-steel-950">
                                    {{ $label }}
                                    @if ($href === '/admin/inquiries' && $newInquiries)
                                        <span class="rounded-full bg-white px-2 text-xs font-bold text-steel-950 tabular">{{ $newInquiries }}<span class="sr-only"> new</span></span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </div>
            <div class="hidden border-t border-white/10 px-5 py-4 text-sm lg:block">
                <p class="truncate text-steel-300" title="{{ $user->email }}">{{ $user->name }}</p>
                <p class="text-xs text-steel-400 capitalize">{{ $user->role }}</p>
                <p class="mt-2"><a href="/admin/password" class="text-steel-200 underline hover:text-white">Change password</a></p>
                <form method="post" action="/admin/logout" class="mt-1">@csrf<button type="submit" class="inline-flex min-h-11 items-center font-semibold text-white underline">Sign out</button></form>
            </div>
        </header>
        <main id="admin-main" tabindex="-1" class="mx-auto max-w-5xl px-4 py-8 focus:outline-none sm:px-6 lg:py-10">
            @unless (\App\Support\Site::isProduction())
                <p class="mb-6 rounded-[var(--radius-control)] border border-dashed border-steel-400 bg-white px-4 py-2 text-sm text-steel-700">Preview site — items marked <strong>Sample</strong> are demonstration content and are hidden on the live site.</p>
            @endunless
            <x-admin.flash class="mb-6" />
            {{ $slot }}
            <div class="mt-12 border-t border-steel-200 pt-4 lg:hidden">
                <p class="text-sm text-steel-600">Signed in as {{ $user->email }} ({{ $user->role }})</p>
                <p class="mt-1"><a href="/admin/password" class="inline-flex min-h-11 items-center font-semibold text-steel-900 underline">Change password</a></p>
                <form method="post" action="/admin/logout">@csrf<button type="submit" class="inline-flex min-h-11 items-center font-semibold text-steel-900 underline">Sign out</button></form>
            </div>
        </main>
    </div>
</body>
</html>
