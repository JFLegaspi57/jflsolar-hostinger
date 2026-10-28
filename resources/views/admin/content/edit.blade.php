@php
    use App\Admin\Blocks;
    $isNew = $block === null;
@endphp
<x-admin.layout :title="$isNew ? 'New block' : $block->title">
    <x-admin.page-title :title="$isNew ? 'New content block' : $block->title" back="/admin/content" back-label="Site content" />
    @if (! $isNew && $block->claim_status !== 'confirmed' && $block->review_note)
        <p class="mb-6 rounded-[var(--radius-control)] border border-[#f0d39a] bg-warn-50 px-4 py-3 text-sm text-warn-800"><strong>Needs confirmation:</strong> {{ $block->review_note }}</p>
    @endif

    <x-admin.form :action="$isNew ? '/admin/content' : '/admin/content/'.rawurlencode($block->key)" :form="$form" :is-new="$isNew" :publishable="false" :save-label="$isNew ? 'Create block' : 'Save'"
        :hidden="$isNew ? [] : ['lock_version' => $block->lock_version]">
        @unless ($isNew)
            <p class="text-sm text-steel-700"><span class="font-semibold text-steel-950">Where this appears:</span> {{ Blocks::placement($block->key, $block->section) }}</p>
        @endunless
        <x-admin.fieldset legend="Content">
            <x-admin.text :form="$form" name="title" label="Title / heading" required maxlength="160" class="sm:col-span-2" />
            <x-admin.text :form="$form" name="subtitle" label="Subtitle" maxlength="200" hint="Optional — e.g. a person’s role, or a short label above the heading." class="sm:col-span-2" />
            <x-admin.textarea :form="$form" name="body" label="Text" maxlength="6000" rows="8" class="sm:col-span-2" hint="Plain text. Leave a blank line between paragraphs." />
        </x-admin.fieldset>
        <x-admin.fieldset legend="Accuracy and visibility">
            <x-admin.checkbox :form="$form" name="confirmed" label="The owner has confirmed this text is accurate"
                hint="Tick only after the facts and promises in this text are approved (founding date, credentials, warranty and support promises). Until then it shows on the preview site only, with an “Awaiting confirmation” marker, and never on the live site." />
            <x-admin.checkbox :form="$form" name="published" label="Show on the website" hint="Untick to hide this block everywhere. Unconfirmed text still only appears on the preview site." />
            <x-admin.textarea :form="$form" name="review_note" label="Review note (staff only)" maxlength="1000" rows="3" class="sm:col-span-2" hint="What needs checking, and who approved it." />
        </x-admin.fieldset>
        @if ($isNew)
            <x-admin.fieldset legend="Placement" description="New blocks appear after the standard ones in the chosen section.">
                <x-admin.select :form="$form" name="section" label="Show in" required :options="Blocks::SECTIONS" />
                <x-admin.text :form="$form" name="sort_order" label="Order" inputmode="numeric" hint="Lower numbers appear first." />
            </x-admin.fieldset>
        @endif
        <details class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4">
            <summary class="inline-flex min-h-11 cursor-pointer items-center gap-2 font-semibold text-steel-900"><span aria-hidden="true">▸</span> Advanced (developer settings)</summary>
            <div class="mt-4 grid gap-5 sm:grid-cols-2">
                @if ($isNew)
                    <x-admin.text :form="$form" name="key" label="Block key" maxlength="60" hint="Optional. Leave blank to create one automatically." class="sm:col-span-2" />
                @else
                    <p class="text-sm text-steel-700 sm:col-span-2">Internal key: <code>{{ $block->key }}</code> (fixed — it decides where the block appears).</p>
                    <x-admin.text :form="$form" name="sort_order" label="Order" inputmode="numeric" hint="Lower numbers appear first within the section." />
                @endif
            </div>
        </details>
    </x-admin.form>

    @unless ($isNew)
        <div class="mb-28 space-y-8">
            <x-admin.image-manager owner-type="block" :owner="$block" :images="$images" :title="$block->key === 'about.founder' ? 'Founder photo' : 'Image (optional)'"
                :guidance="$block->key === 'about.founder' ? 'Use a recent portrait the founder has approved for publication.' : 'Optional image shown with this block. Only use images you have permission to publish.'" />
            <section aria-labelledby="danger-title" class="rounded-[var(--radius-panel)] border border-[#f3c3be] bg-white p-5">
                <h2 id="danger-title" class="text-lg font-bold text-danger-700">Delete this block</h2>
                <p class="mt-1 text-sm text-steel-700">Permanently removes the text and any image. To hide it for now, untick “Show on the website” above instead.@if (Blocks::isStandard($block->key)) This is a standard block: deleting it removes that part of the page.@endif</p>
                <div class="mt-3">
                    <x-admin.confirm :action="'/admin/content/'.rawurlencode($block->key).'/delete'" :title="'Delete “'.$block->title.'”?'" confirm-label="Delete block" label="Delete block">
                        <p>The text and any images in this block are permanently removed. This cannot be undone.</p>
                    </x-admin.confirm>
                </div>
            </section>
        </div>
    @endunless
</x-admin.layout>
