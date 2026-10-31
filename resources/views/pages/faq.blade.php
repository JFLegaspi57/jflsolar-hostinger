@php
    use App\Support\Contact;
    use App\Support\PageText;
    use App\Support\Site;
    use App\Support\Ui;
    $channels = collect(Contact::channels($settings))->whereIn('key', ['phone', 'whatsapp', 'viber']);
@endphp
<x-site-layout title="Frequently Asked Questions" path="/faq" :reviews-above-footer="false" description="Answers about solar systems, sizing, equipment-only orders, payment and pickup, delivery, warranty, technical support and net metering at JFL Solar.">
    <x-slot:head>
        <script type="application/ld+json">{!! \App\Support\Faq::jsonLd($items) !!}</script>
        <style>
            .faq-item { border-bottom: 1px solid var(--color-steel-200); }
            .faq-item summary { list-style: none; cursor: pointer; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.1rem .25rem; min-height: 3rem; font-weight: 700; font-size: 1.125rem; color: var(--color-steel-950); }
            .faq-item summary::-webkit-details-marker { display: none; }
            .faq-item summary:hover { color: var(--color-steel-700); }
            .faq-item summary:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 2px; border-radius: 4px; }
            .faq-chev { flex: none; width: 1.25rem; height: 1.25rem; transition: transform .2s; color: var(--color-gold-800); }
            .faq-item[open] .faq-chev { transform: rotate(180deg); }
            .faq-body { padding: 0 .25rem 1.25rem; color: var(--color-steel-700); max-width: 46rem; }
            .faq-link { display: inline-flex; align-items: center; gap: .35rem; margin-top: .6rem; font-weight: 600; color: var(--color-steel-900); text-decoration: none; }
            .faq-link:hover, .faq-link:focus-visible { color: var(--color-gold-800); text-decoration: underline; }
        </style>
    </x-slot:head>
    <x-page-header eyebrow="Help" :title="PageText::get('faq.header.title')" :intro="PageText::get('faq.header.intro')" />
    <x-container class="grid gap-12 py-10 sm:py-14 lg:grid-cols-[1.25fr_1fr]">
        <div class="border-t border-steel-200">
            @foreach ($items as $i => $it)
                <details class="faq-item" id="q{{ $i + 1 }}" @if ($i === 0) open @endif>
                    <summary>
                        <span>{{ $it['q'] }}</span>
                        <svg class="faq-chev" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                    </summary>
                    <div class="faq-body">
                        <p>{{ $it['a'] }}</p>
                        @if ($it['link'])
                            <a href="{{ $it['link'][0] }}" class="faq-link">{{ $it['link'][1] }} <x-icon name="arrow" class="size-4" /></a>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>
        <aside aria-labelledby="faq-contact" style="align-self: start" class="rounded-[var(--radius-panel)] border border-steel-200 bg-steel-50 p-6">
            <h2 id="faq-contact" class="text-xl font-bold text-steel-950">Still have a question?</h2>
            <p class="mt-2 text-steel-700">Talk to our team. {{ $settings->business_hours ? 'We are open '.$settings->business_hours.'.' : '' }}</p>
            <ul class="mt-4 space-y-2">
                @foreach ($channels as $c)
                    <li>
                        <a href="{{ $c['href'] }}" @if ($c['external']) target="_blank" rel="noopener noreferrer" @endif class="flex min-h-11 items-center gap-3 rounded-[var(--radius-control)] px-2 hover:bg-white">
                            <x-icon :name="$c['key'] === 'phone' ? 'phone' : 'chat'" class="size-5 text-gold-800" />
                            <span><span class="block text-sm text-steel-600">{{ $c['label'] }}</span><span class="block font-semibold tabular">{{ $c['detail'] }}</span></span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <a href="{{ Site::QUOTE_HREF }}" class="{{ Ui::button('primary', 'lg', 'mt-5 w-full') }}">Request a Quote</a>
        </aside>
    </x-container>
</x-site-layout>
