@props(['entity', 'rows', 'archived' => false, 'emptyText', 'featurable' => true, 'canReorder' => true, 'keep' => ''])
{{-- $rows: list of ['id','name','status','isSample','featured','subtitle'] --}}
@php
    $small = 'inline-flex min-h-11 items-center justify-center rounded-[var(--radius-control)] border border-steel-300 bg-white px-3 text-sm font-semibold text-steel-900 hover:border-steel-900 disabled:opacity-40';
    $listUrl = "/admin/{$entity}".(($keep || $archived) ? '?'.implode('&', array_filter([$archived ? 'view=archived' : '', $keep])) : '');
    $editHref = fn ($id) => "/admin/{$entity}/{$id}".(($keep || $archived) ? '?back='.rawurlencode($listUrl) : '');
    $count = count($rows);
@endphp
<x-admin.view-tabs :base="'/admin/'.$entity" :archived="$archived" :keep="$keep" />
@if ($count === 0)
    <p class="rounded-[var(--radius-panel)] border border-dashed border-steel-300 bg-white p-8 text-center text-steel-700">{{ $archived ? 'Nothing archived.' : $emptyText }}</p>
@else
    <ul class="space-y-3">
        @foreach ($rows as $i => $r)
            <li class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-admin.status-pill :status="$r['status']" />
                            @if (! empty($r['isSample']))<x-admin.sample-tag />@endif
                        </div>
                        <h2 class="mt-1.5 text-lg font-bold break-words text-steel-950"><a href="{{ $editHref($r['id']) }}" class="underline-offset-2 hover:underline">{{ $r['name'] }}</a></h2>
                        @if (! empty($r['subtitle']))<p class="text-sm text-steel-600">{{ $r['subtitle'] }}</p>@endif
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5">
                        @if (! $archived && $featurable)
                            @php $featured = ! empty($r['featured']); @endphp
                            <form method="post" action="/admin/{{ $entity }}/{{ $r['id'] }}/featured">
                                @csrf
                                <input type="hidden" name="featured" value="{{ $featured ? 0 : 1 }}">
                                <input type="hidden" name="back" value="{{ request()->fullUrl() }}">
                                <button type="submit" aria-pressed="{{ $featured ? 'true' : 'false' }}" title="{{ $featured ? 'Shown on the home page — click to remove' : 'Click to show on the home page' }}"
                                    class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-[var(--radius-control)] border px-3 text-sm font-semibold {{ $featured ? 'border-gold-500 bg-gold-500 text-steel-950 hover:bg-gold-400' : 'border-steel-300 bg-white text-steel-900 hover:border-steel-900' }}">
                                    <span aria-hidden="true">{{ $featured ? '★' : '☆' }}</span>{{ $featured ? 'Featured' : 'Feature' }}<span class="sr-only"> {{ $r['name'] }}</span>
                                </button>
                            </form>
                        @endif
                        @if (! $archived && $canReorder)
                            <div class="flex gap-1">
                                @foreach (['up' => '↑', 'down' => '↓'] as $dir => $arrow)
                                    <form method="post" action="/admin/{{ $entity }}/{{ $r['id'] }}/move">
                                        @csrf
                                        <input type="hidden" name="direction" value="{{ $dir }}">
                                        <input type="hidden" name="back" value="{{ request()->fullUrl() }}">
                                        <button type="submit" @disabled(($dir === 'up' && $i === 0) || ($dir === 'down' && $i === $count - 1)) class="{{ $small }} w-11 px-0" aria-label="Move {{ $r['name'] }} {{ $dir }}">{{ $arrow }}</button>
                                    </form>
                                @endforeach
                            </div>
                        @endif
                        <a href="{{ $editHref($r['id']) }}" class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] bg-steel-900 px-4 text-sm font-semibold text-white hover:bg-steel-800">Edit<span class="sr-only"> {{ $r['name'] }}</span></a>
                    </div>
                </div>
                <div class="mt-3 border-t border-steel-100 pt-3">
                    <x-admin.status-controls :entity="$entity" :id="$r['id']" :name="$r['name']" :status="$r['status']" />
                </div>
            </li>
        @endforeach
    </ul>
@endif
