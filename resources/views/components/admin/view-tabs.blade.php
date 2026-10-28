@props(['base', 'archived' => false, 'keep' => ''])
@php
    $tab = 'inline-flex min-h-11 items-center rounded-full border border-steel-300 px-4 text-sm font-semibold text-steel-800 aria-[current=page]:border-steel-900 aria-[current=page]:bg-steel-900 aria-[current=page]:text-white';
@endphp
<nav aria-label="Filter by status" class="mb-4 flex gap-2">
    <a href="{{ $keep ? $base.'?'.$keep : $base }}" @unless ($archived) aria-current="page" @endunless class="{{ $tab }}">Drafts and published</a>
    <a href="{{ $base }}?view=archived{{ $keep ? '&'.$keep : '' }}" @if ($archived) aria-current="page" @endif class="{{ $tab }}">Archived</a>
</nav>
