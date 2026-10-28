@php
    $isNew = $category === null;
@endphp
<x-admin.layout :title="$isNew ? 'New category' : $category->name">
    <x-admin.page-title :title="$isNew ? 'New category' : $category->name" back="/admin/categories" back-label="All categories" />
    @unless ($isNew)
        <x-admin.edit-header entity="categories" :record="$category" :name="$category->name" :public-href="'/products?category='.$category->slug" />
    @endunless
    <x-admin.form :action="$isNew ? '/admin/categories' : '/admin/categories/'.$category->id" :form="$form" :status="$category?->status" :is-new="$isNew"
        :hidden="$isNew ? [] : ['lock_version' => $category->lock_version]">
        <x-admin.fieldset legend="Category">
            <x-admin.text :form="$form" name="name" label="Name" required maxlength="80" />
            <x-admin.text :form="$form" name="slug" label="URL slug" maxlength="80" hint="Used in /products?category=slug" />
            <x-admin.textarea :form="$form" name="description" label="Description" maxlength="400" rows="2" class="sm:col-span-2" />
        </x-admin.fieldset>
    </x-admin.form>
</x-admin.layout>
