<x-site-layout title="Privacy notice" path="/privacy" description="How JFL Solar handles personal information sent through this website's inquiry form.">
    <x-page-header eyebrow="Legal" title="Privacy notice" intro="How we handle the information you send through this website." />
    <x-container class="py-10 sm:py-14">
        <div class="max-w-3xl space-y-8 text-steel-800 [&_h2]:mb-2 [&_h2]:text-xl [&_h2]:font-bold [&_h2]:text-steel-950 [&_li]:ml-5 [&_li]:list-disc">
            <p role="note" class="rounded-[var(--radius-control)] border border-[#f0d39a] bg-warn-50 px-4 py-3 text-warn-800">Draft notice pending review by the business owner and a legal adviser. It describes how this website actually handles data.</p>

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

            <section>
                <h2>Cookies and tracking</h2>
                <p>The public website does not use analytics, advertising or tracking cookies. The website sets a session cookie needed for the inquiry form's security check, and staff who sign in to manage the website receive a sign-in cookie. Both are strictly necessary.</p>
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
