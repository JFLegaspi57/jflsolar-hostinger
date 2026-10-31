@php
    use App\Support\Pricing;
    use App\Support\Shop;
    use App\Support\Site;
    $o = $order;
    $waiting = $o->status === 'pending_payment';
    // Re-check every 6 seconds for about 2 minutes, then stop (no endless automatic refresh).
    $autoRefresh = $waiting && $checks < 20;
    $refreshUrl = $o->publicPath().'&r='.($checks + 1);
    $preview ??= false;
    $justPlaced = $preview || session('order_placed');
@endphp
<x-site-layout :title="'Order '.$o->reference" path="/orders" :noindex="true" :reviews-above-footer="false">
    @if ($autoRefresh)
        <x-slot:head><meta http-equiv="refresh" content="6;url={{ $refreshUrl }}"></x-slot:head>
    @endif
    <x-container class="py-10 sm:py-14">
        @if ($preview)
            <p role="status" class="mb-6 rounded-[var(--radius-control)] border border-[#b7e2c6] bg-ok-50 p-4 font-semibold text-ok-700">Staff preview: this is the page a customer sees right after placing an order. No order was saved, and a copy of the summary was sent to the staff email. <a href="/checkout" class="underline">Back to checkout</a></p>
        @endif
        <p class="text-sm font-semibold tracking-wide text-steel-600 uppercase">Order <span class="font-mono">{{ $o->reference }}</span></p>
        @if ($waiting)
            <h1 class="mt-2 text-3xl font-extrabold text-steel-950 sm:text-4xl">{{ $autoRefresh ? 'Confirming your payment…' : 'We haven’t received your payment yet' }}</h1>
            @if ($autoRefresh)
                <p role="status" class="mt-3 max-w-2xl text-lg text-steel-700">This usually takes a few seconds. This page checks again on its own for about two minutes. If you closed the payment page without paying, nothing was charged — <a href="/cart" class="underline">go back to your cart</a>.</p>
            @else
                <p role="status" class="mt-3 max-w-2xl text-lg text-steel-700">If you paid, it can take a little longer to reach us — <a href="{{ $o->publicPath() }}" class="font-semibold underline">check again</a>, or call us with order number <span class="font-mono">{{ $o->reference }}</span>. If you did not finish paying, nothing was charged — <a href="/cart" class="underline">go back to your cart</a>.</p>
            @endif
        @elseif ($o->payment_mode === 'in_person' && ! $o->paid_at && $o->status !== 'cancelled')
            <h1 class="mt-2 flex items-center gap-3 text-3xl font-extrabold text-steel-950 sm:text-4xl"><x-icon name="check" class="size-9 text-ok-700" /> {{ $justPlaced ? 'Thank you — your order is placed' : 'Your order' }}</h1>
            <p role="status" class="mt-3 max-w-2xl text-lg text-steel-700">
                Status: <span class="font-semibold text-steel-950">{{ $o->statusLabel() }}</span>.
                Nothing has been charged. Total to pay: <span class="font-semibold text-steel-950">{{ Pricing::formatPhp($o->total_php) }}</span>@if ($o->payment_preference) by <span class="font-semibold text-steel-950">{{ Shop::choiceLabel($o->payment_preference) }}</span>@endif.
                We will call or message you on {{ $o->customer_phone }} to confirm your order and when it is ready to collect at our {{ strtolower(Shop::LOCATION_NAMES[$o->pickup_location] ?? 'office') }}.
            </p>
            @if (isset(Shop::IN_PERSON_CHOICES[$o->payment_preference]))
                <p class="mt-2 max-w-2xl text-steel-700">{{ Shop::IN_PERSON_CHOICES[$o->payment_preference]['hint'] }}</p>
            @endif
            <div class="mt-4 max-w-2xl">@include('partials.shop-call-sales', ['reference' => $o->reference])</div>
            <p class="mt-2 text-steel-700">Please bring your order number <span class="font-mono font-semibold">{{ $o->reference }}</span>. Keep this page’s address to check your order later.</p>
        @elseif ($o->status === 'cancelled')
            <h1 class="mt-2 text-3xl font-extrabold text-steel-950 sm:text-4xl">This order was cancelled</h1>
            <p class="mt-3 max-w-2xl text-lg text-steel-700">Please contact us if you have questions about it.</p>
        @else
            <h1 class="mt-2 flex items-center gap-3 text-3xl font-extrabold text-steel-950 sm:text-4xl"><x-icon name="check" class="size-9 text-ok-700" /> {{ $o->status === 'completed' ? 'Thank you — order collected' : 'Thank you — your order is paid' }}</h1>
            <p class="mt-3 max-w-2xl text-lg text-steel-700">
                Status: <span class="font-semibold text-steel-950">{{ $o->statusLabel() }}</span>.
                @if ($o->payment_mode === 'online')A payment receipt was emailed to {{ $o->customer_email }}.@endif
                @if ($o->status !== 'completed')We will call or message you on {{ $o->customer_phone }} to arrange pickup.@endif
            </p>
            @if ($o->status !== 'completed')
                <div class="mt-4 max-w-2xl">@include('partials.shop-call-sales', ['reference' => $o->reference])</div>
            @endif
            <p class="mt-2 text-steel-700">Keep this page’s address to check your order later.</p>
        @endif

        @unless ($waiting)
            <div class="mt-6 flex flex-col gap-2 sm:flex-row">
                <a href="{{ $printUrl }}" target="_blank" rel="noopener" class="{{ \App\Support\Ui::button('primary', 'lg') }}">Print order summary</a>
            </div>
            <p class="mt-2 text-sm text-steel-600">Opens your full order summary in a new window, ready to print or save as PDF.</p>
        @endunless

        <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_20rem]">
            <section aria-labelledby="items-h" class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-5">
                <h2 id="items-h" class="text-xl font-bold text-steel-950">Items</h2>
                <ul class="mt-3 divide-y divide-steel-200">
                    @foreach ($o->items as $i)
                        <li class="flex justify-between gap-3 py-2">
                            <span class="min-w-0 text-steel-800">{{ $i->name }}<br><span class="text-sm text-steel-600 tabular">{{ $i->quantity }} {{ $i->unitLabel() }} × {{ Pricing::formatPhp($i->unit_price_php) }}</span></span>
                            <span class="font-semibold text-steel-950 tabular">{{ Pricing::formatPhp($i->line_total_php) }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-3 flex justify-between border-t border-steel-300 pt-3 text-lg font-bold text-steel-950"><span>Total</span><span class="tabular">{{ Pricing::formatPhp($o->total_php) }}</span></p>

                <h2 class="mt-8 text-xl font-bold text-steel-950">Your details</h2>
                <dl class="mt-3 grid gap-x-6 gap-y-2 text-steel-800" style="grid-template-columns:auto 1fr">
                    <dt class="font-semibold text-steel-950">Name</dt><dd>{{ $o->customer_name }}</dd>
                    <dt class="font-semibold text-steel-950">Mobile / phone</dt><dd class="tabular">{{ $o->customer_phone }}</dd>
                    <dt class="font-semibold text-steel-950">Email</dt><dd class="break-all">{{ $o->customer_email }}</dd>
                    <dt class="font-semibold text-steel-950">Payment</dt><dd>{{ $o->paid_at ? 'Paid' : ($o->payment_mode === 'online' ? 'Online (PayMongo)' : 'Pay at pickup'.($o->payment_preference ? ' by '.Shop::choiceLabel($o->payment_preference) : '')) }}</dd>
                    <dt class="font-semibold text-steel-950">Notes</dt><dd class="whitespace-pre-line">{{ $o->customer_notes ?: 'None' }}</dd>
                </dl>
            </section>
            <aside class="self-start space-y-3 rounded-[var(--radius-panel)] border border-steel-200 bg-steel-50 p-5">
                <h2 class="text-lg font-bold text-steel-950">Pickup at our {{ strtolower(Shop::LOCATION_NAMES[$o->pickup_location] ?? 'office') }}</h2>
                <address class="not-italic text-steel-800">{{ Shop::pickupAddress($settings, $o->pickup_location) }}</address>
                @include('partials.shop-directions')
                <p class="text-sm font-semibold text-steel-950">Call Sales before you come, so we can have your order ready.</p>
                <p class="text-sm text-steel-700">Questions? Call <a href="{{ Site::telHref($settings->phone_e164) }}" class="font-semibold underline tabular">{{ $settings->phone_display ?? $settings->phone_e164 }}</a> and mention order <span class="font-mono">{{ $o->reference }}</span>.</p>
            </aside>
        </div>
    </x-container>
</x-site-layout>
