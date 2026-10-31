@php
    use App\Support\Pricing;
    use App\Support\Shop;
    use App\Support\Ui;
    $input = 'min-h-12 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3.5 text-base text-steel-950 placeholder:text-steel-600 focus:border-steel-900 aria-[invalid=true]:border-danger-700 aria-[invalid=true]:bg-danger-50/40';
    $err = fn ($k) => $errors->first($k) ?: null;
    $payDefault = old('payment', $choices[0] ?? 'cash');
    $placeDefault = old('pickup', array_key_first($locations));
    $radio = 'mt-1 size-5 shrink-0 border-steel-500';
    $choice = 'flex cursor-pointer items-start gap-3 rounded-[var(--radius-control)] border border-steel-300 p-4 hover:border-steel-900';
@endphp
<x-site-layout title="Checkout" path="/checkout" :noindex="true" :reviews-above-footer="false">
    <x-container class="py-10 sm:py-14">
        <p><a href="/cart" class="font-semibold text-steel-800 underline">← Back to cart</a></p>
        <h1 class="mt-3 text-3xl font-extrabold text-steel-950 sm:text-4xl">Checkout</h1>
        @include('partials.shop-preview-note')

        <div class="mt-8 grid gap-10 lg:grid-cols-[1fr_22rem]">
            <form method="post" action="/checkout" novalidate class="space-y-6">
                @csrf
                <input type="hidden" name="fingerprint" value="{{ $fingerprint }}">
                <div aria-hidden="true" class="absolute -left-[9999px] h-px w-px overflow-hidden">
                    <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off" value=""></label>
                </div>

                @if (session('checkout_preview'))
                    <p role="status" class="rounded-[var(--radius-control)] border border-[#b7e2c6] bg-ok-50 p-4 font-semibold text-ok-700">{{ session('checkout_preview') }}</p>
                @endif
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
                        <x-form-field id="c-email" label="Email" hint="Your order details are sent here." :error="$err('email')" required>
                            <input id="c-email" name="email" type="email" autocomplete="email" spellcheck="false" value="{{ old('email') }}" maxlength="254" required
                                aria-describedby="c-email-hint{{ $err('email') ? ' c-email-error' : '' }}" @if ($err('email')) aria-invalid="true" @endif class="{{ $input }}">
                        </x-form-field>
                    </div>
                    <x-form-field id="c-notes" label="Notes for us (optional)" hint="For example, a preferred pickup day." :error="$err('notes')">
                        <textarea id="c-notes" name="notes" rows="3" maxlength="1000" aria-describedby="c-notes-hint{{ $err('notes') ? ' c-notes-error' : '' }}" @if ($err('notes')) aria-invalid="true" @endif class="{{ $input }} py-3">{{ old('notes') }}</textarea>
                    </x-form-field>
                </fieldset>

                <fieldset class="space-y-3" @if ($err('pickup')) aria-describedby="c-pickup-error" @endif>
                    <legend class="text-xl font-bold text-steel-950">Pickup {{ count($locations) === 1 ? 'only at' : 'at' }}</legend>
                    @if (count($locations) === 1)
                        <input type="hidden" name="pickup" value="{{ array_key_first($locations) }}">
                        <p><span class="block font-semibold text-steel-950">Our {{ strtolower(Shop::LOCATION_NAMES[array_key_first($locations)]) }}</span><span class="block text-steel-700">{{ reset($locations) }}</span>@include('partials.shop-directions')</p>
                    @else
                        @foreach ($locations as $key => $address)
                            <label class="{{ $choice }}">
                                <input type="radio" name="pickup" value="{{ $key }}" @checked($placeDefault === $key) class="{{ $radio }}">
                                <span><span class="block font-semibold text-steel-950">Our {{ strtolower(Shop::LOCATION_NAMES[$key]) }}</span><span class="block text-steel-700">{{ $address }}</span>@include('partials.shop-directions')</span>
                            </label>
                        @endforeach
                    @endif
                    @if ($err('pickup'))<p id="c-pickup-error" class="text-sm font-semibold text-danger-700">{{ $err('pickup') }}</p>@endif
                    @include('partials.shop-call-sales')
                    <p class="text-sm text-steel-700">We call or message you when your order is ready to collect. Need delivery? <a href="/contact#quote" class="underline">Ask us for a quote</a> instead.</p>
                </fieldset>

                <fieldset class="space-y-3" @if ($err('payment')) aria-describedby="c-payment-error" @endif>
                    <legend class="text-xl font-bold text-steel-950">How will you pay?</legend>
                    @if (array_diff($choices, ['online']))
                        <p class="text-sm text-steel-700">Nothing is charged now. Your order is reserved and you pay the total{{ in_array('online', $choices, true) ? ', unless you choose to pay online' : '' }}.</p>
                    @endif
                    @foreach ($choices as $c)
                        <label class="{{ $choice }}">
                            <input type="radio" name="payment" value="{{ $c }}" @checked($payDefault === $c) class="{{ $radio }}">
                            <span>
                                <span class="block font-semibold text-steel-950">{{ $c === 'online' ? 'Pay online now' : Shop::IN_PERSON_CHOICES[$c]['label'] }}</span>
                                <span class="block text-steel-700">{{ $c === 'online' ? 'Pay now on PayMongo’s secure page with '.Shop::methodsText().'. We never see your card or wallet details.' : Shop::IN_PERSON_CHOICES[$c]['hint'] }}</span>
                            </span>
                        </label>
                    @endforeach
                    @if ($err('payment'))<p id="c-payment-error" class="text-sm font-semibold text-danger-700">{{ $err('payment') }}</p>@endif
                </fieldset>

                <div>
                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="agree" value="1" @checked(old('agree') === '1') required
                            @if ($err('agree')) aria-invalid="true" aria-describedby="c-agree-error" @endif class="mt-1 size-5 rounded border-steel-500">
                        <span class="text-steel-800">I will pick up my order at the place I chose, and I agree to the <a href="/terms" class="underline">terms of use</a> and <a href="/privacy" class="underline">privacy notice</a>.</span>
                    </label>
                    @if ($err('agree'))<p id="c-agree-error" class="mt-1.5 text-sm font-semibold text-danger-700">{{ $err('agree') }}</p>@endif
                </div>
                @if ($err('total'))<p role="alert" class="font-semibold text-danger-700">{{ $err('total') }}</p>@endif

                <div>
                    <button type="submit" class="{{ Ui::button('primary', 'lg', 'w-full sm:w-auto') }}">Place order · {{ Pricing::formatPhp($summary['total']) }}</button>
                    @if (in_array('online', $choices, true))<p class="mt-2 text-sm text-steel-600">If you chose to pay online, you go to PayMongo’s payment page next.</p>@endif
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
