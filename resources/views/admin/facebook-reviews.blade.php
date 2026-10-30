@php
    $input = 'min-h-12 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3.5 text-base text-steel-950 focus:border-steel-900';
    $when = fn (?string $iso) => $iso ? \Carbon\CarbonImmutable::parse($iso)->setTimezone('Asia/Manila')->format('M j, Y g:i A') : '—';
    $card = 'rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6';
@endphp
<x-admin.layout title="Facebook reviews">
    <x-admin.page-title title="Facebook reviews"
        intro="Connect your Facebook page once, and the website checks it for new recommendations about every 6 hours. Only positive reviews with written text are added to the website." />

    <section class="{{ $card }}" aria-labelledby="fb-status">
        <h2 id="fb-status" class="text-lg font-bold text-steel-950">Status</h2>
        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
            <div><dt class="text-sm text-steel-600">Facebook page</dt><dd class="font-semibold text-steel-950">{{ $connection ? $connection['page_name'] : 'Not connected' }}</dd></div>
            <div><dt class="text-sm text-steel-600">Connection key</dt><dd class="font-semibold text-steel-950">{{ $connection ? ($connection['never_expires'] ? 'Does not expire' : 'May expire — reconnect with the app ID and secret for a permanent key') : '—' }}</dd></div>
            <div><dt class="text-sm text-steel-600">Last checked</dt><dd class="font-semibold text-steel-950">{{ $when($data['fetched_at'] ?? null) }}</dd></div>
            <div><dt class="text-sm text-steel-600">Reviews on the website</dt><dd class="font-semibold text-steel-950 tabular">{{ $total }} <span class="font-normal text-steel-600">({{ $fromFacebook }} from the last Facebook check)</span></dd></div>
        </dl>
        @if (! empty($data['error']))
            <p class="mt-4 rounded-[var(--radius-control)] border border-[#f0d39a] bg-warn-50 px-4 py-3 text-sm text-warn-800"><strong>Last problem ({{ $when($data['error_at'] ?? null) }}):</strong> {{ $data['error'] }}</p>
        @endif
        @if ($connection)
            <div class="mt-6 flex flex-wrap gap-3">
                <form method="post" action="/admin/facebook-reviews/sync">@csrf
                    <button type="submit" class="{{ \App\Support\Ui::button('dark', 'md') }}">Check for new reviews now</button>
                </form>
                <form method="post" action="/admin/facebook-reviews/disconnect">@csrf
                    <button type="submit" class="{{ \App\Support\Ui::button('secondary', 'md') }}">Disconnect</button>
                </form>
            </div>
        @endif
    </section>

    <section class="{{ $card }} mt-8" aria-labelledby="fb-connect">
        <h2 id="fb-connect" class="text-lg font-bold text-steel-950">{{ $connection ? 'Reconnect' : 'Connect your Facebook page' }}</h2>
        <ol class="mt-4 list-decimal space-y-3 pl-5 text-steel-800">
            <li>Go to <a href="https://developers.facebook.com/apps" target="_blank" rel="noopener" class="font-semibold underline">developers.facebook.com/apps</a> (sign in with the Facebook account that manages the JFL Solar page). Select <strong>Create app</strong>, choose the option for managing a Page, and name it e.g. “JFL Solar website”.</li>
            <li>In the new app, open <strong>App settings → Basic</strong>. Copy the <strong>App ID</strong> and the <strong>App secret</strong> (select “Show”).</li>
            <li>Open the <a href="https://developers.facebook.com/tools/explorer/" target="_blank" rel="noopener" class="font-semibold underline">Graph API Explorer</a>. Choose your new app, then under <strong>Permissions</strong> add <code>pages_show_list</code>, <code>pages_read_engagement</code> and <code>pages_read_user_content</code>. Select <strong>Generate Access Token</strong> and allow access to the JFL Solar page.</li>
            <li>Copy the long <strong>Access Token</strong> and paste everything below, then select <strong>Connect</strong>.</li>
        </ol>
        <p class="mt-4 text-sm text-steel-600">The website swaps your token for a permanent page key, saves only that key (encrypted), and forgets the app secret. Facebook only lets the website <em>read</em> reviews — it cannot post or change anything.</p>

        <form method="post" action="/admin/facebook-reviews/connect" class="mt-6 grid gap-4 sm:grid-cols-2" autocomplete="off">
            @csrf
            <div class="sm:col-span-2">
                <label for="fb-token" class="mb-1.5 block font-semibold text-steel-950">Access token <span class="text-sm font-normal text-steel-600">(required)</span></label>
                <input id="fb-token" name="token" type="password" required maxlength="1000" autocomplete="off" spellcheck="false" class="{{ $input }}">
            </div>
            <div>
                <label for="fb-app-id" class="mb-1.5 block font-semibold text-steel-950">App ID</label>
                <input id="fb-app-id" name="app_id" inputmode="numeric" maxlength="40" autocomplete="off" class="{{ $input }}">
            </div>
            <div>
                <label for="fb-app-secret" class="mb-1.5 block font-semibold text-steel-950">App secret</label>
                <input id="fb-app-secret" name="app_secret" type="password" maxlength="100" autocomplete="off" spellcheck="false" class="{{ $input }}">
            </div>
            <p class="text-sm text-steel-600 sm:col-span-2">Without the app ID and secret it still works, but Facebook's token stops working after about an hour, and you would need to connect again.</p>
            <div class="sm:col-span-2">
                <button type="submit" class="{{ \App\Support\Ui::button('primary', 'md') }}">Connect</button>
            </div>
        </form>
    </section>
</x-admin.layout>
