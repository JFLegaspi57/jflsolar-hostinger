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
                <img src="{{ $photo }}" alt="" width="80" height="80" style="width: 5rem; height: 5rem" class="shrink-0 rounded-full border border-steel-200 object-cover">
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
                        <li class="flex flex-wrap justify-between gap-2 py-2">
                            <a href="/admin/orders/{{ $o->id }}" class="font-mono font-semibold underline">{{ $o->reference }}</a>
                            <span class="text-steel-700">{{ $o->created_at->timezone('Asia/Manila')->format('j M Y') }} · {{ $o->statusLabel() }} · <span class="tabular">{{ Pricing::formatPhp($o->total_php) }}</span></span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

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
