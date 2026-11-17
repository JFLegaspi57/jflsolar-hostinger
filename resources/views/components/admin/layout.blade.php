@props(['title'])
@php
    $user = auth()->user();
    $items = [
        ['/admin', 'Overview', false],
        ['/admin/visitors', 'Visitors', false],
        ['/admin/inquiries', 'Inquiries', false],
        ['/admin/chats', 'Buckny chats', false],
        ['/admin/orders', 'Orders', false],
        ['/admin/forum', 'Forum', false],
        ['/admin/members', 'Members', false],
        ['/admin/subscribers', 'Subscribers', false],
        ['/admin/packages', 'Packages', false],
        ['/admin/products', 'Products', false],
        ['/admin/inventory', 'Inventory sync', false],
        ['/admin/categories', 'Categories', false],
        ['/admin/projects', 'Projects', false],
        ['/admin/blog', 'Blog', false],
        ['/admin/course', 'Online course', false],
        ['/admin/text', 'Page text', false],
        ['/admin/content', 'Site content', false],
        ['/admin/settings', 'Contact & settings', false],
        ['/admin/facebook-reviews', 'Facebook reviews', true],
        ['/admin/staff', 'Staff', true],
    ];
    $active = fn ($href) => $href === '/admin' ? request()->is('admin') : request()->is(ltrim($href, '/'), ltrim($href, '/').'/*');
    $newInquiries = \App\Models\Inquiry::query()->where('status', 'new')->count();
    $forumPending = \App\Support\Forum::pendingCount();
    $blogWaiting = \App\Support\BlogComments::waitingCount();
    $membersOnline = \App\Support\Members::online()->count();
    $newChats = \App\Support\ChatLog::newCount();
    // Online shop: the Orders page appears once the shop is switched on (or has had orders).
    $shopOn = \App\Support\Shop::ordersVisible();
    if (! $shopOn) {
        $items = array_values(array_filter($items, fn ($i) => $i[0] !== '/admin/orders'));
    }
    $paidOrders = $shopOn && \App\Support\Shop::hasTables() ? \App\Models\Order::query()->whereIn('status', ['reserved', 'paid'])->count() : 0;
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
                                    @if ($href === '/admin/orders' && $paidOrders)
                                        <span class="rounded-full bg-white px-2 text-xs font-bold text-steel-950 tabular">{{ $paidOrders }}<span class="sr-only"> to prepare</span></span>
                                    @endif
                                    @if ($href === '/admin/members' && $membersOnline)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-white px-2 text-xs font-bold text-steel-950 tabular"><span aria-hidden="true" style="width:.45rem;height:.45rem;border-radius:999px;background:#16a34a"></span>{{ $membersOnline }}<span class="sr-only"> online now</span></span>
                                    @endif
                                    @if ($href === '/admin/blog' && $blogWaiting)
                                        <span class="rounded-full bg-white px-2 text-xs font-bold text-steel-950 tabular">{{ $blogWaiting }}<span class="sr-only"> comments to review</span></span>
                                    @endif
                                    @if ($href === '/admin/forum' && $forumPending)
                                        <span class="rounded-full bg-white px-2 text-xs font-bold text-steel-950 tabular">{{ $forumPending }}<span class="sr-only"> waiting for approval</span></span>
                                    @endif
                                    @if ($href === '/admin/chats')
                                        <span data-new-chats class="rounded-full bg-white px-2 text-xs font-bold text-steel-950 tabular" @unless ($newChats) hidden @endunless>{{ $newChats }}<span class="sr-only"> new</span></span>
                                    @endif
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
    {{-- New Buckny chats: checks every 30 seconds, updates the menu badge and pops up a note when a new chat comes in. --}}
    <div id="chat-pop" role="status" aria-live="polite" hidden style="position: fixed; right: 1rem; bottom: 1rem; z-index: 60; max-width: min(24rem, calc(100vw - 2rem)); padding: .9rem 1rem; border-radius: .9rem; background: var(--color-steel-950); color: #fff; box-shadow: 0 12px 32px rgb(0 0 0 / .3)">
        <p style="margin: 0; font-weight: 700">💬 <span data-chat-pop-title>New Buckny chat</span></p>
        <p data-chat-pop-text style="margin: .3rem 0 0; color: var(--color-steel-200); font-size: .9rem"></p>
        <p style="margin: .6rem 0 0; display: flex; gap: .75rem"><a data-chat-pop-link href="/admin/chats" style="font-weight: 700; color: var(--color-gold-400); text-decoration: underline">Open chat</a><button type="button" data-chat-pop-close style="color: var(--color-steel-300); text-decoration: underline; background: none; border: 0; cursor: pointer">Dismiss</button></p>
    </div>
    <script>
        (function () {
            var last = {{ (int) $newChats }}, pop = document.getElementById('chat-pop'), title = document.title.replace(/^\(\d+\) /, '');
            var setTitle = function (n) { document.title = (n ? '(' + n + ') ' : '') + title; };
            setTitle(last);
            pop.querySelector('[data-chat-pop-close]').addEventListener('click', function () { pop.hidden = true; });
            var check = function () {
                fetch('/admin/chats/new-count', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin', cache: 'no-store' })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (d) {
                        if (!d) return;
                        document.querySelectorAll('[data-new-chats]').forEach(function (b) { b.hidden = !d.count; b.firstChild.nodeValue = d.count; });
                        setTitle(d.count);
                        if (d.count > last && d.latest) {
                            pop.querySelector('[data-chat-pop-title]').textContent = (d.count - last > 1 ? (d.count - last) + ' new Buckny chats' : 'New Buckny chat') + (d.latest.callback ? ' · asked for a callback' : '');
                            pop.querySelector('[data-chat-pop-text]').textContent = d.latest.text ? '“' + d.latest.text + '”' : '';
                            pop.querySelector('[data-chat-pop-link]').href = '/admin/chats/' + d.latest.id;
                            pop.hidden = false;
                        }
                        last = d.count;
                    }).catch(function () {});
            };
            setInterval(function () { if (document.visibilityState === 'visible') check(); }, 30000);
            document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'visible') check(); });
        })();
    </script>
</body>
</html>
