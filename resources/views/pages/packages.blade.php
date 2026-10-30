@php
    $filters = ['all' => 'All packages', 'installed' => 'Installed systems', 'equipment' => 'Equipment only'];
    $btn = 'inline-flex min-h-11 shrink-0 items-center rounded-[var(--radius-control)] bg-steel-900 px-4 text-sm font-semibold text-white hover:bg-steel-800 disabled:cursor-not-allowed disabled:opacity-50';
    $intro = new \Illuminate\Support\HtmlString('<strong class="font-semibold text-white">Installed systems</strong> include assessment, installation and commissioning. <strong class="font-semibold text-white">Equipment-only bundles</strong> are supply only — installation is not included.');
@endphp
<x-site-layout title="Solar Packages" path="/packages" :reviews-above-footer="false" description="Installed solar systems and equipment-only bundles with inverter kW, array kWp and battery kWh, inclusions, exclusions and pricing basis. Compare packages and ask JFL Solar.">
    <x-page-hero eyebrow="Packages" title="Solar packages" :intro="$intro"
        image="/images/packages-vents-1280.jpg" srcset="/images/packages-vents-640.jpg 640w, /images/packages-vents-960.jpg 960w, /images/packages-vents-1280.jpg 1280w"
        imageAlt="Long rows of solar panels installed on a commercial metal roof with roof vents" focus="45% 55%" />

    <x-container class="pt-10 pb-24">
        <nav aria-label="Package type" class="-mx-1 flex flex-wrap gap-2">
            @foreach ($filters as $key => $label)
                <a href="{{ $key === 'all' ? '/packages' : '/packages?type='.$key }}" @if ($active === $key) aria-current="page" @endif
                    class="inline-flex min-h-11 items-center rounded-full border border-steel-300 px-4 text-sm font-semibold text-steel-800 hover:border-steel-900 aria-[current=page]:border-steel-900 aria-[current=page]:bg-steel-900 aria-[current=page]:text-white">{{ $label }}</a>
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
