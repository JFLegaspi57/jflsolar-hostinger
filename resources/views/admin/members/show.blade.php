@php
    use App\Support\Members;
    use App\Support\Pricing;
    use App\Support\Visitors;
    use Illuminate\Support\Carbon;
    $when = fn ($t) => Carbon::parse($t)->timezone('Asia/Manila');
    $card = 'rounded-[var(--radius-panel)] border border-steel-200 bg-white p-5';
    $icons = ['facebook_url' => 'facebook', 'youtube_url' => 'youtube', 'instagram_url' => 'instagram', 'tiktok_url' => 'tiktok'];
    $pagesByDay = $pages->groupBy(fn ($p) => $when($p->created_at)->format('l, j M Y'));
@endphp
<x-admin.layout :title="$m->name">
    <p class="mb-3"><a href="/admin/members" class="font-semibold underline">← Members</a></p>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            @if ($photo = Members::photoUrl($m))
                <x-photo-zoom :full="Members::photoFullUrl($m)" :small="$photo" :alt="$m->name" :label="'View '.$m->name.'’s full photo'"><img src="{{ $photo }}" alt="" width="80" height="80" style="width: 5rem; height: 5rem" class="shrink-0 rounded-full border border-steel-200 object-cover"></x-photo-zoom>
            @else
                <span style="width: 5rem; height: 5rem" class="inline-flex shrink-0 items-center justify-center rounded-full bg-steel-100 text-steel-600"><x-icon name="user" class="size-10" /></span>
            @endif
            <div>
                <h1 class="text-2xl font-extrabold text-steel-950">{{ $m->name }} @if ($m->status !== 'active')<span class="ml-1 rounded-full bg-danger-50 px-2 text-xs font-semibold text-danger-700">Disabled</span>@endif</h1>
                <p class="text-steel-700 break-all"><a href="mailto:{{ $m->email }}" class="underline">{{ $m->email }}</a>@if ($m->phone) · <a href="tel:{{ preg_replace('/[^0-9+]/', '', $m->phone) }}" class="underline tabular">{{ $m->phone }}</a>@endif</p>
                <p class="text-sm text-steel-600">Joined {{ $m->created_at->timezone('Asia/Manila')->format('j M Y, g:i A') }} · Promos: {{ $subscribed ? 'yes' : 'no' }}</p>
                @php $socials = collect(Members::SOCIALS)->filter(fn ($s, $f) => $m->{$f}); @endphp
                @if ($socials->isNotEmpty())
                    <p class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                        @foreach ($socials as $field => [$label])
                            <a href="{{ $m->{$field} }}" target="_blank" rel="noopener noreferrer nofollow" class="inline-flex items-center gap-1 underline"><x-icon :name="$icons[$field]" class="size-4" /> {{ $label }}</a>
                        @endforeach
                    </p>
                @endif
            </div>
        </div>
        <form method="post" action="/admin/members/{{ $m->id }}">
            @csrf
            <button type="submit" name="action" value="{{ $m->status === 'active' ? 'disable' : 'enable' }}" class="{{ \App\Support\Ui::button('secondary', 'sm') }}">{{ $m->status === 'active' ? 'Disable account' : 'Enable account' }}</button>
        </form>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section aria-labelledby="logins-h" class="{{ $card }}">
            <h2 id="logins-h" class="text-lg font-bold text-steel-950">Sign-ins</h2>
            <p class="text-sm text-steel-600">Time and approximate place (from the internet connection; may show the provider’s city).</p>
            @if ($logins->isEmpty())
                <p class="mt-3 text-steel-700">{{ $m->last_login_at ? 'Last signed in '.$m->last_login_at->timezone('Asia/Manila')->format('j M Y, g:i A').' (before sign-in places were recorded).' : 'No sign-ins recorded yet.' }}</p>
            @else
                <ul class="mt-3 divide-y divide-steel-200 text-sm">
                    @foreach ($logins as $l)
                        <li class="flex flex-wrap justify-between gap-2 py-2"><span class="tabular">{{ $when($l->created_at)->format('D j M Y, g:i A') }}</span><span class="text-steel-700">{{ $l->place ?: 'Place unknown' }}</span></li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section aria-labelledby="orders-h" class="{{ $card }}">
            <h2 id="orders-h" class="text-lg font-bold text-steel-950">Orders</h2>
            @if ($orders->isEmpty())
                <p class="mt-3 text-steel-700">No orders placed while signed in.</p>
            @else
                <ul class="mt-3 divide-y divide-steel-200 text-sm">
                    @foreach ($orders as $o)
                        <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                            <a href="/admin/orders/{{ $o->id }}" class="font-mono font-semibold underline">{{ $o->reference }}</a>
                            <span class="flex flex-wrap items-center gap-2 text-steel-700">{{ $o->created_at->timezone('Asia/Manila')->format('j M Y') }} · {{ $o->statusLabel() }} · <span class="tabular">{{ Pricing::formatPhp($o->total_php) }}</span>
                                @if (in_array($o->status, \App\Http\Controllers\Admin\OrderController::DELETABLE, true))
                                    <x-admin.confirm :action="'/admin/orders/'.$o->id.'/delete'" :title="'Delete order '.$o->reference.'?'" confirm-label="Delete permanently" label="Delete" :sr-label="'order '.$o->reference" :fields="['back' => 'member:'.$m->id]">
                                        <p>This order is removed for good, with its items. Paid orders lose their payment record too, so keep any records you need for your accounts first. This can’t be undone.</p>
                                    </x-admin.confirm>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <section id="messages" aria-labelledby="messages-h" class="{{ $card }} mt-6">
        <h2 id="messages-h" class="text-lg font-bold text-steel-950">Message on {{ $m->name }}’s account page</h2>
        <p class="text-sm text-steel-600">Only this member sees it, at the top of their account page, until they close it. Plain text: each line is a paragraph, lines starting with "- " become a list.</p>
        @foreach ($messages as $msg)
            <div class="mt-3 rounded-[var(--radius-control)] border border-steel-200 p-3">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <p class="font-semibold text-steel-950">{{ $msg->title }}</p>
                    <form method="post" action="/admin/members/{{ $m->id }}/messages/{{ $msg->id }}/delete">@csrf<button type="submit" class="text-sm underline text-steel-700">Remove</button></form>
                </div>
                <p class="text-sm text-steel-600">Posted {{ $when($msg->created_at)->format('j M Y, g:i A') }} · {{ $msg->closed_at ? 'Read and closed by the member '.$when($msg->closed_at)->format('j M Y, g:i A') : 'Showing on the account page (not closed yet)' }}</p>
                <details class="mt-1 text-sm"><summary class="cursor-pointer underline">Show the message</summary><div class="mt-2 text-steel-800"><x-paragraphs :text="$msg->body" /></div></details>
            </div>
        @endforeach
        <form method="post" action="/admin/members/{{ $m->id }}/messages" class="mt-4 space-y-3">
            @csrf
            <label class="block"><span class="text-sm font-semibold">Title</span><input name="title" required maxlength="200" class="mt-1 min-h-11 w-full rounded-[var(--radius-control)] border border-steel-400 px-3" placeholder="A personal message from JF Legaspi"></label>
            <label class="block"><span class="text-sm font-semibold">Message</span><textarea name="body" required rows="8" maxlength="10000" class="mt-1 w-full rounded-[var(--radius-control)] border border-steel-400 p-3"></textarea></label>
            <button type="submit" class="{{ \App\Support\Ui::button('primary', 'sm') }}">Post on the account page</button>
        </form>
    </section>

    <section aria-labelledby="course-h" class="{{ $card }} mt-6">
        <h2 id="course-h" class="text-lg font-bold text-steel-950">Online course record</h2>
        @include('partials.course-certificate', ['memberId' => $m->id])
        @php $certIssued = \App\Support\CourseCertificates::for($m->id); @endphp
        <form method="post" action="/admin/members/{{ $m->id }}/certificate" class="mt-3 flex flex-wrap items-end gap-3">
            @csrf
            @if ($certIssued)
                <input type="hidden" name="action" value="revoke">
                <button type="submit" class="text-sm underline text-steel-700" onclick="return confirm('Remove this certificate number? The member will no longer see it.')">Remove the certificate number</button>
            @else
                <input type="hidden" name="action" value="issue">
                <label class="text-sm"><span class="font-semibold">Course completed on</span><br><input type="date" name="completed_on" value="{{ ($courseRecord['completedOn'] ?? null) ?: now('Asia/Manila')->toDateString() }}" class="mt-1 min-h-10 rounded-[var(--radius-control)] border border-steel-400 px-2"></label>
                <button type="submit" class="{{ \App\Support\Ui::button('primary', 'sm') }}">Issue certificate number (CTN)</button>
                <span class="text-sm text-steel-600">Gives this member their own unique number, shown on their account page.</span>
            @endif
        </form>
        @include('pages.course.record', ['record' => $courseRecord, 'staff' => true])
    </section>

    <section aria-labelledby="pages-h" class="{{ $card }} mt-6">
        <h2 id="pages-h" class="text-lg font-bold text-steel-950">Pages viewed while signed in</h2>
        <p class="text-sm text-steel-600">Newest first, last {{ $pages->count() }} shown. Not recorded when the browser asks not to be tracked. Kept for about 13 months.</p>
        @if ($pages->isEmpty())
            <p class="mt-3 text-steel-700">No pages recorded yet.</p>
        @else
            @foreach ($pagesByDay as $day => $rows)
                <h3 class="mt-4 text-sm font-semibold tracking-wide text-steel-600 uppercase">{{ $day }}</h3>
                <ul class="mt-1 divide-y divide-steel-200 text-sm">
                    @foreach ($rows as $p)
                        @php $path = (string) $p->path; $plain = strtok($path, '?') ?: '/'; @endphp
                        <li class="flex flex-wrap items-baseline justify-between gap-2 py-1.5">
                            <a href="{{ $path }}" target="_blank" rel="noopener" class="min-w-0 underline break-all">{{ Visitors::pageLabel($plain) }}@if ($path !== $plain) <span class="text-steel-600">({{ substr($path, strlen($plain)) }})</span>@endif</a>
                            <span class="text-steel-600 tabular">{{ $when($p->created_at)->format('g:i A') }}@if ($p->place) · {{ $p->place }}@endif</span>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        @endif
    </section>
</x-admin.layout>
