@props(['project', 'admin' => false])
@php
    use App\Support\Labels;
    use App\Support\Pricing;
    use App\Support\Site;
    use App\Support\Ui;
    $size = $project->system_size_confirmed ? Pricing::formatCapacity($project->system_size_kwp, 'kWp') : null;
    $videos = \App\Support\VideoEmbed::all($project->video_urls ?? null);
    $others = $project->photos->reject(fn ($p) => $project->cover && $p->id === $project->cover->id)->values();
    $facts = [
        ['Type', Labels::PROJECT_TYPE[$project->project_type], false],
        ['Location', $project->location ?? '—', false],
        ['System size', $size ?? 'To be confirmed', false],
        ['Scope', $project->scope ?? '—', true],
    ];
@endphp
<article aria-labelledby="project-title">
    <x-container class="py-8 sm:py-10">
        <x-breadcrumbs :items="[['href' => '/', 'label' => 'Home'], ['href' => '/projects', 'label' => 'Projects'], ['label' => $project->title]]" />
        @if ($project->is_sample && ! Site::isProduction())
            <p role="note" class="mt-6 rounded-[var(--radius-control)] border border-dashed border-steel-400 bg-steel-50 px-4 py-3 text-steel-800"><strong>Sample project.</strong> This entry is not a completed JFL Solar installation. It demonstrates how projects will be presented.</p>
        @endif
        <h1 id="project-title" class="mt-6 max-w-3xl text-3xl font-extrabold text-steel-950 sm:text-4xl">{{ $project->title }}</h1>
        <dl class="mt-5 grid max-w-3xl grid-cols-2 gap-4 border-y border-steel-200 py-4 sm:grid-cols-4">
            @foreach ($facts as [$label, $value, $wide])
                <div class="{{ $wide ? 'col-span-2 sm:col-span-1' : '' }}">
                    <dt class="text-xs font-semibold tracking-wide text-steel-600 uppercase">{{ $label }}</dt>
                    <dd class="mt-0.5 font-semibold text-steel-900">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        <figure class="mt-8 overflow-hidden rounded-[var(--radius-panel)] border border-steel-200">
            <div class="aspect-[16/9] bg-steel-100">
                @if ($project->cover)
                    <x-media-image :asset="$project->cover" :admin="$admin" sizes="(min-width: 1216px) 1152px, 100vw" priority />
                @else
                    <x-placeholder-image :label="$project->is_sample ? 'Sample — placeholder image' : 'Project photo pending'" />
                @endif
            </div>
            @if ($project->cover?->caption)<figcaption class="px-4 py-2 text-sm text-steel-600">{{ $project->cover->caption }}</figcaption>@endif
        </figure>

        @if ($videos)
            <section aria-labelledby="videos-title" class="mt-12">
                <h2 id="videos-title" class="text-xl font-bold text-steel-950">{{ count($videos) === 1 ? 'Video' : 'Videos' }}</h2>
                <ul class="mt-4 grid gap-6 {{ count($videos) > 1 ? 'lg:grid-cols-2' : '' }}">
                    @foreach ($videos as $i => $v)
                        <li>
                            <div class="overflow-hidden rounded-[var(--radius-panel)] border border-steel-200 bg-steel-950"
                                style="aspect-ratio: {{ $v['vertical'] ? '9 / 16' : '16 / 9' }}; {{ $v['vertical'] ? 'max-width: 22rem; margin-inline: auto;' : '' }}">
                                <iframe src="{{ $v['src'] }}" title="{{ $project->title }} — video {{ $i + 1 }} ({{ $v['service'] }})" loading="lazy"
                                    allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share; fullscreen" allowfullscreen
                                    referrerpolicy="strict-origin-when-cross-origin" style="width: 100%; height: 100%; border: 0"></iframe>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <div class="mt-10 grid gap-10 lg:grid-cols-[1fr_20rem]">
            <x-paragraphs :text="$project->description ?: $project->summary" class="max-w-2xl text-lg text-steel-700" />
            <aside class="rounded-[var(--radius-panel)] bg-steel-50 p-6">
                <h2 class="text-lg font-bold text-steel-950">Planning something similar?</h2>
                <p class="mt-2 text-steel-600">Tell us about your site and we will recommend a system.</p>
                <a href="{{ Site::QUOTE_HREF }}" class="{{ Ui::button('primary', 'md', 'mt-4 w-full') }}">Request a Quote</a>
            </aside>
        </div>

        @if ($others->isNotEmpty())
            <section aria-labelledby="gallery-title" class="mt-12">
                <h2 id="gallery-title" class="text-xl font-bold text-steel-950">Photos</h2>
                <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($others as $photo)
                        <li>
                            <figure class="overflow-hidden rounded-[var(--radius-panel)] border border-steel-200">
                                <div class="aspect-[4/3] bg-steel-100"><x-media-image :asset="$photo" :admin="$admin" sizes="(min-width: 1024px) 380px, (min-width: 640px) 50vw, 100vw" /></div>
                                @if ($photo->caption)<figcaption class="px-3 py-2 text-sm text-steel-600">{{ $photo->caption }}</figcaption>@endif
                            </figure>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </x-container>
</article>
