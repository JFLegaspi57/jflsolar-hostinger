@php
    use App\Support\BlogComments;
    use App\Support\Forum;
    use App\Support\Members;
    use App\Support\Ui;
    // Both language versions share these reactions and comments; after posting, members return to the version they were reading.
    $path = '/blog/'.$post->slug;
    $fil = $tagalog ?? false;
    $next = rawurlencode($fil ? $path.'/tagalog' : $path);
    $t = fn (string $en, string $tl) => $fil ? $tl : $en;
@endphp
{{-- Reactions and comments for signed-in members. Only positive comments are published automatically; others wait for staff review. --}}
<section aria-labelledby="comments-title" class="bp-panel">
    <h2 id="comments-title" class="text-lg font-bold text-steel-950">{{ $t('Reactions & comments', 'Mga reaksyon at komento') }}</h2>

    <div class="bp-reactions" role="group" aria-label="{{ $t('Reactions', 'Mga reaksyon') }}">
        @foreach (BlogComments::REACTIONS as $key => [$emoji, $label])
            @if ($member)
                <form method="post" action="{{ $path }}/react">
                    @csrf
                    <input type="hidden" name="reaction" value="{{ $key }}">
                    @if ($fil)<input type="hidden" name="version" value="tagalog">@endif
                    <button type="submit" class="bp-react" aria-pressed="{{ $myReaction === $key ? 'true' : 'false' }}" title="{{ $label }}">
                        <span aria-hidden="true">{{ $emoji }}</span> <span class="tabular">{{ $reactions[$key] }}</span><span class="sr-only"> {{ $label }}</span>
                    </button>
                </form>
            @else
                <a href="/login?next={{ $next }}" class="bp-react" title="{{ $label }} ({{ $t('sign in to react', 'mag-sign in para mag-react') }})">
                    <span aria-hidden="true">{{ $emoji }}</span> <span class="tabular">{{ $reactions[$key] }}</span><span class="sr-only"> {{ $label }}, {{ $t('sign in to react', 'mag-sign in para mag-react') }}</span>
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
                    @if ($c->status === 'review')<p class="bp-waiting">{{ $t('Waiting for review — only you can see this', 'Hinihintay ang pagsusuri — ikaw lang ang nakakakita nito') }}</p>@endif
                    <p class="bp-body">{{ $c->body }}</p>
                </div>
            </article>
        @empty
            <p class="text-sm text-steel-600">{{ $t('No comments yet. Be the first to share your thoughts.', 'Wala pang komento. Ikaw na ang mauna!') }}</p>
        @endforelse
    </div>

    @if ($member)
        <form method="post" action="{{ $path }}/comments" class="bp-form" novalidate>
            @csrf
            @if ($fil)<input type="hidden" name="version" value="tagalog">@endif
            <div class="bp-trap" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
            <label for="comment-body" class="block font-semibold text-steel-950">{{ $t('Write a comment', 'Sumulat ng komento') }}</label>
            <textarea id="comment-body" name="body" rows="4" maxlength="{{ BlogComments::MAX_CHARS }}" required
                @error('body') aria-invalid="true" aria-describedby="comment-error" @enderror>{{ old('body') }}</textarea>
            @error('body')<p id="comment-error" class="mt-1 text-sm font-semibold text-danger-700">{{ $message }}</p>@enderror
            <p class="mt-1 text-xs text-steel-600">@if ($fil)Magpo-post bilang <span class="font-semibold">{{ Forum::displayName($member->name) }}</span> — para manatiling magiliw ang lugar na ito, ang mga positibong komento lang ang ipinapakita; sinusuri muna ng aming team ang iba.@else Posting as <span class="font-semibold">{{ Forum::displayName($member->name) }}</span> — to keep this a friendly space, only positive comments are shown; others are reviewed by our team first.@endif</p>
            <button type="submit" class="{{ Ui::button('primary', 'md', 'mt-3 w-full') }}">{{ $t('Post comment', 'I-post ang komento') }}</button>
        </form>
    @else
        @if ($fil)
            <p class="bp-signin"><a href="/login?next={{ $next }}" class="font-semibold underline">Mag-sign in</a> o <a href="/signup?next={{ $next }}" class="font-semibold underline">gumawa ng libreng account</a> para mag-react at mag-comment.</p>
        @else
            <p class="bp-signin"><a href="/login?next={{ $next }}" class="font-semibold underline">Sign in</a> or <a href="/signup?next={{ $next }}" class="font-semibold underline">create a free account</a> to react and comment.</p>
        @endif
    @endif
</section>
