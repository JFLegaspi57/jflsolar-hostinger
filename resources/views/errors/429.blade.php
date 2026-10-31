<x-site-layout title="Please wait a moment" :path="'/'.request()->path()" noindex>
    <div class="mx-auto flex w-full max-w-2xl flex-1 flex-col justify-center px-4 py-20">
        <p class="font-display text-sm font-bold tracking-wide text-gold-800 uppercase">Too many tries</p>
        <h1 class="mt-2 text-4xl font-extrabold text-steel-950">Please wait a minute, then try again.</h1>
        <p class="mt-4 text-lg text-steel-600">We received several requests from you in a short time. This protects the website from spam. Your cart is still saved.</p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="/cart" class="{{ \App\Support\Ui::button() }}">Back to your cart</a>
            <a href="/contact" class="{{ \App\Support\Ui::button('secondary') }}">Contact us</a>
        </div>
    </div>
</x-site-layout>
