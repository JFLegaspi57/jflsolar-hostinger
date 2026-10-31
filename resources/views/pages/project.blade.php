<x-site-layout :title="$project->title" :path="'/projects/'.$project->slug" :description="$project->summary ?: $project->title" :noindex="$project->is_sample" :reviews-above-footer="false">
    <x-detail.project :project="$project" />
</x-site-layout>
