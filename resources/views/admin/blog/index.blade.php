@php
    $rows = $posts->map(fn ($p) => [
        'id' => $p->id, 'name' => $p->title, 'status' => $p->status, 'isSample' => $p->is_sample, 'featured' => false,
        'subtitle' => implode(' · ', array_filter([$p->published_at ? 'Published '.\App\Support\Blog::date($p) : 'Not yet published', ($photos[$p->id] ?? 0).' image(s)'])),
    ])->all();
@endphp
<x-admin.layout title="Blog">
    <x-admin.page-title title="Blog" intro="Posts for JF Legaspi’s blog at /blog, linked from the founder section of the About page. Newest published posts show first.">
        <x-slot:action><x-admin.new-button href="/admin/blog/new">New post</x-admin.new-button></x-slot:action>
    </x-admin.page-title>
    <x-admin.record-list entity="blog" :rows="$rows" :archived="$archived" :featurable="false" :can-reorder="false" empty-text="No posts yet. Write your first one with “New post”." />
</x-admin.layout>
