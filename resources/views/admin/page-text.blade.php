@php
    use App\Http\Controllers\Admin\PageTextController;
    use App\Support\PageTextData;
    $tab = 'inline-flex min-h-11 items-center rounded-full border border-steel-300 px-4 text-sm font-semibold text-steel-800 aria-[current=page]:border-steel-900 aria-[current=page]:bg-steel-900 aria-[current=page]:text-white';
@endphp
<x-admin.layout title="Page text">
    <x-admin.page-title title="Page text" intro="Headings and wording on the Home, Solar Services and About pages. Changes appear on the website as soon as you save. Plain text only." />

    <nav aria-label="Choose a page" class="mb-4 flex flex-wrap gap-2">
        @foreach (PageTextData::PAGES as $slug => $p)
            <a href="/admin/text/{{ $slug }}" @if ($slug === $page['slug']) aria-current="page" @endif class="{{ $tab }}">{{ $p['name'] }}</a>
        @endforeach
    </nav>
    <p class="mb-6 text-sm text-steel-700">
        @if ($page['note']){{ $page['note'] }} @endif
        <a href="{{ $page['path'] }}" target="_blank" rel="noopener" class="font-semibold text-steel-900 underline">View the {{ $page['name'] }} page<span class="sr-only"> (opens in a new tab)</span></a>
    </p>

    @if ($missing)
        <p role="alert" class="rounded-[var(--radius-control)] border border-[#f0d39a] bg-warn-50 px-4 py-3 text-warn-800">Page text editing is not switched on yet: the database update for it (upgrade-2026-10-28.sql) has not been imported. The website shows the original wording meanwhile.</p>
    @else
        <x-admin.form :action="'/admin/text/'.$page['slug']" :form="$form" :publishable="false" save-label="Save page text" :hidden="['version' => $version]">
            @foreach ($page['groups'] as $g)
                <x-admin.fieldset :legend="$g['title']" :description="$g['note']">
                    @foreach ($g['fields'] as $f)
                        @php
                            $name = PageTextController::fieldName($f['key']);
                            $original = PageTextData::DEFAULTS[$f['key']];
                            $current = $form->value($name);
                            $edited = $current !== '' && $current !== $original;
                            $hint = ($f['kind'] === 'list' ? 'One point per line. ' : '')
                                .($edited ? 'Changed — original: “'.str_replace("\n", ' · ', $original).'”. Clear the box to restore it.' : 'Clear the box to restore the original wording.');
                        @endphp
                        @if ($f['kind'] === 'line')
                            <x-admin.text :form="$form" :name="$name" :label="$f['label']" :hint="$hint" :maxlength="PageTextData::FIELD_MAX['line']" class="sm:col-span-2" />
                        @else
                            <x-admin.textarea :form="$form" :name="$name" :label="$f['label']" :hint="$hint" :maxlength="PageTextData::FIELD_MAX[$f['kind']]" :rows="$f['kind'] === 'list' ? 4 : 3" class="sm:col-span-2" />
                        @endif
                    @endforeach
                </x-admin.fieldset>
            @endforeach
        </x-admin.form>
    @endif
</x-admin.layout>
