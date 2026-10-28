<x-site-layout title="Projects" path="/projects" description="Solar PV projects by JFL Solar for residential, commercial and industrial clients.">
    <x-page-header eyebrow="Projects" title="Project gallery" intro="Residential, commercial and industrial solar work." />
    <x-container class="py-10 sm:py-14">
        @if ($projects->contains('is_sample', true) && ! \App\Support\Site::isProduction())
            <p role="note" class="mb-8 rounded-[var(--radius-control)] border border-dashed border-steel-400 bg-steel-50 px-4 py-3 text-steel-800">
                <strong>Sample projects.</strong> The entries below demonstrate the gallery layout during the proposal stage. They are not completed JFL Solar installations and will be replaced with approved projects before launch.
            </p>
        @endif
        @if ($projects->isNotEmpty())
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($projects as $p)
                    <x-project-card :project="$p" heading="h2" />
                @endforeach
            </div>
        @else
            <div class="rounded-[var(--radius-panel)] border border-dashed border-steel-300 p-10 text-center">
                <p class="text-lg font-semibold text-steel-900">Project photos are on the way.</p>
                <p class="mt-2 text-steel-600">Ask us about our work — <a href="/contact" class="font-semibold underline">get in touch</a>.</p>
            </div>
        @endif
    </x-container>
</x-site-layout>
