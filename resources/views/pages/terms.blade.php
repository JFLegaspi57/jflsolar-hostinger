<x-site-layout title="Terms of use" path="/terms" description="Terms for using the JFL Solar website.">
    <x-page-header eyebrow="Legal" title="Terms of use" />
    <x-container class="py-10 sm:py-14">
        <div class="max-w-3xl space-y-8 text-steel-800 [&_h2]:mb-2 [&_h2]:text-xl [&_h2]:font-bold [&_h2]:text-steel-950">
            <p role="note" class="rounded-[var(--radius-control)] border border-[#f0d39a] bg-warn-50 px-4 py-3 text-warn-800">Draft terms pending review by the business owner and a legal adviser.</p>
            <section>
                <h2>Information on this website</h2>
                <p>Package, product and price information is provided for general guidance. A price, specification or availability shown on this website is not an offer. The scope, price, delivery charges, schedule and warranty for any purchase or installation are confirmed in a written quotation from JFL Solar.</p>
            </section>
            <section>
                <h2>Promotions</h2>
                <p>Promotional prices apply only between the dates shown and are subject to confirmation in a written quotation.</p>
            </section>
            <section>
                <h2>Estimates and system performance</h2>
                <p>Solar output, savings and backup duration depend on location, weather, consumption, tariffs and equipment. Nothing on this website is a guarantee of savings or performance.</p>
            </section>
            <section>
                <h2>Brands and trademarks</h2>
                <p>Product and brand names belong to their respective owners. Their appearance on this website does not mean JFL Solar is an authorised distributor or that the manufacturer endorses JFL Solar, unless expressly stated.</p>
            </section>
            @if (\App\Support\Shop::enabled())
            <section>
                <h2>Online orders</h2>
                <p>Items marked “Add to cart” can be ordered on this website for pickup at our office or warehouse. Prices are pickup prices in Philippine pesos at the time you place the order. Delivery is not included in online orders.</p>
                @if (\App\Support\Shop::inPersonPayment())
                <p>If you choose to pay at pickup, your order is reserved and you pay the order total in person when you collect it. We contact you when it is ready.</p>
                @endif
                @if (\App\Support\Shop::onlinePayment())
                <p>If you choose to pay online, you pay through PayMongo. That order is confirmed only when PayMongo confirms your payment; we then contact you to arrange pickup.</p>
                @endif
            </section>
            @endif
            <section>
                <h2>Contacting us</h2>
                <p>Sending an inquiry does not create a contract. We will reply to discuss your requirements.</p>
            </section>
        </div>
    </x-container>
</x-site-layout>
