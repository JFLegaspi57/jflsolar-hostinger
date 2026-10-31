@php
    use App\Support\Contact;
    use App\Support\PublicContent;
    use App\Support\Site;
    $settings = PublicContent::settings();
    $menuContacts = collect(Contact::channels($settings))->whereIn('key', ['phone', 'whatsapp', 'viber']);
    $cartCount = \App\Support\Shop::visible() ? \App\Support\Shop::count() : null;
    $member = \App\Support\Members::current();
    $isActive = fn (string $href) => request()->is(ltrim($href, '/')) || request()->is(ltrim($href, '/').'/*');
@endphp
<header class="sticky top-0 z-40 border-b border-steel-200 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/90">
    <div class="mx-auto flex h-[4.5rem] max-w-[76rem] items-center gap-2 px-3 sm:gap-3 sm:px-6 lg:h-20 lg:px-8">
        <x-logo priority class="site-logo" />
        <div class="hdr-actions ml-auto flex items-center gap-1 lg:gap-4">
            <nav aria-label="Main" class="site-nav hidden lg:block">
                <style>@media (max-width: 1279.98px) { .site-nav a { padding-inline: .45rem; font-size: .9rem; } .site-nav a > span { left: .45rem; right: .45rem; } }
                    @media (min-width: 1024px) and (max-width: 1279.98px) { .site-nav a { padding-inline: .3rem; font-size: .86rem; } .site-nav a > span { left: .3rem; right: .3rem; } .hdr-actions { gap: .2rem !important; } }
                    @media (min-width: 1024px) and (max-width: 1219.98px) { .quote-long { display: none !important; } .quote-short { display: inline !important; } }
                    @media (max-width: 639.98px) { .auth-label { display: none; } header [data-auth-open], header a[href="/account"] { padding-inline: 0 !important; } .hdr-actions { gap: 0 !important; } }
                    @media (max-width: 399.98px) { header .site-logo img { width: 104px !important; } }</style>
                <ul class="flex items-center gap-1">
                    @foreach (Site::NAV as $item)
                        @php $active = $isActive($item['href']); @endphp
                        <li>
                            <a href="{{ $item['href'] }}" @if ($active) aria-current="page" @endif
                                class="relative inline-flex min-h-11 items-center px-3 text-[0.95rem] font-semibold whitespace-nowrap transition-colors {{ $active ? 'text-steel-950' : 'text-steel-700 hover:text-steel-950' }}">
                                {{ $item['label'] }}
                                @if ($active)<span aria-hidden="true" class="absolute inset-x-3 bottom-1 h-[3px] rounded bg-gold-500"></span>@endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
            @if ($member)
                <a href="/account" aria-label="Your account" @if (request()->is('account')) aria-current="page" @endif
                    style="min-width: 2.75rem" class="inline-flex min-h-11 items-center justify-center gap-1 rounded-[var(--radius-control)] px-2 text-sm font-semibold whitespace-nowrap text-steel-700 hover:bg-steel-100 hover:text-steel-950 aria-[current=page]:bg-gold-50">
                    @if ($photo = \App\Support\Members::photoUrl($member))
                        <img src="{{ $photo }}" alt="" width="24" height="24" class="size-6 rounded-full object-cover">
                    @else
                        <x-icon name="user" class="size-4" />
                    @endif
                    <span class="auth-label">Account</span>
                </a>
            @else
                <a href="/login" data-auth-open="login" aria-haspopup="dialog" aria-label="Log in or create an account" @if (request()->is('login', 'signup')) aria-current="page" @endif
                    style="min-width: 2.75rem" class="inline-flex min-h-11 items-center justify-center gap-1 rounded-[var(--radius-control)] px-2 text-sm font-semibold whitespace-nowrap text-steel-700 hover:bg-steel-100 hover:text-steel-950 aria-[current=page]:bg-gold-50">
                    <x-icon name="user" class="size-4" />
                    <span class="auth-label">Log in</span>
                </a>
            @endif
            @if ($cartCount !== null)
                <a href="/cart" @if (request()->is('cart', 'checkout')) aria-current="page" @endif
                    class="relative inline-flex size-11 items-center justify-center rounded-[var(--radius-control)] text-steel-900 hover:bg-steel-100 aria-[current=page]:bg-gold-50">
                    <x-icon name="cart" class="size-6" />
                    <span class="sr-only">Cart{{ $cartCount ? ', '.$cartCount.' '.($cartCount === 1 ? 'item' : 'items') : ', empty' }}</span>
                    @if ($cartCount)<span aria-hidden="true" class="absolute top-0 rounded-full bg-steel-900 px-1 text-center text-xs leading-5 font-bold text-white tabular" style="right: 0; min-width: 1.25rem; transform: translate(20%, -20%)">{{ $cartCount }}</span>@endif
                </a>
            @endif
            <a href="{{ Site::QUOTE_HREF }}" class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] bg-gold-500 px-3.5 text-sm font-semibold whitespace-nowrap text-steel-950 hover:bg-gold-400 sm:px-4 sm:text-[0.95rem]">
                <span class="quote-short sm:hidden">Get a quote</span>
                <span class="quote-long hidden sm:inline">Request a Quote</span>
            </a>

            {{-- Mobile menu: native <dialog>.showModal() — focus moves in, page behind is inert, Escape closes, focus returns. --}}
            <button type="button" data-open-dialog="mobile-menu" aria-haspopup="dialog" aria-controls="mobile-menu"
                class="inline-flex size-11 items-center justify-center rounded-[var(--radius-control)] text-steel-900 hover:bg-steel-100 lg:hidden">
                <x-icon name="menu" class="size-6" />
                <span class="sr-only">Open menu</span>
            </button>
            <dialog id="mobile-menu" aria-label="Menu" data-backdrop-close class="sheet bg-white text-steel-900 shadow-2xl">
                <div class="flex h-full flex-col overflow-y-auto overscroll-contain">
                    <div class="flex items-center justify-between border-b border-steel-200 px-4 py-3">
                        <span class="font-display text-lg font-bold">Menu</span>
                        <button type="button" data-close-dialog autofocus class="inline-flex size-11 items-center justify-center rounded-[var(--radius-control)] hover:bg-steel-100">
                            <x-icon name="x" class="size-6" />
                            <span class="sr-only">Close menu</span>
                        </button>
                    </div>
                    <nav aria-label="Main" class="px-2 py-3">
                        <ul>
                            @foreach (Site::NAV as $item)
                                <li>
                                    <a href="{{ $item['href'] }}" data-close-dialog @if ($isActive($item['href'])) aria-current="page" @endif
                                        class="flex min-h-12 items-center justify-between rounded-[var(--radius-control)] px-3 text-lg font-semibold hover:bg-steel-50 aria-[current=page]:bg-gold-50 aria-[current=page]:text-steel-950">
                                        {{ $item['label'] }}
                                        <x-icon name="arrow" class="size-4 text-steel-400" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                    <div class="mt-auto space-y-3 border-t border-steel-200 p-4">
                        <a href="{{ $member ? '/account' : '/login' }}" @unless ($member) data-auth-open="login" @endunless data-close-dialog class="flex min-h-12 items-center justify-center gap-2 rounded-[var(--radius-control)] border border-steel-300 font-semibold text-steel-950 hover:bg-steel-50"><x-icon name="user" class="size-5" /> {{ $member ? 'Your account' : 'Log in / Create account' }}</a>
                        <a href="{{ Site::QUOTE_HREF }}" data-close-dialog class="flex min-h-12 items-center justify-center rounded-[var(--radius-control)] bg-gold-500 font-semibold text-steel-950 hover:bg-gold-400">Request a Quote</a>
                        <ul class="space-y-1">
                            @foreach ($menuContacts as $c)
                                <li>
                                    <a href="{{ $c['href'] }}" class="flex min-h-11 items-center gap-3 rounded-[var(--radius-control)] px-2 hover:bg-steel-50">
                                        <x-icon :name="$c['key'] === 'phone' ? 'phone' : 'chat'" class="size-5 text-gold-800" />
                                        <span>
                                            <span class="block text-sm text-steel-600">{{ $c['label'] }}</span>
                                            <span class="block font-semibold tabular">{{ $c['detail'] }}</span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </dialog>
        </div>
    </div>
</header>
@unless ($member)
    @include('partials.auth-dialog')
@endunless
