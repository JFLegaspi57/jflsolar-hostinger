@php
    use App\Support\ChatLog;
    $tab = 'inline-flex min-h-11 items-center gap-1.5 rounded-full border border-steel-300 px-4 text-sm font-semibold text-steel-800 aria-[current=page]:border-steel-900 aria-[current=page]:bg-steel-900 aria-[current=page]:text-white';
    $langs = ['en' => 'English', 'fil' => 'Filipino'];
@endphp
<x-admin.layout title="Buckny chats">
    <x-admin.page-title title="Buckny chats" :intro="'What visitors ask Buckny, and his answers. Newest first; chats are deleted after '.ChatLog::KEEP_DAYS.' days. Today: '.$today.' '.($today === 1 ? 'chat' : 'chats').'.'" />

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <nav aria-label="Filter chats" class="flex flex-wrap gap-2">
            <a href="/admin/chats" @if ($view === 'all' && ! $visitor) aria-current="page" @endif class="{{ $tab }}">All <span class="tabular">({{ $counts['all'] }})</span></a>
            <a href="/admin/chats?view=callback" @if ($view === 'callback') aria-current="page" @endif class="{{ $tab }}">Asked for a callback <span class="tabular">({{ $counts['callback'] }})</span></a>
        </nav>
        <form method="get" action="/admin/chats" role="search" class="ml-auto flex flex-wrap gap-2">
            @if ($view === 'callback')<input type="hidden" name="view" value="callback">@endif
            <label for="chat-q" class="sr-only">Search chats</label>
            <input id="chat-q" type="search" name="q" value="{{ $search }}" placeholder="Search words, e.g. battery" class="min-h-11 rounded-[var(--radius-control)] border border-steel-500 px-3" style="width: 16rem; max-width: 100%">
            <button type="submit" class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] bg-steel-900 px-4 font-semibold text-white">Search</button>
        </form>
    </div>
    @if ($visitor)
        <p class="mb-4 text-steel-700">Showing chats from one visitor. <a href="/admin/chats" class="font-semibold underline">Show all</a></p>
    @endif

    @if ($chats->isEmpty())
        <p class="rounded-[var(--radius-panel)] border border-dashed border-steel-300 bg-white p-8 text-center text-steel-700">{{ $search !== '' ? 'No chats with “'.$search.'”.' : 'No chats yet. They appear here as soon as a visitor asks Buckny something.' }}</p>
    @else
        <ul class="divide-y divide-steel-200 rounded-[var(--radius-panel)] border border-steel-200 bg-white">
            @foreach ($chats as $c)
                @php $first = ChatLog::firstQuestion($c); @endphp
                <li class="p-4">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <a href="/admin/chats/{{ $c->id }}" class="line-clamp-2 min-w-0 flex-1 text-lg font-bold text-steel-950 underline-offset-2 hover:underline">{{ $first !== '' ? $first : 'Chat' }}</a>
                        <span class="text-sm text-steel-600 tabular">{{ \Illuminate\Support\Carbon::parse($c->last_at)->timezone('Asia/Manila')->format('j M Y, g:i A') }}</span>
                    </div>
                    <p class="mt-1 text-sm text-steel-700">
                        {{ $c->questions }} {{ (int) $c->questions === 1 ? 'question' : 'questions' }}
                        · {{ $langs[$c->lang] ?? $c->lang }}
                        @if ($c->device) · {{ $c->device }} @endif
                        @if ($c->page) · from <span class="break-words">{{ $c->page }}</span> @endif
                        @if ($c->callback) · <strong class="text-ok-700">Asked for a callback</strong> @endif
                    </p>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $chats->links() }}</div>
    @endif
</x-admin.layout>
