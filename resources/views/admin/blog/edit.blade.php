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
            <x-admin.text :form="$form" name="published_on" label="Date shown on the post" type="date" hint="Leave blank to use the day you first publish it. Set an earlier date for an older story." />
            <x-admin.text :form="$form" name="date_label" label="Show the date as (optional)" maxlength="60" hint="For an event over several days, e.g. 10–14 November 2026. Leave blank to show the date above." />
            <x-admin.textarea :form="$form" name="excerpt" label="Short summary" maxlength="300" rows="2" class="sm:col-span-2" hint="One or two sentences shown on the blog list and in search results." />
            <x-admin.textarea :form="$form" name="body" label="Post" required maxlength="20000" rows="16" class="sm:col-span-2"
                hint="Each line becomes its own paragraph. Start a line with “## ” for a section heading and “-” for a bullet point. A short label before a colon (“Tip: …”) is shown in bold. For a link, write [link text](https://…)." />
        </x-admin.fieldset>
        <x-admin.fieldset legend="Filipino version (optional)">
            <p class="text-sm text-steel-600 sm:col-span-2">Shown at /blog/{{ $post?->slug ?: 'your-slug' }}/tagalog, with a “Basahin sa Filipino” link on the English post. Reactions and comments are shared by both versions. Leave the Filipino post blank to use the ready-made translation, if there is one, or to have no Filipino version.</p>
            <x-admin.text :form="$form" name="title_tl" label="Filipino title" maxlength="160" class="sm:col-span-2" />
            <x-admin.textarea :form="$form" name="excerpt_tl" label="Filipino summary" maxlength="300" rows="2" class="sm:col-span-2" />
            <x-admin.textarea :form="$form" name="body_tl" label="Filipino post" maxlength="20000" rows="16" class="sm:col-span-2" hint="Same writing rules as the English post." />
        </x-admin.fieldset>
    </x-admin.form>

    @unless ($isNew)
        <div class="mb-28 space-y-8">
            <x-admin.image-manager owner-type="post" :owner="$post" :images="$post->photos" title="Post images" cover-label="cover image" />
        </div>
    @endunless
</x-admin.layout>
