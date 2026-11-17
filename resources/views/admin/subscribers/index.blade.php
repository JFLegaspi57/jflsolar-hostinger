@php $tab = 'inline-flex min-h-11 items-center gap-1.5 rounded-full border border-steel-300 px-4 text-sm font-semibold text-steel-800 aria-[current=page]:border-steel-900 aria-[current=page]:bg-steel-900 aria-[current=page]:text-white'; @endphp
<x-admin.layout title="Subscribers">
    @if (session('status'))<p role="status" class="mb-4 rounded-[var(--radius-control)] border border-[#b7e2c6] bg-ok-50 px-3 py-2 text-sm font-semibold text-ok-700">{{ session('status') }}</p>@endif
    <x-admin.page-title title="Subscribers" intro="People who asked for JFL Solar promos and news (ticked at account sign-up or on their account page). Download the list and send your emails from your usual email or newsletter tool. Include each person’s unsubscribe link (it is in the download)." />
    <dl class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4" aria-label="Subscriber totals">
        @foreach ([['Total subscribers', $counts['active']], ['Unsubscribed', $counts['left']], ['All sign-ups', $counts['active'] + $counts['left']], ['Joined in the last 30 days', $counts['new30']]] as $i => [$label, $value])
            <div class="rounded-[var(--radius-panel)] border {{ $i === 0 ? 'border-gold-500 bg-gold-50' : 'border-steel-200 bg-white' }} p-4">
                <dt class="text-sm font-semibold text-steel-700">{{ $label }}</dt>
                <dd class="mt-1 text-3xl font-extrabold text-steel-950 tabular">{{ number_format($value) }}</dd>
            </div>
        @endforeach
    </dl>
    <section aria-labelledby="welcome-h" class="mb-5 border-l-4 border-gold-500 bg-gold-50 p-4 text-steel-800" style="border-radius: .75rem">
        <h2 id="welcome-h" class="font-bold text-steel-950">Automatic welcome email</h2>
        @if ($welcome['ready'])
            <p class="mt-1">Every new member gets “Thank you from the whole JFL Solar team + a quick tour of our website” automatically, right after signing up, from <strong>{{ $welcome['from'] ?? 'the website’s mailbox' }}</strong>{{ $welcome['gmail'] ? ' (Gmail)' : '' }}. Received so far (automatically, or by hand from Gmail): <strong class="tabular">{{ $welcome['sent'] }}</strong>.</p>
            @if ($welcome['error'])
                <p class="mt-2 text-sm text-steel-800"><strong>Last sending problem</strong>@if ($welcome['errorAt'] ?? null) ({{ $welcome['errorAt'] }})@endif: <span class="break-words">{{ $welcome['error'] }}</span></p>
            @endif
            @if ($welcome['pending'])
                <form method="post" action="/admin/subscribers/welcome" class="mt-3">@csrf<button type="submit" class="{{ \App\Support\Ui::button('primary') }}">Send it to {{ $welcome['pending'] }} waiting {{ $welcome['pending'] === 1 ? 'member' : 'members' }}</button></form>
            @endif
        @else
            <p class="mt-1">The welcome email is ready, but the website’s mail server is not set up, so it can’t be sent yet.</p>
        @endif
        @if ($welcome['check'] ?? null)
            <form method="post" action="/admin/subscribers/mail-password" class="mt-3 flex flex-wrap items-end gap-2" autocomplete="off">
                @csrf
                <label class="grid gap-1 text-sm font-semibold text-steel-900">Mailbox password for {{ $welcome['check']['Username'] ?: 'the website mailbox' }}
                    <input type="password" name="password" autocomplete="new-password" required maxlength="200" class="min-h-11 rounded-[var(--radius-control)] border border-steel-500 px-3 font-normal" style="width: 16rem">
                </label>
                <button type="submit" class="{{ \App\Support\Ui::button('primary') }}">Save and test</button>
            </form>
            <p class="mt-1 text-xs text-steel-700">The same password you use for this mailbox in Hostinger → Emails. It is saved encrypted and never shown again. The test signs in to the mail server without sending any email.</p>
            <details class="mt-3 text-sm">
                <summary class="cursor-pointer font-semibold">Check the mail settings</summary>
                <dl class="mt-2 grid gap-1" style="grid-template-columns: max-content 1fr; column-gap: 1rem">
                    @foreach ($welcome['check'] as $k => $v)<dt class="font-semibold">{{ $k }}</dt><dd class="break-words">{{ $v }}</dd>@endforeach
                </dl>
                <form method="post" action="/admin/subscribers/mail-test" class="mt-2">@csrf<button type="submit" class="{{ \App\Support\Ui::button('secondary') }}">Test the mail login (sends nothing)</button></form>
            </details>
        @endif
        @unless ($welcome['gmail'])
            <p class="mt-2 text-sm">To send it from your Gmail account instead, add <code>GMAIL_USERNAME</code> (the Gmail address) and <code>GMAIL_APP_PASSWORD</code> (a Google app password) to the server settings, then reload the settings.</p>
        @endunless
    </section>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <nav aria-label="Filter subscribers" class="flex flex-wrap gap-2">
            <a href="/admin/subscribers" @if ($show === 'active') aria-current="page" @endif class="{{ $tab }}">Subscribed <span class="tabular">({{ $counts['active'] }})</span></a>
            <a href="/admin/subscribers?show=left" @if ($show === 'left') aria-current="page" @endif class="{{ $tab }}">Unsubscribed <span class="tabular">({{ $counts['left'] }})</span></a>
        </nav>
        <a href="/admin/subscribers/export" class="{{ \App\Support\Ui::button('primary') }}">Download list (CSV)</a>
    </div>
    @if (! $rows || $rows->isEmpty())
        <p class="rounded-[var(--radius-panel)] border border-dashed border-steel-300 bg-white p-8 text-center text-steel-700">{{ $show === 'active' ? 'No subscribers yet.' : 'No one has unsubscribed.' }}</p>
    @else
        <ul class="divide-y divide-steel-200 rounded-[var(--radius-panel)] border border-steel-200 bg-white">
            @foreach ($rows as $r)
                <li class="flex flex-wrap items-baseline justify-between gap-2 p-4">
                    <span class="font-semibold text-steel-950 break-all">{{ $r->email }}@if ($r->name) <span class="font-normal text-steel-700">· {{ $r->name }}</span>@endif</span>
                    <span class="text-sm text-steel-600">{{ \App\Support\Newsletter::SOURCES[$r->source] ?? $r->source }} · {{ \Illuminate\Support\Carbon::parse($show === 'active' ? $r->subscribed_at : $r->unsubscribed_at)->timezone('Asia/Manila')->format('j M Y') }}</span>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $rows->links() }}</div>
    @endif
</x-admin.layout>
