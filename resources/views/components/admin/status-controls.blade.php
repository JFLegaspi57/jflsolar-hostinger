@props(['entity', 'id', 'name', 'status', 'returnTo' => 'list'])
@php
    $small = 'inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] border border-steel-300 bg-white px-3 text-sm font-semibold text-steel-900 hover:border-steel-900';
    $statusUrl = "/admin/{$entity}/{$id}/status";
    $back = ['return' => $returnTo, 'back' => request()->fullUrl()];
@endphp
{{-- Publish / unpublish / archive / restore / delete for one record. --}}
<div class="flex flex-wrap gap-1.5">
    @if ($status === 'draft')
        <form method="post" action="{{ $statusUrl }}">
            @csrf
            <input type="hidden" name="status" value="published">
            @foreach ($back as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] border border-gold-500 bg-gold-500 px-3 text-sm font-semibold text-steel-950 hover:bg-gold-400">Publish<span class="sr-only"> {{ $name }}</span></button>
        </form>
    @endif
    @if ($status === 'published')
        <x-admin.confirm tone="caution" :action="$statusUrl" :fields="['status' => 'draft'] + $back" :title="'Unpublish “'.$name.'”?'" confirm-label="Unpublish" label="Unpublish" :sr-label="$name">
            <p>It disappears from the public website straight away and becomes a draft you can keep editing. Publish it again at any time. (Photos already viewed may stay cached for up to 5 minutes.)</p>
        </x-admin.confirm>
    @endif
    @if ($status !== 'archived')
        <x-admin.confirm tone="caution" :action="$statusUrl" :fields="['status' => 'archived'] + $back" :title="'Archive “'.$name.'”?'" confirm-label="Archive" label="Archive" :sr-label="$name">
            <p>Use this for items you no longer offer. It is hidden from the website and moved to the <strong>Archived</strong> tab, where you can restore it as a draft or delete it permanently.</p>
        </x-admin.confirm>
    @else
        <form method="post" action="{{ $statusUrl }}">
            @csrf
            <input type="hidden" name="status" value="draft">
            @foreach ($back as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            <button type="submit" class="{{ $small }}">Restore as draft<span class="sr-only"> {{ $name }}</span></button>
        </form>
        <x-admin.confirm :action="'/admin/'.$entity.'/'.$id.'/delete'" :title="'Delete “'.$name.'” permanently?'" confirm-label="Delete permanently" label="Delete permanently" :sr-label="$name">
            <p>This removes the item and all of its images and files. It cannot be undone. Archived items stay hidden without being deleted.</p>
        </x-admin.confirm>
    @endif
</div>
