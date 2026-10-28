@php
    $rows = $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'status' => $c->status, 'subtitle' => '/'.$c->slug.' · '.($counts[$c->id] ?? 0).' product(s)'])->all();
@endphp
<x-admin.layout title="Categories">
    <x-admin.page-title title="Product categories" intro="Categories group products and drive the filters on the Products page. A category with products cannot be deleted.">
        <x-slot:action><x-admin.new-button href="/admin/categories/new">New category</x-admin.new-button></x-slot:action>
    </x-admin.page-title>
    <x-admin.record-list entity="categories" :rows="$rows" :archived="$archived" empty-text="No categories yet." :featurable="false" />
</x-admin.layout>
