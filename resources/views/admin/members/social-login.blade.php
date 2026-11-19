@php $input = 'min-h-11 w-full rounded-[var(--radius-control)] border border-steel-400 bg-white px-3 text-base text-steel-950'; @endphp
<x-admin.layout title="Sign-up with Google & Facebook">
    <x-admin.page-title title="Sign-up with Google & Facebook" intro="Lets visitors create a member account, or sign in, with one click using their Google or Facebook account. Each button shows on the sign-up and sign-in pages once its two keys are saved here. Staff sign-in is not affected." />
    <p class="mb-5 text-sm"><a href="/admin/members" class="underline">← Members</a></p>

    @foreach ($info as $p => $i)
        <section class="mb-6 rounded-[var(--radius-panel)] border border-steel-200 bg-white p-5" @if ($i['on']) style="border-color:#16a34a;background:#f0fdf4" @endif aria-labelledby="sl-{{ $p }}">
            <h2 id="sl-{{ $p }}" class="text-lg font-bold text-steel-950">{{ $i['name'] }}: {{ $i['on'] ? 'ON' : 'OFF' }}</h2>
            @if ($p === 'google')
                <ol class="mt-2 list-decimal space-y-1 pl-5 text-sm text-steel-800">
                    <li>Open <a class="underline" href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">console.cloud.google.com/apis/credentials</a> and sign in with the JFL Solar Google account. Create a project named "JFL Solar website" if asked.</li>
                    <li>OAuth consent screen: User type <strong>External</strong>, app name "JFL Solar", your support email, then publish the app ("In production").</li>
                    <li>Credentials → Create credentials → <strong>OAuth client ID</strong> → Application type "Web application".</li>
                    <li>Under "Authorised redirect URIs" add: <code class="break-all">{{ $i['callback'] }}</code> (and the same with the other address of the website, e.g. with or without www, if visitors use both).</li>
                    <li>Copy the <strong>Client ID</strong> and <strong>Client secret</strong> into the boxes below.</li>
                </ol>
            @else
                <ol class="mt-2 list-decimal space-y-1 pl-5 text-sm text-steel-800">
                    <li>Open <a class="underline" href="https://developers.facebook.com/apps" target="_blank" rel="noopener">developers.facebook.com/apps</a> with your Facebook account → Create app → use case <strong>"Authenticate and request data from users with Facebook Login"</strong>, app name "JFL Solar".</li>
                    <li>In the use case, add the <strong>email</strong> permission (public_profile is included).</li>
                    <li>Facebook Login → Settings → "Valid OAuth Redirect URIs": <code class="break-all">{{ $i['callback'] }}</code></li>
                    <li>App settings → Basic: add the privacy policy URL <code>{{ url('/privacy') }}</code> and terms URL <code>{{ url('/terms') }}</code>, then switch the app to <strong>Live</strong>.</li>
                    <li>Copy the <strong>App ID</strong> and <strong>App secret</strong> (App settings → Basic) into the boxes below.</li>
                </ol>
            @endif
            <form method="post" action="/admin/social-login" class="mt-4 grid gap-3 sm:grid-cols-2" autocomplete="off">
                @csrf
                <input type="hidden" name="provider" value="{{ $p }}">
                <label class="grid gap-1 text-sm font-semibold text-steel-900">{{ $p === 'google' ? 'Client ID' : 'App ID' }}
                    <input name="app_id" value="{{ $i['id'] }}" maxlength="200" class="{{ $input }} font-normal">
                </label>
                <label class="grid gap-1 text-sm font-semibold text-steel-900">{{ $p === 'google' ? 'Client secret' : 'App secret' }}
                    <input type="password" name="app_secret" autocomplete="new-password" maxlength="200" placeholder="{{ $i['has_secret'] ? 'Saved (leave empty to keep it)' : '' }}" class="{{ $input }} font-normal">
                </label>
                <div class="sm:col-span-2 flex flex-wrap gap-2">
                    <button type="submit" class="{{ \App\Support\Ui::button('primary') }}">Save</button>
                    <span class="self-center text-xs text-steel-700">Saved encrypted, never shown again. To turn it off, empty the ID and save.</span>
                </div>
            </form>
        </section>
    @endforeach
</x-admin.layout>
