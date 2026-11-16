@php
    $tagalog = $tagalog ?? false;
    $tl = $tl ?? null;
    $enPath = '/projects/'.$project->slug;
    $title = $tagalog ? $tl['title'] : $project->title;
    $summary = $tagalog ? $tl['summary'] : $project->summary;
@endphp
<x-site-layout :title="$title" :path="$tagalog ? $enPath.'/tagalog' : $enPath" :description="$summary ?: $title" :noindex="$project->is_sample" :reviews-above-footer="false" :lang="$tagalog ? 'fil' : 'en-PH'">
    @if ($tl)
        <x-slot:head>@include('partials.guide-hreflang', ['en' => $enPath])</x-slot:head>
    @endif
    <x-detail.project :project="$project" :tagalog="$tagalog" :tl="$tl" />
</x-site-layout>
