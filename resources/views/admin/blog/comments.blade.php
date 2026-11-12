@php $tab = 'inline-flex min-h-11 items-center gap-1.5 rounded-full border border-steel-300 px-4 text-sm font-semibold text-steel-800 aria-[current=page]:border-steel-900 aria-[current=page]:bg-steel-900 aria-[current=page]:text-white'; @endphp
<x-admin.layout title="Blog comments">
    <x-admin.page-title title="Blog comments" back="/admin/blog" back-label="All posts"
        intro="Members can react to and comment on blog posts. Positive comments appear straight away; any comment that is not clearly positive waits here for you. Approve it to show it, or hide it. Nothing is deleted." />
    @if (session('status'))
        <p role="status" class="mb-4 rounded-[var(--radius-control)] border border-[#b7e2c6] bg-ok-50 px-3 py-2 text-sm font-semibold text-ok-700">{{ session('status') }}</p>
    @endif
    @unless (\App\Support\Chatbot::available())
        <p class="mb-4 rounded-[var(--radius-control)] border border-steel-200 bg-white px-3 py-2 text-sm text-steel-700">Comments are checked with a word list for now. Once the Anthropic API key is added (the same one that switches on the Buckny chat), Claude reads each comment instead, which catches far more.</p>
    @endunless
    <nav aria-label="Filter comments" class="mb-4 flex flex-wrap gap-2">
        <a href="/admin/blog/comments" @if ($view === 'review') aria-current="page" @endif class="{{ $tab }}">Waiting for review <span class="tabular">({{ $waiting }})</span></a>
        <a href="/admin/blog/comments?view=published" @if ($view === 'published') aria-current="page" @endif class="{{ $tab }}">Published</a>
        <a href="/admin/blog/comments?view=hidden" @if ($view === 'hidden') aria-current="page" @endif class="{{ $tab }}">Hidden</a>
    </nav>
    @if ($comments->isEmpty())
        <p class="rounded-[var(--radius-panel)] border border-dashed border-steel-300 bg-white p-8 text-center text-steel-700">{{ $view === 'review' ? 'Nothing waiting for review.' : 'Nothing here.' }}</p>
    @else
        <ul class="space-y-3">
            @foreach ($comments as $c)
                <li class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4">
                    <p class="text-sm text-steel-600">
                        On @if ($c->post)<a href="/blog/{{ $c->post->slug }}#comments" class="font-semibold text-steel-950 underline">{{ $c->post->title }}</a>@else a deleted post @endif
                        · {{ $c->created_at->timezone('Asia/Manila')->format('j M Y, g:i A') }}
                    </p>
                    <p class="mt-1 font-semibold text-steel-950">{{ $c->author_name }}@if ($c->member) <span class="font-normal text-steel-600">· {{ $c->member->name }} · <a href="mailto:{{ $c->member->email }}" class="underline">{{ $c->member->email }}</a></span>@endif</p>
                    <div class="mt-2 text-steel-800" style="white-space: pre-line">{{ $c->body }}</div>
                    @if ($c->check_note)<p class="mt-2 text-xs text-steel-600">{{ $c->check_note }}</p>@endif
                    <form method="post" action="/admin/blog/comments/{{ $c->id }}" class="mt-3 flex flex-wrap gap-2">
                        @csrf
                        <input type="hidden" name="view" value="{{ $view }}">
                        @if ($c->status === 'review')
                            <button type="submit" name="action" value="approve" class="{{ \App\Support\Ui::button('primary', 'sm') }}">Approve and show</button>
                            <button type="submit" name="action" value="hide" class="{{ \App\Support\Ui::button('secondary', 'sm') }}">Hide</button>
                        @elseif ($c->status === 'published')
                            <button type="submit" name="action" value="hide" class="{{ \App\Support\Ui::button('secondary', 'sm') }}">Hide</button>
                        @else
                            <button type="submit" name="action" value="restore" class="{{ \App\Support\Ui::button('secondary', 'sm') }}">Show again</button>
                        @endif
                    </form>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $comments->links() }}</div>
    @endif
</x-admin.layout>
