@php
    use App\Support\Contact;
    use App\Support\PublicContent;
    use App\Support\Site;
    $settings = PublicContent::settings();
    $menuContacts = collect(Contact::channels($settings))->whereIn('key', ['phone', 'whatsapp', 'viber']);
    $isActive = fn (string $href) => request()->is(ltrim($href, '/')) || request()->is(ltrim($href, '/').'/*');
@endphp
<header class="sticky top-0 z-40 border-b border-steel-200 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/90">
    <div class="mx-auto flex h-[4.5rem] max-w-[76rem] items-center gap-2 px-3 sm:gap-3 sm:px-6 lg:h-20 lg:px-8">
        <x-logo priority />
        <div class="ml-auto flex items-center gap-1 lg:gap-4">
            <nav aria-label="Main" class="site-nav hidden lg:block">
                <style>@media (max-width: 1279.98px) { .site-nav a { padding-inline: .45rem; font-size: .9rem; } .site-nav a > span { left: .45rem; right: .45rem; } }</style>
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
            <a href="{{ Site::QUOTE_HREF }}" class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] bg-gold-500 px-3.5 text-sm font-semibold whitespace-nowrap text-steel-950 hover:bg-gold-400 sm:px-4 sm:text-[0.95rem]">
                <span class="sm:hidden">Get a quote</span>
                <span class="hidden sm:inline">Request a Quote</span>
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
