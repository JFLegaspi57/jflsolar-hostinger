@php
    $isNew = $post === null;
@endphp
<x-admin.layout :title="$isNew ? 'New post' : $post->title">
    <x-admin.page-title :title="$isNew ? 'New post' : $post->title" back="/admin/blog" back-label="All posts"
        :intro="$isNew ? '“Save as draft” keeps it hidden while you write; “Save and publish” puts it on the blog straight away. You can add images after the first save.' : null" />

    @unless ($isNew)
        <x-admin.edit-header entity="blog" :record="$post" :name="$post->title" :public-href="'/blog/'.$post->slug">
            @if ($post->published_at)<p class="mt-2 text-sm text-steel-600">Date shown on the post: {{ \App\Support\Blog::date($post) }}</p>@endif
        </x-admin.edit-header>
    @endunless

    <x-admin.form :action="$isNew ? '/admin/blog' : '/admin/blog/'.$post->id" :form="$form" :status="$post?->status" :is-new="$isNew"
        :hidden="$isNew ? [] : ['lock_version' => $post->lock_version]">
        <x-admin.fieldset legend="Post">
            <x-admin.text :form="$form" name="title" label="Title" required maxlength="160" class="sm:col-span-2" />
            <x-admin.text :form="$form" name="slug" label="URL slug" maxlength="80" class="sm:col-span-2" hint="/blog/your-slug — leave blank to create from the title." />
            <x-admin.textarea :form="$form" name="excerpt" label="Short summary" maxlength="300" rows="2" class="sm:col-span-2" hint="One or two sentences shown on the blog list and in search results." />
            <x-admin.textarea :form="$form" name="body" label="Post" required maxlength="20000" rows="16" class="sm:col-span-2"
                hint="Each line becomes its own paragraph. Start a line with “## ” for a section heading and “-” for a bullet point. A short label before a colon (“Tip: …”) is shown in bold." />
        </x-admin.fieldset>
    </x-admin.form>

    @unless ($isNew)
        <div class="mb-28 space-y-8">
            <x-admin.image-manager owner-type="post" :owner="$post" :images="$post->photos" title="Post images" cover-label="cover image" />
        </div>
    @endunless
</x-admin.layout>
