@php
    use App\Support\Pricing;
    use App\Support\Shop;
    use App\Support\Ui;
    $input = 'min-h-12 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3.5 text-base text-steel-950 placeholder:text-steel-600 focus:border-steel-900 aria-[invalid=true]:border-danger-700 aria-[invalid=true]:bg-danger-50/40';
    $err = fn ($k) => $errors->first($k) ?: null;
    $pickup = Shop::pickupAddress($settings);
@endphp
<x-site-layout title="Checkout" path="/checkout" :noindex="true" :reviews-above-footer="false">
    <x-container class="py-10 sm:py-14">
        <p><a href="/cart" class="font-semibold text-steel-800 underline">← Back to cart</a></p>
        <h1 class="mt-3 text-3xl font-extrabold text-steel-950 sm:text-4xl">Checkout</h1>

        <div class="mt-8 grid gap-10 lg:grid-cols-[1fr_22rem]">
            <form method="post" action="/checkout" novalidate class="space-y-6">
                @csrf
                <div aria-hidden="true" class="absolute -left-[9999px] h-px w-px overflow-hidden">
                    <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off" value=""></label>
                </div>

                @if (session('checkout_error'))
                    <p role="alert" class="rounded-[var(--radius-control)] border border-[#f3c3be] bg-danger-50 p-4 font-semibold text-danger-700">{{ session('checkout_error') }}</p>
                @elseif ($errors->any())
                    <p role="alert" class="rounded-[var(--radius-control)] border border-[#f3c3be] bg-danger-50 p-4 font-semibold text-danger-700">Please correct the highlighted fields.</p>
                @endif

                <fieldset class="space-y-4">
                    <legend class="text-xl font-bold text-steel-950">Your details</legend>
                    <x-form-field id="c-name" label="Full name" :error="$err('name')" required>
                        <input id="c-name" name="name" type="text" autocomplete="name" value="{{ old('name') }}" maxlength="100" required
                            @if ($err('name')) aria-invalid="true" aria-describedby="c-name-error" @endif class="{{ $input }}">
                    </x-form-field>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-form-field id="c-phone" label="Mobile or phone" :error="$err('phone')" required>
                            <input id="c-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" value="{{ old('phone') }}" maxlength="30" required
                                @if ($err('phone')) aria-invalid="true" aria-describedby="c-phone-error" @endif class="{{ $input }} tabular">
                        </x-form-field>
                        <x-form-field id="c-email" label="Email" hint="Your payment receipt is sent here." :error="$err('email')" required>
                            <input id="c-email" name="email" type="email" autocomplete="email" spellcheck="false" value="{{ old('email') }}" maxlength="254" required
                                aria-describedby="c-email-hint{{ $err('email') ? ' c-email-error' : '' }}" @if ($err('email')) aria-invalid="true" @endif class="{{ $input }}">
                        </x-form-field>
                    </div>
                    <x-form-field id="c-notes" label="Notes for us (optional)" hint="For example, a preferred pickup day." :error="$err('notes')">
                        <textarea id="c-notes" name="notes" rows="3" maxlength="1000" aria-describedby="c-notes-hint{{ $err('notes') ? ' c-notes-error' : '' }}" @if ($err('notes')) aria-invalid="true" @endif class="{{ $input }} py-3">{{ old('notes') }}</textarea>
                    </x-form-field>
                </fieldset>

                <section aria-labelledby="pickup-h" class="rounded-[var(--radius-panel)] border border-steel-300 p-5">
                    <h2 id="pickup-h" class="text-xl font-bold text-steel-950">Pickup</h2>
                    <p class="mt-2 text-steel-800">Online orders are picked up at:</p>
                    <address class="mt-1 font-semibold not-italic text-steel-950">{{ $pickup }}</address>
                    <p class="mt-2 text-sm text-steel-700">After your payment is confirmed we call or message you to arrange the pickup time. Need delivery? <a href="/contact#quote" class="underline">Ask us for a quote</a> instead.</p>
                </section>

                <div>
                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="agree" value="1" @checked(old('agree') === '1') required
                            @if ($err('agree')) aria-invalid="true" aria-describedby="c-agree-error" @endif class="mt-1 size-5 rounded border-steel-500">
                        <span class="text-steel-800">I will pick up my order at the address above, and I agree to the <a href="/terms" class="underline">terms of use</a> and <a href="/privacy" class="underline">privacy notice</a>.</span>
                    </label>
                    @if ($err('agree'))<p id="c-agree-error" class="mt-1.5 text-sm font-semibold text-danger-700">{{ $err('agree') }}</p>@endif
                </div>
                @if ($err('total'))<p role="alert" class="font-semibold text-danger-700">{{ $err('total') }}</p>@endif

                <div>
                    <button type="submit" class="{{ Ui::button('primary', 'lg', 'w-full sm:w-auto') }}">Continue to payment · {{ Pricing::formatPhp($summary['total']) }}</button>
                    <p class="mt-2 text-sm text-steel-600">You’ll pay on PayMongo’s secure page with {{ Shop::methodsText() }}. We never see your card or wallet details.</p>
                </div>
            </form>

            <aside aria-labelledby="sum-h" class="self-start rounded-[var(--radius-panel)] border border-steel-200 bg-steel-50 p-5">
                <h2 id="sum-h" class="text-xl font-bold text-steel-950">Order summary</h2>
                <ul class="mt-3 divide-y divide-steel-200">
                    @foreach ($summary['lines'] as $line)
                        <li class="flex justify-between gap-3 py-2">
                            <span class="min-w-0 text-steel-800">{{ $line['product']->name }}<br><span class="text-sm text-steel-600 tabular">{{ $line['qty'] }} {{ $line['unit'] }} × {{ Pricing::formatPhp($line['unit_price']) }}</span></span>
                            <span class="font-semibold text-steel-950 tabular">{{ Pricing::formatPhp($line['line_total']) }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-3 flex justify-between border-t border-steel-300 pt-3 text-lg font-bold text-steel-950"><span>Total</span><span class="tabular">{{ Pricing::formatPhp($summary['total']) }}</span></p>
            </aside>
        </div>
    </x-container>
</x-site-layout>
