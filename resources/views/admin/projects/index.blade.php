@php
    use App\Support\Labels;
    $rows = $projects->map(fn ($p) => [
        'id' => $p->id, 'name' => $p->title, 'status' => $p->status, 'isSample' => $p->is_sample, 'featured' => $p->featured,
        'subtitle' => implode(' · ', array_filter([Labels::PROJECT_TYPE[$p->project_type], $p->location, ($photos[$p->id] ?? 0).' photo(s)'])),
    ])->all();
@endphp
<x-admin.layout title="Projects">
    <x-admin.page-title title="Projects" intro="Completed JFL Solar installations. Only publish real projects with photos you have permission to use.">
        <x-slot:action><x-admin.new-button href="/admin/projects/new">New project</x-admin.new-button></x-slot:action>
    </x-admin.page-title>
    <x-admin.record-list entity="projects" :rows="$rows" :archived="$archived" empty-text="No projects yet." />
</x-admin.layout>
