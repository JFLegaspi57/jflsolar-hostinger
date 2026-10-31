@php $tab = 'inline-flex min-h-11 items-center gap-1.5 rounded-full border border-steel-300 px-4 text-sm font-semibold text-steel-800 aria-[current=page]:border-steel-900 aria-[current=page]:bg-steel-900 aria-[current=page]:text-white'; @endphp
<x-admin.layout title="Subscribers">
    <x-admin.page-title title="Subscribers" intro="People who asked for JFL Solar promos and news (footer form, account sign-up or account page). Download the list and send your emails from your usual email or newsletter tool. Include each person’s unsubscribe link (it is in the download)." />
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <nav aria-label="Filter subscribers" class="flex flex-wrap gap-2">
            <a href="/admin/subscribers" @if ($show === 'active') aria-current="page" @endif class="{{ $tab }}">Subscribed <span class="tabular">({{ $counts['active'] }})</span></a>
            <a href="/admin/subscribers?show=left" @if ($show === 'left') aria-current="page" @endif class="{{ $tab }}">Unsubscribed <span class="tabular">({{ $counts['left'] }})</span></a>
        </nav>
        <a href="/admin/subscribers/export" class="{{ \App\Support\Ui::button('primary') }}">Download list (CSV)</a>
    </div>
    @if (! $rows || $rows->isEmpty())
        <p class="rounded-[var(--radius-panel)] border border-dashed border-steel-300 bg-white p-8 text-center text-steel-700">{{ $show === 'active' ? 'No subscribers yet.' : 'No one has unsubscribed.' }}</p>
    @else
        <ul class="divide-y divide-steel-200 rounded-[var(--radius-panel)] border border-steel-200 bg-white">
            @foreach ($rows as $r)
                <li class="flex flex-wrap items-baseline justify-between gap-2 p-4">
                    <span class="font-semibold text-steel-950 break-all">{{ $r->email }}@if ($r->name) <span class="font-normal text-steel-700">· {{ $r->name }}</span>@endif</span>
                    <span class="text-sm text-steel-600">{{ \App\Support\Newsletter::SOURCES[$r->source] ?? $r->source }} · {{ \Illuminate\Support\Carbon::parse($show === 'active' ? $r->subscribed_at : $r->unsubscribed_at)->timezone('Asia/Manila')->format('j M Y') }}</span>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $rows->links() }}</div>
    @endif
</x-admin.layout>
