@php $input = 'min-h-11 w-full rounded-[var(--radius-control)] border border-steel-400 bg-white px-3 text-base text-steel-950'; @endphp
<x-admin.layout title="Members">
    <x-admin.page-title title="Members" intro="Customer accounts created on the website (they can also post in the forum). Open a name to see their orders, sign-ins (time and approximate place) and the pages they viewed while signed in. Disabling an account signs the person out and stops them signing in." />
    <dl class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4" aria-label="Member totals">
        @foreach ([['Total members', $counts['total']], ['Active', $counts['active']], ['Disabled', $counts['total'] - $counts['active']], ['Joined in the last 30 days', $counts['new30']]] as $i => [$label, $value])
            <div class="rounded-[var(--radius-panel)] border {{ $i === 0 ? 'border-gold-500 bg-gold-50' : 'border-steel-200 bg-white' }} p-4">
                <dt class="text-sm font-semibold text-steel-700">{{ $label }}</dt>
                <dd class="mt-1 text-3xl font-extrabold text-steel-950 tabular">{{ number_format($value) }}</dd>
            </div>
        @endforeach
    </dl>
    <section id="online" aria-labelledby="online-title" class="mb-6 rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 id="online-title" class="flex items-center gap-2 text-lg font-bold text-steel-950">
                <span aria-hidden="true" style="display:inline-block;width:.65rem;height:.65rem;border-radius:999px;background:{{ $online->isNotEmpty() ? '#16a34a' : 'var(--color-steel-300)' }}"></span>
                Online now <span class="tabular text-steel-600">({{ $online->count() }})</span>
            </h2>
            <a href="/admin/members#online" class="text-sm font-semibold text-steel-900 underline">Refresh</a>
        </div>
        <p class="mt-1 text-sm text-steel-600">Signed-in members who opened a page in the last {{ \App\Support\Members::ONLINE_MINUTES }} minutes. Only staff can see this list.</p>
        @if ($online->isEmpty())
            <p class="mt-3 text-steel-700">No members are online right now.</p>
        @else
            <ul class="mt-3 divide-y divide-steel-200">
                @foreach ($online as $o)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div class="flex min-w-0 items-center gap-3">
                            @if ($photo = \App\Support\Members::photoUrl($o))
                                <img src="{{ $photo }}" alt="" width="40" height="40" class="size-10 shrink-0 rounded-full object-cover">
                            @else
                                <span aria-hidden="true" class="flex size-10 shrink-0 items-center justify-center rounded-full bg-steel-900 font-bold text-gold-400">{{ mb_strtoupper(mb_substr($o->name, 0, 1)) }}</span>
                            @endif
                            <div class="min-w-0">
                                <p class="font-semibold break-words text-steel-950"><a href="/admin/members/{{ $o->id }}" class="underline-offset-2 hover:underline">{{ $o->name }}</a></p>
                                <p class="text-sm break-all text-steel-600">{{ $o->email }}</p>
                            </div>
                        </div>
                        <div class="text-sm text-steel-700">
                            <p>@if ($o->last_path)Viewing <a href="{{ $o->last_path }}" target="_blank" rel="noopener" class="font-semibold break-all text-steel-900 underline">{{ \Illuminate\Support\Str::limit($o->last_path, 60) }}</a>@else Just signed in @endif</p>
                            <p class="text-steel-600">{{ $o->last_seen->diffForHumans() }}@if ($o->last_place) · {{ $o->last_place }}@endif</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
    @if ($q !== '' && $members)
        <p role="status" class="mb-3 text-sm text-steel-700">{{ number_format($members->total()) }} {{ $members->total() === 1 ? 'member matches' : 'members match' }} “{{ $q }}”.</p>
    @endif
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <form method="get" action="/admin/members" class="flex w-full max-w-xl gap-2">
        <label for="m-q" class="sr-only">Search members</label>
        <input id="m-q" name="q" type="search" value="{{ $q }}" placeholder="Search name, email or phone" class="{{ $input }}">
        <button type="submit" class="{{ \App\Support\Ui::button('secondary') }}">Search</button>
    </form>
    <a href="/admin/members/export" class="{{ \App\Support\Ui::button('primary') }}">Download list (CSV)</a>
    </div>
    @if (! $members || $members->isEmpty())
        <p class="rounded-[var(--radius-panel)] border border-dashed border-steel-300 bg-white p-8 text-center text-steel-700">{{ $q !== '' ? 'No members match your search.' : 'No one has created an account yet.' }}</p>
    @else
        <ul class="divide-y divide-steel-200 rounded-[var(--radius-panel)] border border-steel-200 bg-white">
            @foreach ($members as $m)
                <li class="flex flex-wrap items-center justify-between gap-3 p-4">
                    <div class="flex min-w-0 items-start gap-3">
                    @if ($photo = \App\Support\Members::photoUrl($m))
                        <img src="{{ $photo }}" alt="" width="48" height="48" loading="lazy" style="width: 3rem; height: 3rem" class="shrink-0 rounded-full border border-steel-200 object-cover">
                    @else
                        <span style="width: 3rem; height: 3rem" class="inline-flex shrink-0 items-center justify-center rounded-full bg-steel-100 text-steel-600"><x-icon name="user" class="size-6" /></span>
                    @endif
                    <div class="min-w-0">
                        <p class="font-bold text-steel-950"><a href="/admin/members/{{ $m->id }}" class="underline-offset-2 hover:underline">{{ $m->name }}</a> @if ($m->status !== 'active')<span class="ml-1 rounded-full bg-danger-50 px-2 text-xs font-semibold text-danger-700">Disabled</span>@endif</p>
                        <p class="text-sm text-steel-700 break-all"><a href="mailto:{{ $m->email }}" class="underline">{{ $m->email }}</a>@if ($m->phone) · <span class="tabular">{{ $m->phone }}</span>@endif</p>
                        <p class="text-sm text-steel-600">Joined {{ $m->created_at->timezone('Asia/Manila')->format('j M Y') }}@isset($m->orders_count) · {{ $m->orders_count }} {{ $m->orders_count === 1 ? 'order' : 'orders' }}@endisset @if ($m->last_login_at)· last signed in {{ $m->last_login_at->timezone('Asia/Manila')->format('j M Y, g:i A') }}@if ($logins[$m->id]->place ?? null) from {{ $logins[$m->id]->place }}@endif @endif</p>
                        @php $socials = collect(\App\Support\Members::SOCIALS)->filter(fn ($s, $f) => $m->{$f}); @endphp
                        @if ($socials->isNotEmpty())
                            <p class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                                @foreach ($socials as $field => [$label])
                                    <a href="{{ $m->{$field} }}" target="_blank" rel="noopener noreferrer nofollow" class="inline-flex items-center gap-1 underline"><x-icon :name="['facebook_url' => 'facebook', 'youtube_url' => 'youtube', 'instagram_url' => 'instagram', 'tiktok_url' => 'tiktok'][$field]" class="size-4" /> {{ $label }}</a>
                                @endforeach
                            </p>
                        @endif
                    </div>
                    </div>
                    <form method="post" action="/admin/members/{{ $m->id }}{{ $q !== '' ? '?q='.rawurlencode($q) : '' }}">
                        @csrf
                        @if ($m->status === 'active')
                            <button type="submit" name="action" value="disable" class="{{ \App\Support\Ui::button('secondary', 'sm') }}">Disable account</button>
                        @else
                            <button type="submit" name="action" value="enable" class="{{ \App\Support\Ui::button('secondary', 'sm') }}">Enable account</button>
                        @endif
                    </form>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $members->links() }}</div>
    @endif
</x-admin.layout>
