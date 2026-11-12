@php
    use App\Support\BlogComments;
    use App\Support\Forum;
    use App\Support\Members;
    use App\Support\Ui;
    $path = '/blog/'.$post->slug;
    $next = rawurlencode($path);
@endphp
{{-- Reactions and comments for signed-in members. Only positive comments are published automatically; others wait for staff review. --}}
<section aria-labelledby="comments-title" class="bp-panel">
    <h2 id="comments-title" class="text-lg font-bold text-steel-950">Reactions &amp; comments</h2>

    <div class="bp-reactions" role="group" aria-label="Reactions">
        @foreach (BlogComments::REACTIONS as $key => [$emoji, $label])
            @if ($member)
                <form method="post" action="{{ $path }}/react">
                    @csrf
                    <input type="hidden" name="reaction" value="{{ $key }}">
                    <button type="submit" class="bp-react" aria-pressed="{{ $myReaction === $key ? 'true' : 'false' }}" title="{{ $label }}">
                        <span aria-hidden="true">{{ $emoji }}</span> <span class="tabular">{{ $reactions[$key] }}</span><span class="sr-only"> {{ $label }}</span>
                    </button>
                </form>
            @else
                <a href="/login?next={{ $next }}" class="bp-react" title="{{ $label }} (sign in to react)">
                    <span aria-hidden="true">{{ $emoji }}</span> <span class="tabular">{{ $reactions[$key] }}</span><span class="sr-only"> {{ $label }}, sign in to react</span>
                </a>
            @endif
        @endforeach
    </div>

    @if (session('comment_status'))
        <p role="status" class="bp-flash">{{ session('comment_status') }}</p>
    @endif

    <div class="bp-comment-list">
        @forelse ($comments as $c)
            @php $photo = $c->member ? Members::photoUrl($c->member) : null; @endphp
            <article class="bp-comment">
                <div class="bp-avatar" aria-hidden="true">
                    @if ($photo)<img src="{{ $photo }}" alt="" width="36" height="36">@else{{ mb_strtoupper(mb_substr($c->author_name, 0, 1)) }}@endif
                </div>
                <div class="min-w-0">
                    <p class="text-sm"><span class="font-semibold text-steel-950">{{ $c->author_name }}</span>
                        <span class="text-steel-600">· <time datetime="{{ $c->created_at->toAtomString() }}">{{ $c->created_at->diffForHumans() }}</time></span></p>
                    @if ($c->status === 'review')<p class="bp-waiting">Waiting for review — only you can see this</p>@endif
                    <p class="bp-body">{{ $c->body }}</p>
                </div>
            </article>
        @empty
            <p class="text-sm text-steel-600">No comments yet. Be the first to share your thoughts.</p>
        @endforelse
    </div>

    @if ($member)
        <form method="post" action="{{ $path }}/comments" class="bp-form" novalidate>
            @csrf
            <div class="bp-trap" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
            <label for="comment-body" class="block font-semibold text-steel-950">Write a comment</label>
            <textarea id="comment-body" name="body" rows="4" maxlength="{{ BlogComments::MAX_CHARS }}" required
                @error('body') aria-invalid="true" aria-describedby="comment-error" @enderror>{{ old('body') }}</textarea>
            @error('body')<p id="comment-error" class="mt-1 text-sm font-semibold text-danger-700">{{ $message }}</p>@enderror
            <p class="mt-1 text-xs text-steel-600">Posting as <span class="font-semibold">{{ Forum::displayName($member->name) }}</span> — to keep this a friendly space, only positive comments are shown; others are reviewed by our team first.</p>
            <button type="submit" class="{{ Ui::button('primary', 'md', 'mt-3 w-full') }}">Post comment</button>
        </form>
    @else
        <p class="bp-signin"><a href="/login?next={{ $next }}" class="font-semibold underline">Sign in</a> or <a href="/signup?next={{ $next }}" class="font-semibold underline">create a free account</a> to react and comment.</p>
    @endif
</section>
