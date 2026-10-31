@php $tab = 'inline-flex min-h-11 items-center gap-1.5 rounded-full border border-steel-300 px-4 text-sm font-semibold text-steel-800 aria-[current=page]:border-steel-900 aria-[current=page]:bg-steel-900 aria-[current=page]:text-white'; @endphp
<x-admin.layout title="Forum">
    <x-admin.page-title title="Forum" intro="A member’s posts wait here until you approve one; after that their posts appear at once. Hide anything off-topic or spammy. To answer as “JFL Solar team”, open the forum while signed in here. Pin, lock or hide whole topics from the topic page." />
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <nav aria-label="Filter posts" class="flex flex-wrap gap-2">
            <a href="/admin/forum" @if ($view === 'pending') aria-current="page" @endif class="{{ $tab }}">Waiting for approval <span class="tabular">({{ $pending }})</span></a>
            <a href="/admin/forum?view=recent" @if ($view === 'recent') aria-current="page" @endif class="{{ $tab }}">Recent posts</a>
            <a href="/admin/forum?view=hidden" @if ($view === 'hidden') aria-current="page" @endif class="{{ $tab }}">Hidden</a>
        </nav>
        <a href="/forum" class="{{ \App\Support\Ui::button('secondary') }}">Open the forum</a>
    </div>
    @if (! $posts || $posts->isEmpty())
        <p class="rounded-[var(--radius-panel)] border border-dashed border-steel-300 bg-white p-8 text-center text-steel-700">{{ $view === 'pending' ? 'Nothing waiting for approval.' : 'Nothing here.' }}</p>
    @else
        <ul class="space-y-3">
            @foreach ($posts as $p)
                <li class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4">
                    <p class="text-sm text-steel-600">
                        {{ $p->is_opening ? 'New topic' : 'Reply' }} in <a href="{{ $p->topic->path() }}#post-{{ $p->id }}" class="font-semibold text-steel-950 underline">{{ $p->topic->title }}</a>
                        · {{ $p->created_at->timezone('Asia/Manila')->format('j M Y, g:i A') }}
                    </p>
                    <p class="mt-1 font-semibold text-steel-950">{{ $p->author_name }}@if ($p->member) <span class="font-normal text-steel-600">· {{ $p->member->name }} · <a href="mailto:{{ $p->member->email }}" class="underline">{{ $p->member->email }}</a></span>@endif</p>
                    <div class="mt-2 text-steel-800">{{ \Illuminate\Support\Str::limit($p->body, 600) }}</div>
                    <form method="post" action="/admin/forum/posts/{{ $p->id }}" class="mt-3 flex flex-wrap gap-2">
                        @csrf
                        <input type="hidden" name="back" value="/admin/forum{{ $view === 'pending' ? '' : '?view='.$view }}">
                        @if ($p->status === 'pending')
                            <button type="submit" name="action" value="approve" class="{{ \App\Support\Ui::button('primary', 'sm') }}">Approve</button>
                            <button type="submit" name="action" value="hide" class="{{ \App\Support\Ui::button('secondary', 'sm') }}">Hide (reject)</button>
                        @elseif ($p->status === 'published')
                            <button type="submit" name="action" value="hide" class="{{ \App\Support\Ui::button('secondary', 'sm') }}">Hide</button>
                        @else
                            <button type="submit" name="action" value="restore" class="{{ \App\Support\Ui::button('secondary', 'sm') }}">Show again</button>
                        @endif
                    </form>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $posts->links() }}</div>
    @endif
</x-admin.layout>
