<x-site-layout title="Privacy notice" path="/privacy" :reviews-above-footer="false" description="How JFL Solar handles personal information sent through this website's inquiry form.">
    <x-page-header eyebrow="Legal" title="Privacy notice" intro="How we handle the information you send through this website." />
    <x-container class="py-10 sm:py-14">
        <div class="max-w-3xl space-y-8 text-steel-800 [&_h2]:mb-2 [&_h2]:text-xl [&_h2]:font-bold [&_h2]:text-steel-950 [&_li]:ml-5 [&_li]:list-disc">
            <p class="text-sm text-steel-600">Last updated October 31, 2026.</p>

            <section>
                <h2>Who we are</h2>
                <p>JFL Solar, {{ $settings->office_address }}. Phone {{ $settings->phone_display }}@if ($settings->public_email), email {{ $settings->public_email }}@endif. Contact us using these details for any privacy question or request.</p>
            </section>

            <section>
                <h2>What we collect</h2>
                <p>When you send an inquiry we receive the details you enter:</p>
                <ul>
                    <li>your name, and the phone number and/or email address you give us;</li>
                    <li>the type of inquiry, your message, and any package or product you selected;</li>
                    <li>optionally, your city or province and an electricity-bill range.</li>
                </ul>
                <p class="mt-2">Please do not include sensitive personal information (for example government ID numbers or payment details) in your message.</p>
            </section>

            <section>
                <h2>How we use it</h2>
                <p>Only to reply to your inquiry, prepare a quotation, and follow up on the request you made. We do not sell your information or use it for unrelated marketing.</p>
            </section>

            <section>
                <h2>How it is stored</h2>
                <ul>
                    <li>Your inquiry is saved in this website's database, which only signed-in JFL Solar staff can view, and a copy may be emailed to our business inbox.</li>
                    <li>To prevent spam and abuse, the website briefly counts submissions using a one-way, salted fingerprint of your IP address. It cannot be turned back into your IP address, is not stored with your inquiry, and expires automatically within 24 hours.</li>
                    <li>Our hosting provider may keep standard technical access logs for security and reliability.</li>
                    <li>We keep inquiries for as long as needed to handle your request and any resulting quotation or sale, then delete them.</li>
                </ul>
            </section>

            @if (\App\Support\Shop::enabled())
            <section>
                <h2>Online orders</h2>
                <p>When you order online we keep your name, phone number, email address, any notes you add, the items you ordered, where you will pick them up, and how and when you paid{{ \App\Support\Shop::onlinePayment() ? " (for online payments, PayMongo's payment reference)" : '' }}, so we can prepare your order, arrange pickup and keep a record of the sale. Only signed-in JFL Solar staff can view orders, and a copy may be emailed to our business inbox. We keep order records for as long as the law requires us to keep sales records, then delete them.</p>
                @if (\App\Support\Shop::onlinePayment())
                <p>Online payments are handled by PayMongo on its own secure page. To set up the payment we send PayMongo your name, email address, phone number, order number and the items and amounts in your order. You enter your card, GCash, Maya or bank details on PayMongo's page, not ours, and we do not store them. PayMongo processes your payment and sends your receipt under its own privacy policy.</p>
                @endif
                <p>Before you check out, the items in your cart are kept on our server and linked to your browser by the website's session cookie.</p>
            </section>

            @endif
            <section>
                <h2>Accounts</h2>
                <p>If you create an account we keep your name, email address, optional phone number and a scrambled (hashed) form of your password — never the password itself — plus when you last signed in. Orders you place while signed in are linked to your account so you can see them. Only signed-in JFL Solar staff can see account details. You can change your details, or delete your account, on your account page. Orders stay in our sales records after an account is deleted.</p>
            </section>

            <section>
                <h2>Promos and news emails</h2>
                <p>If you ask for our promos and news (when creating an account or on your account page), we keep your email address, your name if you gave it, and when and where you signed up, and use them only to send JFL Solar emails. Every email has an unsubscribe link, and you can also unsubscribe on your account page or by contacting us. We do not sell or share the list.</p>
            </section>

            <section>
                <h2>Cookies and tracking</h2>
                <p>The website sets a session cookie needed for the inquiry form's security check{{ \App\Support\Shop::enabled() ? ' and to keep your shopping cart' : '' }}, and people who sign in (customers, or staff managing the website) receive a sign-in cookie; if you tick “Keep me signed in”, that cookie lasts until you sign out. These are strictly necessary.</p>
                <p>To count how many people visit, the website also gives your browser a random visitor number in a cookie (kept for up to about 13 months). We store only a scrambled form of that number, the dates and times it visited, which pages of this website it opened and for how long, and the approximate country and city of each visit — no name, IP address, exact location or activity on other websites — and use it only to see how many new and returning visitors the website has, which pages are useful and roughly where visitors are. A small script on each page measures how long the page is on screen and sends that number to our own server when you leave. The country and city are worked out on our own server from your internet address, using a downloaded location database (IP geolocation by DB-IP); your address is not sent to anyone and is not kept. It is not shared with anyone and no advertising or third-party analytics services are used. If your browser sends a “Do Not Track” or “Global Privacy Control” signal, you are not counted. You can also delete or block cookies in your browser settings.</p>
            </section>

            <section>
                <h2>Messaging apps and phone calls</h2>
                <p>If you contact us by phone, WhatsApp or Viber, those services handle your communication under their own privacy policies.</p>
            </section>

            <section>
                <h2>Your rights</h2>
                <p>Under the Data Privacy Act of 2012 (Republic Act No. 10173) you may ask to access, correct or delete the personal information we hold about you, or object to its processing. Contact us using the details above.</p>
            </section>
        </div>
    </x-container>
</x-site-layout>
