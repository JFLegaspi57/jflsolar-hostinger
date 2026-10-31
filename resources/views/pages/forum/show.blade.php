@php
    use App\Support\Forum;
    use App\Support\Ui;
    $input = 'w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3.5 py-3 text-base text-steel-950 placeholder:text-steel-600 focus:border-steel-900 aria-[invalid=true]:border-danger-700';
    $opening = $posts->firstWhere('is_opening', true);
    $back = $topic->path();
    $mod = fn ($action, $label, $url) => '<form method="post" action="'.$url.'" class="inline">'.csrf_field().'<input type="hidden" name="back" value="'.e($back).'"><button type="submit" name="action" value="'.$action.'" class="'.Ui::button('secondary', 'sm').'">'.e($label).'</button></form>';
@endphp
<x-site-layout :title="$topic->title.' — Solar forum'" :path="$topic->path()" :noindex="$topic->status !== 'published'" :description="\Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', (string) $opening?->body), 155)" :reviews-above-footer="false">
    <x-container class="py-10 sm:py-14">
        <p><a href="/forum?category={{ $topic->category }}" class="font-semibold text-steel-800 underline">← {{ Forum::CATEGORIES[$topic->category] ?? 'Forum' }}</a></p>
        <h1 class="mt-3 text-3xl font-extrabold text-steel-950 sm:text-4xl">{{ $topic->title }}</h1>
        <p class="mt-2 text-steel-600">
            @if ($topic->pinned)<span style="margin-right:.25rem" class="rounded-full bg-gold-50 px-2 text-xs font-semibold text-gold-800">Pinned</span>@endif
            @if ($topic->locked)<span style="margin-right:.25rem" class="rounded-full bg-steel-100 px-2 text-xs font-semibold text-steel-800">Closed</span>@endif
            @if ($topic->status === 'pending')<span style="margin-right:.25rem" class="rounded-full bg-warn-50 px-2 text-xs font-semibold text-warn-800">Waiting for approval</span>@endif
            @if ($topic->status === 'hidden')<span style="margin-right:.25rem" class="rounded-full bg-danger-50 px-2 text-xs font-semibold text-danger-700">Hidden from the public</span>@endif
            {{ $topic->replies_count }} {{ $topic->replies_count === 1 ? 'reply' : 'replies' }}
        </p>
        <div class="mt-4 space-y-3">
            @if (session('forum_status'))<p role="status" class="rounded-[var(--radius-control)] border border-[#b7e2c6] bg-ok-50 p-4 font-semibold text-ok-700">{{ session('forum_status') }}</p>@endif
            @if (session('forum_error'))<p role="alert" class="rounded-[var(--radius-control)] border border-[#f3c3be] bg-danger-50 p-4 font-semibold text-danger-700">{{ session('forum_error') }}</p>@endif
        </div>

        @if ($staff)
            <div class="mt-4 flex flex-wrap items-center gap-2 rounded-[var(--radius-control)] border border-steel-200 bg-steel-50 p-3 text-sm">
                <span class="font-semibold text-steel-800">Staff:</span>
                {!! $mod($topic->pinned ? 'unpin' : 'pin', $topic->pinned ? 'Unpin' : 'Pin to top', '/admin/forum/topics/'.$topic->id) !!}
                {!! $mod($topic->locked ? 'unlock' : 'lock', $topic->locked ? 'Open for replies' : 'Close replies', '/admin/forum/topics/'.$topic->id) !!}
                {!! $mod($topic->status === 'hidden' ? 'restore' : 'hide', $topic->status === 'hidden' ? 'Show topic' : 'Hide topic', '/admin/forum/topics/'.$topic->id) !!}
            </div>
        @endif

        <ol class="mt-6 space-y-4">
            @foreach ($posts as $p)
                <li id="post-{{ $p->id }}" class="rounded-[var(--radius-panel)] border {{ $p->isStaff() ? 'border-gold-500 bg-gold-50' : 'border-steel-200 bg-white' }} p-5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="font-semibold text-steel-950">
                            {{ $p->author_name }}
                            @if ($p->isStaff())<span class="ml-1 rounded-full bg-steel-900 px-2 text-xs font-semibold text-white">Staff</span>@endif
                            @if ($p->status === 'pending')<span class="ml-1 rounded-full bg-warn-50 px-2 text-xs font-semibold text-warn-800">Waiting for approval — only you{{ $staff ? ' and staff' : '' }} can see this</span>@endif
                            @if ($p->status === 'hidden')<span class="ml-1 rounded-full bg-danger-50 px-2 text-xs font-semibold text-danger-700">Hidden</span>@endif
                        </p>
                        <p class="text-sm text-steel-600"><time datetime="{{ $p->created_at->toAtomString() }}">{{ $p->created_at->timezone('Asia/Manila')->format('M j, Y g:i A') }}</time></p>
                    </div>
                    <div class="mt-3 text-steel-800" style="overflow-wrap: anywhere">{{ Forum::render($p->body) }}</div>
                    @if ($staff)
                        <div class="mt-3 flex flex-wrap gap-2">
                            @if ($p->status === 'pending'){!! $mod('approve', 'Approve', '/admin/forum/posts/'.$p->id) !!}@endif
                            {!! $mod($p->status === 'hidden' ? 'restore' : 'hide', $p->status === 'hidden' ? 'Show again' : 'Hide', '/admin/forum/posts/'.$p->id) !!}
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>

        <section id="reply" aria-labelledby="reply-h" class="mt-10 max-w-3xl">
            <h2 id="reply-h" class="text-xl font-bold text-steel-950">Your reply</h2>
            @if ($topic->locked && ! $staff)
                <p class="mt-2 text-steel-700">This topic is closed to new replies.</p>
            @elseif (! $member && ! $staff)
                <p class="mt-2 text-steel-700"><a href="/login?next={{ rawurlencode($topic->path()) }}" class="font-semibold underline">Sign in</a> or <a href="/signup?next={{ rawurlencode($topic->path()) }}" class="font-semibold underline">create a free account</a> to reply.</p>
            @elseif ($topic->status !== 'published' && ! $staff)
                <p class="mt-2 text-steel-700">Replies open once this topic is approved.</p>
            @else
                <form method="post" action="{{ $topic->path() }}/reply" class="mt-3 space-y-3">
                    @csrf
                    <div aria-hidden="true" class="absolute -left-[9999px] h-px w-px overflow-hidden"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off" value=""></label></div>
                    <label for="r-body" class="sr-only">Your reply</label>
                    <textarea id="r-body" name="body" rows="5" maxlength="5000" required @if ($errors->has('body')) aria-invalid="true" aria-describedby="r-body-error" @endif class="{{ $input }}">{{ old('body') }}</textarea>
                    @if ($errors->has('body'))<p id="r-body-error" class="text-sm font-semibold text-danger-700">{{ $errors->first('body') }}</p>@endif
                    <p class="text-sm text-steel-600">Posting as <span class="font-semibold">{{ $staff ? Forum::STAFF_NAME : Forum::displayName($member->name) }}</span>. Plain text; up to {{ Forum::MAX_LINKS }} links.</p>
                    <button type="submit" class="{{ Ui::button('primary') }}">Post reply</button>
                </form>
            @endif
        </section>
    </x-container>
</x-site-layout>
