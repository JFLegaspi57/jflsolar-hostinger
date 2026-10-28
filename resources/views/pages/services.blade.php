@php
    use App\Support\PageText;
    use App\Support\Services;
    use App\Support\Site;
    use App\Support\Ui;
    $t = fn (string $key) => PageText::get($key);
@endphp
<x-site-layout title="Solar Services" path="/services" description="Solar PV assessment, consultancy, proposal, installation, commissioning and after-sales support for residential, commercial and industrial customers in the Philippines.">
    <x-page-header :eyebrow="$t('services.header.eyebrow')" :title="$t('services.header.title')" :intro="$t('services.header.intro')">
        <div class="flex flex-wrap gap-3">
            <a href="{{ Site::QUOTE_HREF }}" class="{{ Ui::button() }}">{{ $t('services.header.button1') }}</a>
            <a href="/packages" class="{{ Ui::button('secondary') }}">{{ $t('services.header.button2') }}</a>
        </div>
    </x-page-header>

    <x-section labelledby="who-title" id="installation">
        <x-section-heading id="who-title" :eyebrow="$t('services.who.eyebrow')" :title="$t('services.who.title')" />
        <div class="mt-10 grid gap-6 md:grid-cols-3">
            @foreach (Services::audiences() as $a)
                <section id="{{ $a['id'] }}" aria-labelledby="{{ $a['id'] }}-title" class="rounded-[var(--radius-panel)] border border-steel-200 p-6">
                    <h3 id="{{ $a['id'] }}-title" class="text-xl font-bold text-steel-950">{{ $a['title'] }}</h3>
                    <p class="mt-2 text-steel-600">{{ $a['body'] }}</p>
                    <ul class="mt-4 space-y-1.5">
                        @foreach ($a['points'] as $p)
                            <li class="flex items-start gap-2 text-steel-800"><x-icon name="check" class="mt-1 size-4 text-gold-800" />{{ $p }}</li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    </x-section>

    <x-section tone="muted" labelledby="process-title" id="consultancy">
        <x-section-heading id="process-title" :eyebrow="$t('services.process.eyebrow')" :title="$t('services.process.title')" :intro="$settings->service_area_note" />
        <ol class="mt-10 space-y-0 border-l-2 border-steel-300">
            @foreach (Services::processSteps() as $i => $step)
                <li class="relative pb-8 pl-8 last:pb-0">
                    <span aria-hidden="true" class="absolute top-0 -left-[1.05rem] inline-flex size-8 items-center justify-center rounded-full bg-steel-900 font-display text-sm font-bold text-gold-400 tabular">{{ $i + 1 }}</span>
                    <h3 class="text-xl font-bold text-steel-950"><span class="sr-only">Step {{ $i + 1 }}: </span>{{ $step['title'] }}</h3>
                    <p class="mt-1 max-w-2xl text-steel-700">{{ $step['body'] }}</p>
                </li>
            @endforeach
        </ol>
    </x-section>

    <x-section labelledby="types-title" id="system-types">
        <x-section-heading id="types-title" :eyebrow="$t('services.types.eyebrow')" :title="$t('services.types.title')" :intro="$t('services.types.intro')" />
        <div class="mt-10 grid gap-px overflow-hidden rounded-[var(--radius-panel)] border border-steel-200 bg-steel-200 lg:grid-cols-3">
            @foreach (Services::systemTypes() as $st)
                <section aria-labelledby="type-{{ $st['key'] }}" class="bg-white p-6 sm:p-8">
                    <p class="text-sm font-semibold tracking-wide text-gold-800 uppercase">{{ $st['lead'] }}</p>
                    <h3 id="type-{{ $st['key'] }}" class="mt-1 text-2xl font-extrabold text-steel-950">{{ $st['title'] }}</h3>
                    <p class="mt-3 text-steel-700">{{ $st['body'] }}</p>
                    <p class="mt-4 border-t border-steel-200 pt-4 text-sm text-steel-600"><strong class="text-steel-900">{{ $t('services.types.suits_label') }} </strong>{{ $st['suits'] }}</p>
                </section>
            @endforeach
        </div>
        <p class="mt-6 max-w-3xl text-sm text-steel-600">{{ $t('services.types.note') }}</p>
    </x-section>

    <x-section tone="dark" labelledby="cta-title">
        <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-center">
            <div>
                <h2 id="cta-title" class="text-3xl font-extrabold text-white">{{ $t('services.cta.title') }}</h2>
                <p class="mt-2 text-steel-300">{{ $t('services.cta.text') }} {{ $t('services.cta.call') }} <a href="{{ Site::telHref($settings->phone_e164) }}" class="font-semibold text-white underline">{{ $settings->phone_display }}</a>.</p>
            </div>
            <a href="{{ Site::QUOTE_HREF }}" class="{{ Ui::button('primary', 'lg') }}">{{ $t('services.cta.button') }}</a>
        </div>
        <p class="mt-6 text-sm text-steel-400">{{ $t('services.cta.equipment') }} <a href="/products" class="font-semibold text-white underline">{{ $t('services.cta.equipment_link') }}</a>.</p>
    </x-section>
</x-site-layout>
