@php
    $isNew = $project === null;
@endphp
<x-admin.layout :title="$isNew ? 'New project' : $project->title">
    <x-admin.page-title :title="$isNew ? 'New project' : $project->title" back="/admin/projects" back-label="All projects"
        :intro="$isNew ? '“Save as draft” keeps it hidden while you work; “Save and publish” puts it on the website straight away. You can add photos after the first save.' : null" />

    @unless ($isNew)
        <x-admin.edit-header entity="projects" :record="$project" :name="$project->title" :public-href="'/projects/'.$project->slug" :preview-href="'/admin/preview/projects/'.$project->id" />
    @endunless

    <x-admin.form :action="$isNew ? '/admin/projects' : '/admin/projects/'.$project->id" :form="$form" :status="$project?->status" :is-new="$isNew"
        :hidden="$isNew ? [] : ['lock_version' => $project->lock_version]">
        <x-admin.fieldset legend="Project">
            <x-admin.text :form="$form" name="title" label="Title" required maxlength="140" class="sm:col-span-2" />
            <x-admin.text :form="$form" name="slug" label="URL slug" maxlength="80" hint="/projects/your-slug — leave blank to create from the title." />
            <x-admin.select :form="$form" name="project_type" label="Project type" required :options="\App\Support\Labels::PROJECT_TYPE" />
            <x-admin.text :form="$form" name="location" label="General location" maxlength="120" hint="City or province only — never a client's full address." />
            <x-admin.text :form="$form" name="system_size_kwp" label="System size (kWp)" inputmode="decimal" />
            <x-admin.checkbox :form="$form" name="system_size_confirmed" label="System size is confirmed" hint="Unconfirmed sizes are not shown publicly." />
            <x-admin.text :form="$form" name="scope" label="Scope of work" maxlength="600" class="sm:col-span-2" hint="e.g. “Assessment, supply, installation and commissioning”." />
            <x-admin.textarea :form="$form" name="summary" label="Short summary" maxlength="300" rows="2" class="sm:col-span-2" />
            <x-admin.textarea :form="$form" name="description" label="Description" maxlength="6000" rows="5" class="sm:col-span-2" />
        </x-admin.fieldset>
        <x-admin.fieldset legend="Display and internal">
            <x-admin.checkbox :form="$form" name="featured" label="Show on the home page" />
            <x-admin.checkbox :form="$form" name="is_sample" label="Sample / demonstration project" hint="Sample projects are labelled in preview and hidden on the live site. Untick only for a real, approved JFL Solar project." />
            <x-admin.textarea :form="$form" name="admin_notes" label="Internal notes (never shown publicly)" maxlength="2000" rows="3" class="sm:col-span-2" />
        </x-admin.fieldset>
    </x-admin.form>

    @unless ($isNew)
        <div class="mb-28 space-y-8">
            <details class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4">
                <summary class="inline-flex min-h-11 cursor-pointer items-center gap-2 font-semibold text-steel-900"><span aria-hidden="true">▸</span> Show saved card preview</summary>
                <div class="mt-3 max-w-sm"><x-project-card :project="$project" admin /></div>
            </details>
            <x-admin.image-manager owner-type="project" :owner="$project" :images="$project->photos" title="Project photos" cover-label="cover photo" />
        </div>
    @endunless
</x-admin.layout>
