@php use App\Support\Site; use App\Support\Ui; @endphp
{{-- Closing call-to-action shared by the Services sub-pages. $ctaTitle, $ctaText. --}}
<x-section tone="dark" labelledby="svc-cta-title">
    <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-center">
        <div>
            <h2 id="svc-cta-title" class="text-3xl font-extrabold text-white">{{ $ctaTitle }}</h2>
            <p class="mt-2 max-w-2xl text-steel-300">{{ $ctaText }} Call our Sales team at <a href="{{ Site::telHref($settings->phone_e164) }}" class="font-semibold text-white underline">{{ $settings->phone_display }}</a>.</p>
        </div>
        <a href="{{ Site::QUOTE_HREF }}" class="{{ Ui::button('primary', 'lg') }}">Inquire Now</a>
    </div>
    <p class="mt-6 text-sm text-steel-400">See all our <a href="/services" class="font-semibold text-white underline">services</a>, our <a href="/projects" class="font-semibold text-white underline">installations</a> and the <a href="/products" class="font-semibold text-white underline">products we supply</a>.</p>
</x-section>
