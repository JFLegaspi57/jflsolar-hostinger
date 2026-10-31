@php
    use App\Support\Site;
    use App\Support\Ui;
    $intro = 'Pick your own hybrid inverter, solar panels and battery, set the quantities, and add the mounting, cables, protection devices and accessories you need. Every part shows its own price and the total updates as you go.';
@endphp
<x-site-layout title="Build Your Own Solar Package" path="/packages/build-your-own" :reviews-above-footer="false" description="Build your own solar package: choose a hybrid inverter, solar panels and battery, add mounting, cables, safety devices and accessories, see the total and add everything to your basket. Pickup prices from JFL Solar.">
    <x-page-hero eyebrow="Packages" title="Build Your Own" :intro="$intro"
        image="/images/packages-vents-1280.jpg" srcset="/images/packages-vents-640.jpg 640w, /images/packages-vents-960.jpg 960w, /images/packages-vents-1280.jpg 1280w"
        imageAlt="Long rows of solar panels installed on a commercial metal roof with roof vents" focus="45% 55%" />

    <x-container class="pt-10 pb-24">
        <div class="mx-auto max-w-5xl">
            <x-breadcrumbs :items="[['href' => '/', 'label' => 'Home'], ['href' => '/packages', 'label' => 'Packages'], ['label' => 'Build Your Own']]" />
            @if ($lines)
                <div class="mt-6">
                    <x-package-builder :lines="$lines" :action="'/packages/build-your-own/basket'" heading="Your system"
                        intro="We start you with a typical small system — change any part, set a quantity to 0 to leave it out, then open the add-on groups below for everything else you need." />
                </div>
                <p class="mt-4 text-sm text-steel-600">Not sure what size you need? Try our <a href="/pv-calculator" class="font-semibold underline">PV Calculator</a>, or compare our ready-made <a href="/packages?type=materials" class="font-semibold underline">Materials Only</a> and <a href="/packages?type=complete" class="font-semibold underline">Complete Packages</a>.</p>
            @else
                <div class="mt-6 rounded-[var(--radius-panel)] border border-steel-200 bg-steel-50 p-6">
                    <h2 class="text-xl font-bold text-steel-950">Online ordering is not available right now</h2>
                    <p class="mt-2 text-steel-700">Tell us what you need and we’ll put together a quotation.</p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <a href="{{ Site::QUOTE_HREF }}" class="{{ Ui::button('primary', 'lg') }}">Request a Quote</a>
                        <a href="{{ Site::telHref($settings->phone_e164) }}" class="{{ Ui::button('secondary', 'lg') }}"><x-icon name="phone" class="size-4" /> Call {{ $settings->phone_display }}</a>
                    </div>
                </div>
            @endif
        </div>
    </x-container>
</x-site-layout>
