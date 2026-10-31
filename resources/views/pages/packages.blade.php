@php
    // Button order: All packages, Materials Only, Build Your Own (its own page), Complete Packages.
    $filters = ['all' => 'All packages', 'materials' => 'Materials Only', 'build' => 'Build Your Own', 'complete' => 'Complete Packages'];
    $btn = 'inline-flex min-h-11 shrink-0 items-center rounded-[var(--radius-control)] bg-steel-900 px-4 text-sm font-semibold text-white hover:bg-steel-800 disabled:cursor-not-allowed disabled:opacity-50';
    $intro = match ($active) {
        'materials' => 'Includes three main components, the inverter, battery and solar panels only. A highly customizable bundle ideal for professional DIYers and professional installers. We’re offering technical assistance from installation, commissioning up to even troubleshooting of the system.',
        // Matches what the Complete packages list as included; no promises beyond that.
        'complete' => 'Everything done for you: the inverter, battery and solar panels plus mounting, cables and safety devices, with site assessment, system design, installation and commissioning by our team. We’ll also show you how your system works and how to monitor it on the mobile app. The final price is confirmed after the site ocular inspection.',
        default => new \Illuminate\Support\HtmlString('Three ways to go solar with us. <a href="/packages?type=materials" class="font-semibold text-white underline">Materials Only</a>: the inverter, battery and solar panels, for DIYers and installers. <a href="/packages/build-your-own" class="font-semibold text-white underline">Build Your Own</a>: pick every part and see your total as you go. <a href="/packages?type=complete" class="font-semibold text-white underline">Complete packages</a>: supply, installation and commissioning, done for you.'),
    };
@endphp
<x-site-layout title="Solar Packages" path="/packages" :reviews-above-footer="false" description="JFL Solar packages: Materials Only bundles (inverter, battery and solar panels), Build Your Own with a price for every part, and Complete packages with installation. Compare inverter kW, array kWp, battery kWh and prices.">
    <x-page-hero eyebrow="Packages" :title="match ($active) { 'materials' => 'Materials Only', 'complete' => 'Complete packages', default => 'Solar packages' }" :intro="$intro"
        image="/images/packages-vents-1280.jpg" srcset="/images/packages-vents-640.jpg 640w, /images/packages-vents-960.jpg 960w, /images/packages-vents-1280.jpg 1280w"
        imageAlt="Long rows of solar panels installed on a commercial metal roof with roof vents" focus="45% 55%">
        <p class="mt-5 text-xl font-extrabold text-gold-400">Sagot ka namin!</p>
    </x-page-hero>

    <x-container class="pt-10 pb-24">
        <nav aria-label="Package type" class="-mx-1 flex flex-wrap gap-2">
            @foreach ($filters as $key => $label)
                @if ($key === 'build')
                    <a href="/packages/build-your-own" class="inline-flex min-h-11 items-center gap-1.5 rounded-full border border-gold-500 bg-gold-50 px-4 text-sm font-semibold text-steel-900 hover:border-steel-900"><x-icon name="tools" class="size-4" /> {{ $label }}</a>
                @else
                    <a href="{{ $key === 'all' ? '/packages' : '/packages?type='.$key }}" @if ($active === $key) aria-current="page" @endif
                        class="inline-flex min-h-11 items-center rounded-full border border-steel-300 px-4 text-sm font-semibold text-steel-800 hover:border-steel-900 aria-[current=page]:border-steel-900 aria-[current=page]:bg-steel-900 aria-[current=page]:text-white">{{ $label }}</a>
                @endif
            @endforeach
        </nav>
        <p class="mt-4 text-sm text-steel-600" role="status">{{ $shown->count() }} {{ $shown->count() === 1 ? 'package' : 'packages' }}</p>

        <form id="compare-form" action="/packages/compare" method="get" aria-label="Compare packages"></form>
        @if ($shown->count() > 1)
            {{-- Compare controls. In page flow: instructions + submit (works without JS).
                 After the first selection (JS): a compact sticky bar so the action stays reachable. --}}
            <div class="mt-4" data-compare-bar="compare-form" data-max="3">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-[var(--radius-control)] border border-steel-200 bg-steel-50 px-4 py-2.5">
                    <p class="text-sm text-steel-700" data-compare-status>Tick “Compare” on 2 or 3 packages to see them side by side.</p>
                    <button type="submit" form="compare-form" class="{{ $btn }}" data-compare-submit>Compare selected</button>
                </div>
                <div class="fixed inset-x-0 bottom-0 z-30 hidden border-t border-steel-200 bg-white/95 shadow-[0_-4px_16px_rgb(0_0_0/0.06)] backdrop-blur" data-compare-sticky>
                    <div class="mx-auto flex max-w-[76rem] items-center justify-between gap-3 px-4 py-2.5 sm:px-6 lg:px-8">
                        <p class="text-sm font-semibold text-steel-800" role="status" aria-live="polite" data-compare-live></p>
                        <button type="submit" form="compare-form" class="{{ $btn }}" data-compare-submit>Compare</button>
                    </div>
                </div>
            </div>
        @endif
        @if ($shown->isNotEmpty())
            <div class="mt-6 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($shown as $pkg)
                    <x-package-card :pkg="$pkg" heading="h2" compare-form="compare-form" show-image />
                @endforeach
            </div>
        @else
            <div class="mt-6 rounded-[var(--radius-panel)] border border-dashed border-steel-300 p-8 text-center">
                <p class="font-semibold text-steel-900">No packages in this category yet.</p>
                <p class="mt-1 text-steel-600"><a href="/contact#quote" class="font-semibold underline">Request a quotation</a> and we will recommend a system.</p>
            </div>
        @endif
        <p class="mt-8 max-w-3xl text-sm text-steel-600">Prices marked as samples are for demonstration only. Final pricing, delivery charges and installation scheduling are confirmed in a written quotation.</p>
    </x-container>
</x-site-layout>
