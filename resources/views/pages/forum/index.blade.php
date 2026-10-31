@php
    use App\Support\Forum;
    use App\Support\Ui;
    $chip = 'inline-flex min-h-10 items-center rounded-full border border-steel-300 px-3.5 text-sm font-semibold text-steel-800 hover:border-steel-900 aria-[current=page]:border-steel-900 aria-[current=page]:bg-steel-900 aria-[current=page]:text-white';
    $title = $category ? Forum::CATEGORIES[$category].' — Solar forum' : 'Solar forum';
@endphp
<x-site-layout :title="$title" :path="'/forum'.($category ? '?category='.$category : '')" :noindex="$q !== '' || ($topics && $topics->currentPage() > 1)" description="Ask questions about solar panels, inverters, batteries and installation. Answers from the community and the JFL Solar team.">
    <x-page-header eyebrow="Community" title="Solar forum" intro="Ask about solar panels, inverters, batteries, wiring and net metering. Other owners and the JFL Solar team answer." />
    <x-container class="py-10 sm:py-14">
        @if (session('forum_status'))<p role="status" class="mb-6 rounded-[var(--radius-control)] border border-[#b7e2c6] bg-ok-50 p-4 font-semibold text-ok-700">{{ session('forum_status') }}</p>@endif
        <div class="flex flex-wrap items-center justify-between gap-4">
            <form method="get" action="/forum" class="flex w-full max-w-md gap-2">
                @if ($category)<input type="hidden" name="category" value="{{ $category }}">@endif
                <label for="f-q" class="sr-only">Search topics</label>
                <input id="f-q" name="q" type="search" value="{{ $q }}" placeholder="Search questions" class="min-h-12 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3.5 text-base text-steel-950 placeholder:text-steel-600">
                <button type="submit" class="{{ Ui::button('secondary') }}">Search</button>
            </form>
            <a href="/forum/new{{ $category ? '?category='.$category : '' }}" class="{{ Ui::button('primary', 'lg') }}">Ask a question</a>
        </div>
        <nav aria-label="Forum topics" class="mt-6 flex flex-wrap gap-2">
            <a href="/forum" @if (! $category) aria-current="page" @endif class="{{ $chip }}">All</a>
            @foreach (Forum::CATEGORIES as $key => $label)
                <a href="/forum?category={{ $key }}" @if ($category === $key) aria-current="page" @endif class="{{ $chip }}">{{ $label }}</a>
            @endforeach
        </nav>

        @if (! $topics || $topics->isEmpty())
            <div class="mt-8 rounded-[var(--radius-panel)] border border-dashed border-steel-300 bg-white p-10 text-center">
                <p class="text-lg text-steel-800">{{ $q !== '' ? 'No questions match your search.' : 'No questions here yet. Be the first to ask!' }}</p>
                <p class="mt-4"><a href="/forum/new{{ $category ? '?category='.$category : '' }}" class="{{ Ui::button('primary') }}">Ask a question</a></p>
            </div>
        @else
            <ul class="mt-8 divide-y divide-steel-200 rounded-[var(--radius-panel)] border border-steel-200 bg-white">
                @foreach ($topics as $t)
                    <li class="flex flex-wrap items-center justify-between gap-3 p-4 sm:p-6">
                        <div class="min-w-0">
                            <a href="{{ $t->path() }}" class="text-lg font-bold text-steel-950 underline-offset-2 hover:underline">{{ $t->title }}</a>@if ($t->pinned)<span class="ml-1 rounded-full bg-gold-50 px-2 text-xs font-semibold text-gold-800">Pinned</span>@endif
                            <p class="mt-0.5 text-sm text-steel-600">{{ Forum::CATEGORIES[$t->category] ?? $t->category }} · by {{ $t->author_name }} · {{ $t->last_post_at?->timezone('Asia/Manila')->diffForHumans() }}@if ($t->locked) · closed @endif</p>
                        </div>
                        <span class="text-sm font-semibold text-steel-700 tabular">{{ $t->replies_count }} {{ $t->replies_count === 1 ? 'reply' : 'replies' }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="mt-4">{{ $topics->links() }}</div>
        @endif

        @unless ($member || $staff)
            <p class="mt-8 text-steel-700">To ask or answer, <a href="/login?next=%2Fforum" class="font-semibold underline">sign in</a> or <a href="/signup?next=%2Fforum" class="font-semibold underline">create a free account</a>. Reading is open to everyone.</p>
        @endunless
        <p class="mt-4 text-sm text-steel-600">Please be kind and stay on topic. Advice here is general; for safety-critical electrical work, use a licensed electrician. See the <a href="/terms#forum" class="underline">forum rules</a>.</p>
    </x-container>
</x-site-layout>
