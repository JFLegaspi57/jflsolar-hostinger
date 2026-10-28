@php
    use App\Support\Labels;
    $tabs = ['new' => 'New', 'handled' => 'Handled', 'archived' => 'Archived'];
    $tab = 'inline-flex min-h-11 items-center gap-1.5 rounded-full border border-steel-300 px-4 text-sm font-semibold text-steel-800 aria-[current=page]:border-steel-900 aria-[current=page]:bg-steel-900 aria-[current=page]:text-white';
@endphp
<x-admin.layout title="Inquiries">
    <x-admin.page-title title="Inquiries" intro="Quote requests sent through the website. Reply by phone or email, then mark them as handled." />
    <nav aria-label="Filter inquiries" class="mb-4 flex flex-wrap gap-2">
        @foreach ($tabs as $key => $label)
            <a href="/admin/inquiries{{ $key === 'new' ? '' : '?view='.$key }}" @if ($view === $key) aria-current="page" @endif class="{{ $tab }}">{{ $label }} <span class="tabular">({{ $counts[$key] ?? 0 }})</span></a>
        @endforeach
    </nav>
    @if ($inquiries->isEmpty())
        <p class="rounded-[var(--radius-panel)] border border-dashed border-steel-300 bg-white p-8 text-center text-steel-700">{{ $view === 'new' ? 'No new inquiries.' : 'Nothing here.' }}</p>
    @else
        <ul class="divide-y divide-steel-200 rounded-[var(--radius-panel)] border border-steel-200 bg-white">
            @foreach ($inquiries as $i)
                <li class="p-4">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <a href="/admin/inquiries/{{ $i->id }}" class="text-lg font-bold text-steel-950 underline-offset-2 hover:underline">{{ $i->name }}</a>
                        <span class="text-sm text-steel-600 tabular">{{ $i->created_at->timezone('Asia/Manila')->format('j M Y, g:i A') }}</span>
                    </div>
                    <p class="text-sm text-steel-700">{{ Labels::INQUIRY_TYPES[$i->inquiry_type] ?? $i->inquiry_type }}@if ($i->item_label) · {{ $i->item_label }}@endif @if ($i->location) · {{ $i->location }}@endif</p>
                    <p class="mt-1 line-clamp-2 text-steel-700">{{ $i->message }}</p>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $inquiries->links() }}</div>
    @endif
</x-admin.layout>
