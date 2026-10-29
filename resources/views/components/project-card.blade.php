@props(['project', 'heading' => 'h3', 'admin' => false])
@php
    use App\Support\Labels;
    use App\Support\Pricing;
    use App\Support\Site;
    $size = $project->system_size_confirmed ? Pricing::formatCapacity($project->system_size_kwp, 'kWp') : null;
@endphp
<article class="group relative overflow-hidden rounded-[var(--radius-panel)] border border-steel-200 bg-white">
    <div class="relative aspect-[3/2] overflow-hidden bg-steel-100">
        @if (\App\Support\VideoEmbed::all($project->video_urls ?? null))
            <span class="absolute top-3 left-3 z-10 inline-flex items-center gap-1.5 rounded-full bg-steel-950/80 px-2.5 py-1 text-xs font-semibold text-white">
                <svg viewBox="0 0 24 24" aria-hidden="true" class="size-3.5" fill="currentColor"><path d="M8 5.5v13l11-6.5z" /></svg> Video
            </span>
        @endif
        @if ($project->cover)
            <x-media-image :asset="$project->cover" :admin="$admin" sizes="(min-width: 1024px) 380px, (min-width: 640px) 50vw, 100vw" class="transition-transform duration-300 group-hover:scale-[1.02]" />
        @else
            <x-placeholder-image :label="$project->is_sample ? 'Sample — placeholder image' : 'Project photo pending'" />
        @endif
    </div>
    <div class="p-5">
        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold tracking-wide text-steel-600 uppercase">
            <span>{{ Labels::PROJECT_TYPE[$project->project_type] }}</span>
            @if ($project->location)<span aria-hidden="true">·</span><span class="normal-case tracking-normal">{{ $project->location }}</span>@endif
            @if ($size)<span aria-hidden="true">·</span><span class="tabular">{{ $size }}</span>@endif
        </div>
        <{{ $heading }} class="mt-2 text-lg font-bold text-steel-950">
            <a href="/projects/{{ $project->slug }}" class="after:absolute after:inset-0 hover:underline">{{ $project->title }}</a>
        </{{ $heading }}>
        <p class="mt-1.5 text-[0.95rem] text-steel-600">{{ $project->summary }}</p>
        @if ($project->is_sample && ! Site::isProduction())
            <div class="mt-3"><x-sample-badge label="Sample — not a JFL installation" /></div>
        @endif
    </div>
</article>
