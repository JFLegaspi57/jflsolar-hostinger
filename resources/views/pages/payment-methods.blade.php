@php
    use App\Support\Shop;
    use App\Support\Ui;
    $inPerson = Shop::inPersonChoices($settings);
    $online = Shop::onlinePayment();
    $card = 'rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6 sm:p-8';
    $icons = ['cash' => '💵', 'gcash' => '📱', 'bank_transfer' => '🏦', 'check' => '🧾', 'card' => '💳'];
@endphp
<x-site-layout title="Payment Methods" path="/payment-methods" :reviews-above-footer="false"
    description="How to pay for your JFL Solar order: cash, GCash, bank transfer, check or card at pickup{{ $online ? ', or online through PayMongo' : '' }}. No cash on delivery (COD).">
    <x-page-header eyebrow="About JFL Solar" title="Payment Methods" intro="The ways you can pay for solar panels, inverters, batteries and other solar PV system materials from JFL Solar." />
    <x-container class="py-10 sm:py-14">
        <div class="mx-auto max-w-3xl space-y-8">
            @if ($inPerson)
                <section aria-labelledby="pm-pickup" class="{{ $card }}">
                    <h2 id="pm-pickup" class="text-2xl font-bold text-steel-950">Pay at pickup</h2>
                    <p class="mt-2 text-steel-700">Choose one of these when you place your order. We contact you when your order is ready.</p>
                    <ul class="mt-4 divide-y divide-steel-200">
                        @foreach ($inPerson as $c)
                            <li class="flex gap-4 py-4">
                                <span aria-hidden="true" class="text-2xl" style="line-height: 1">{{ $icons[$c] ?? '•' }}</span>
                                <div>
                                    <p class="font-bold text-steel-950">{{ Shop::IN_PERSON_CHOICES[$c]['label'] }}</p>
                                    <p class="mt-1 text-steel-700">{{ Shop::IN_PERSON_CHOICES[$c]['hint'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($online)
                <section aria-labelledby="pm-online" class="{{ $card }}">
                    <h2 id="pm-online" class="text-2xl font-bold text-steel-950">Pay online</h2>
                    <p class="mt-2 text-steel-700">Pay when you check out on PayMongo’s secure payment page with <strong>{{ Shop::methodsText() }}</strong>.</p>
                    <ul class="mt-3 list-disc text-steel-700" style="padding-left: 1.4rem">
                        <li class="mt-1">You enter your card or wallet details on PayMongo’s page. JFL Solar never sees or stores them.</li>
                        <li class="mt-1">Your order is confirmed once PayMongo confirms your payment. Then we contact you to arrange pickup.</li>
                        <li class="mt-1">PayMongo sends your receipt by email.</li>
                    </ul>
                </section>
            @endif

            <section aria-labelledby="pm-cod" class="border-l-4 border-gold-500 bg-gold-50 p-5 text-steel-800" style="border-radius: .75rem">
                <h2 id="pm-cod" class="text-xl font-bold text-steel-950">No cash on delivery (COD)</h2>
                <p class="mt-2">JFL Solar does not offer cash on delivery. If your order needs delivery, check our <a href="/delivery-rates" class="font-semibold underline">delivery rates</a> and contact us to arrange payment and delivery.</p>
            </section>

            <section aria-labelledby="pm-good" class="{{ $card }}">
                <h2 id="pm-good" class="text-xl font-bold text-steel-950">Good to know</h2>
                <ul class="mt-3 list-disc text-steel-700" style="padding-left: 1.4rem">
                    <li class="mt-1">For GCash and bank transfer, only pay to the GCash number or bank account that JFL Solar sends you when we confirm your order.</li>
                    <li class="mt-1">Keep your receipt or reference number until you have your order.</li>
                    <li class="mt-1">Questions about payment? Call or text our Sales team at <a href="tel:+639694712098" class="font-semibold whitespace-nowrap underline">0969 471 2098</a>, or <a href="/contact" class="font-semibold underline">contact us</a>.</li>
                </ul>
                <p class="mt-5 flex flex-wrap gap-3">
                    <a href="/products" class="{{ Ui::button('primary') }}">Browse products</a>
                    <a href="/terms" class="{{ Ui::button('secondary') }}">Terms of sale</a>
                </p>
            </section>
        </div>
    </x-container>
</x-site-layout>
