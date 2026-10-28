@props(['entity', 'record', 'name', 'publicHref' => null, 'previewHref' => null])
{{-- Status summary + lifecycle controls shown above an edit form. --}}
<section aria-label="Status" class="mb-8 rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4">
    <div class="flex flex-wrap items-center gap-2">
        <x-admin.status-pill :status="$record->status" />
        @if ($record->is_sample ?? false)<x-admin.sample-tag />@endif
        @if ($previewHref)<a href="{{ $previewHref }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-steel-900 underline">Preview full page</a>@endif
        @if ($publicHref && $record->status === 'published')
            <a href="{{ $publicHref }}" target="_blank" rel="noopener" class="ml-auto inline-flex min-h-11 items-center text-sm font-semibold text-steel-900 underline">View live page<span class="sr-only"> (opens in a new tab)</span></a>
        @endif
    </div>
    {{ $slot }}
    <div class="mt-3 border-t border-steel-100 pt-3">
        <x-admin.status-controls :entity="$entity" :id="$record->getKey()" :name="$name" :status="$record->status" return-to="edit" />
    </div>
</section>
