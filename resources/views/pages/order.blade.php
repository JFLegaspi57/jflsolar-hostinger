@php
    use App\Support\Pricing;
    use App\Support\Shop;
    use App\Support\Site;
    $o = $order;
    $waiting = $o->status === 'pending_payment';
@endphp
<x-site-layout :title="'Order '.$o->reference" path="/orders" :noindex="true" :reviews-above-footer="false">
    @if ($waiting)
        <x-slot:head><meta http-equiv="refresh" content="6"></x-slot:head>
    @endif
    <x-container class="py-10 sm:py-14">
        <p class="text-sm font-semibold tracking-wide text-steel-600 uppercase">Order <span class="font-mono">{{ $o->reference }}</span></p>
        @if ($waiting)
            <h1 class="mt-2 text-3xl font-extrabold text-steel-950 sm:text-4xl">Confirming your payment…</h1>
            <p role="status" class="mt-3 max-w-2xl text-lg text-steel-700">This usually takes a few seconds. This page refreshes on its own. If you closed the payment page without paying, nothing was charged — <a href="/cart" class="underline">go back to your cart</a>.</p>
        @elseif ($o->status === 'cancelled')
            <h1 class="mt-2 text-3xl font-extrabold text-steel-950 sm:text-4xl">This order was cancelled</h1>
            <p class="mt-3 max-w-2xl text-lg text-steel-700">Please contact us if you have questions about it.</p>
        @else
            <h1 class="mt-2 flex items-center gap-3 text-3xl font-extrabold text-steel-950 sm:text-4xl"><x-icon name="check" class="size-9 text-ok-700" /> Thank you — your order is paid</h1>
            <p class="mt-3 max-w-2xl text-lg text-steel-700">
                Status: <span class="font-semibold text-steel-950">{{ $o->statusLabel() }}</span>.
                A payment receipt was emailed to {{ $o->customer_email }}. We will call or message you on {{ $o->customer_phone }} to arrange pickup.
            </p>
            <p class="mt-2 text-steel-700">Keep this page’s address to check your order later.</p>
        @endif

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
            </section>
            <aside class="self-start space-y-3 rounded-[var(--radius-panel)] border border-steel-200 bg-steel-50 p-5">
                <h2 class="text-lg font-bold text-steel-950">Pickup at</h2>
                <address class="not-italic text-steel-800">{{ Shop::pickupAddress($settings) }}</address>
                <p class="text-sm text-steel-700">Questions? Call <a href="{{ Site::telHref($settings->phone_e164) }}" class="font-semibold underline tabular">{{ $settings->phone_display ?? $settings->phone_e164 }}</a> and mention order <span class="font-mono">{{ $o->reference }}</span>.</p>
            </aside>
        </div>
    </x-container>
</x-site-layout>
