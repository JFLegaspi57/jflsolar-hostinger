@php
    use App\Support\Contact;
    use App\Support\PublicContent;
    use App\Support\Site;
    $settings = PublicContent::settings();
    // The Contact page already lists every contact route and address, so its footer is shorter.
    $compact = ($path ?? null) === '/contact';
@endphp
<footer class="on-dark bg-steel-950 text-steel-200">
    <div id="newsletter" class="border-b border-white/10">
        <div class="mx-auto grid max-w-[76rem] gap-4 px-4 py-8 sm:px-6 lg:grid-cols-2 items-center lg:px-8">
            <div>
                <h2 class="font-display text-xl font-bold text-white">Get our promos and solar news</h2>
                <p class="mt-1 text-sm text-steel-300">Occasional emails about sales, new products and tips. Unsubscribe at any time.</p>
            </div>
            <form method="post" action="/newsletter" class="space-y-2">
                @csrf
                <div aria-hidden="true" class="absolute -left-[9999px] h-px w-px overflow-hidden"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off" value=""></label></div>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <label for="nl-email" class="sr-only">Email address</label>
                    <input id="nl-email" name="email" type="email" autocomplete="email" spellcheck="false" maxlength="254" required placeholder="Your email address" value="{{ old('newsletter_email') }}"
                        class="min-h-12 w-full flex-1 rounded-[var(--radius-control)] border border-steel-500 bg-white px-3.5 text-base text-steel-950 placeholder:text-steel-600">
                    <button type="submit" class="inline-flex min-h-12 items-center justify-center rounded-[var(--radius-control)] bg-gold-500 px-5 font-semibold whitespace-nowrap text-steel-950 hover:bg-gold-400">Subscribe</button>
                </div>
                <label class="flex cursor-pointer items-start gap-2 text-sm text-steel-300">
                    <input type="checkbox" name="consent" value="1" required class="mt-0.5 size-4 shrink-0 rounded">
                    <span>I agree to receive JFL Solar emails. See our <a href="/privacy" class="underline hover:text-white">privacy notice</a>.</span>
                </label>
                @if (session('newsletter_status'))<p role="status" class="text-sm font-semibold text-white">{{ session('newsletter_status') }}</p>@endif
                @if (session('newsletter_error'))<p role="alert" class="text-sm font-semibold text-gold-400">{{ session('newsletter_error') }}</p>@endif
            </form>
        </div>
    </div>
    <div class="mx-auto grid max-w-[76rem] gap-10 px-4 py-14 sm:px-6 md:grid-cols-2 {{ $compact ? '' : 'lg:grid-cols-[1.3fr_1fr_1fr_1fr]' }} lg:px-8">
        <div class="space-y-4">
            <x-logo plate />
            <p class="max-w-xs text-sm text-steel-300">{{ Site::DESCRIPTION }}</p>
            <p class="text-sm font-semibold text-white">{{ $settings->service_area }}</p>
        </div>

        <nav aria-label="Footer">
            <h2 class="mb-3 text-sm font-semibold tracking-wide text-white uppercase">Explore</h2>
            <ul class="{{ $compact ? 'flex flex-wrap gap-x-6 gap-y-1' : 'space-y-1' }} text-sm">
                @foreach (Site::NAV as $item)
                    <li><a href="{{ $item['href'] }}" class="inline-flex min-h-9 items-center hover:text-white hover:underline">{{ $item['label'] }}</a></li>
                @endforeach
            </ul>
        </nav>

        @unless ($compact)
        <div>
            <h2 class="mb-3 text-sm font-semibold tracking-wide text-white uppercase">Contact</h2>
            <ul class="space-y-1 text-sm">
                @foreach (Contact::channels($settings) as $c)
                    <li>
                        <a href="{{ $c['href'] }}" @if ($c['external']) target="_blank" rel="noopener noreferrer" @endif class="inline-flex min-h-9 items-center gap-2 hover:text-white hover:underline">
                            <x-icon :name="['phone' => 'phone', 'whatsapp' => 'whatsapp', 'viber' => 'viber', 'messenger' => 'messenger', 'facebook' => 'facebook', 'email' => 'mail'][$c['key']] ?? 'chat'" class="size-4 text-gold-500" />
                            <span class="text-steel-400">{{ $c['label'] }}:</span> <span class="tabular">{{ $c['detail'] }}</span>
                            @if ($c['external'])<span class="sr-only">(opens in a new tab)</span>@endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="space-y-4 text-sm">
            <div>
                <h2 class="mb-1 flex items-center gap-2 text-sm font-semibold tracking-wide text-white uppercase"><x-icon name="building" class="size-4 text-gold-500" /> Office</h2>
                <address class="not-italic">{{ $settings->office_address }}</address>
            </div>
            @if ($settings->warehouse_address)
                <div>
                    <h2 class="mb-1 flex items-center gap-2 text-sm font-semibold tracking-wide text-white uppercase"><x-icon name="warehouse" class="size-4 text-gold-500" /> Warehouse</h2>
                    <address class="not-italic">{{ $settings->warehouse_address }}</address>
                </div>
            @endif
        </div>
        @endunless
    </div>
    <div class="border-t border-white/10">
        <div class="mx-auto flex max-w-[76rem] flex-col gap-2 px-4 py-5 text-xs text-steel-400 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <p>© {{ now('Asia/Manila')->year }} {{ Site::NAME }}. All rights reserved.</p>
            <ul class="flex gap-4">
                <li><a href="/privacy" class="inline-flex min-h-9 items-center hover:text-white hover:underline">Privacy</a></li>
                <li><a href="/terms" class="inline-flex min-h-9 items-center hover:text-white hover:underline">Terms of use</a></li>
                <li><a href="{{ \App\Support\Members::current() ? '/account' : '/login' }}" class="inline-flex min-h-9 items-center hover:text-white hover:underline">{{ \App\Support\Members::current() ? 'Your account' : 'Sign in' }}</a></li>
            </ul>
        </div>
    </div>
</footer>
