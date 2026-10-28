@php
    $input = 'min-h-12 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3.5 text-base text-steel-950 focus:border-steel-900 aria-[invalid=true]:border-danger-700';
    $field = function (string $name) use ($errors) {
        return $errors->has($name) ? 'aria-invalid=true aria-describedby='.$name.'-error' : '';
    };
@endphp
<x-admin.bare :title="$noUsers ? 'Create the first owner' : 'Owner recovery'">
    <h1 class="mt-8 text-2xl font-extrabold text-steel-950">{{ $noUsers ? 'Create the first owner' : 'Owner recovery' }}</h1>
    <x-admin.flash class="mt-5 text-sm" />

    @if ($state !== 'ready')
        <div class="mt-4 space-y-3 text-steel-700">
            <p>This page works only for someone who can open the website's files in Hostinger. That proves you control the website, without needing email.</p>
            @if ($state === 'stale')
                <p role="alert" class="rounded-[var(--radius-control)] border border-[#f0d39a] bg-warn-50 px-3 py-2 text-warn-800">The recovery file is older than 24 hours. Delete it and create a new one.</p>
            @elseif ($state === 'short')
                <p role="alert" class="rounded-[var(--radius-control)] border border-[#f0d39a] bg-warn-50 px-3 py-2 text-warn-800">The phrase in the recovery file is too short. Use at least 12 characters.</p>
            @endif
            <ol class="list-decimal space-y-1.5 pl-5">
                <li>In Hostinger, open <strong>File Manager</strong> for this website.</li>
                <li>Go into the folder <code class="rounded bg-steel-100 px-1">jflsolar-app/storage/app</code>. The <code class="rounded bg-steel-100 px-1">jflsolar-app</code> folder sits next to <code class="rounded bg-steel-100 px-1">public_html</code>, not inside it.</li>
                <li>Create a new file named <code class="rounded bg-steel-100 px-1">admin-recovery.txt</code>.</li>
                <li>Type a phrase of at least 12 characters into it (only you should know it), and save.</li>
                <li>Reload this page within 24 hours.</li>
            </ol>
            <p><a href="/admin/recover" class="font-semibold text-steel-900 underline">Reload this page</a></p>
        </div>
    @else
        <p class="mt-2 text-steel-600">
            {{ $noUsers ? 'Set up the owner account that manages this website and its staff.' : 'Set a new password for an owner account.' }}
            The recovery file is deleted once this succeeds.
        </p>
        <form method="post" action="/admin/recover" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="phrase" class="mb-1.5 block font-semibold text-steel-950">Phrase from the recovery file</label>
                <input id="phrase" name="phrase" type="password" autocomplete="off" required class="{{ $input }}" {{ $field('phrase') }}>
                @error('phrase')<p id="phrase-error" class="mt-1 text-sm font-semibold text-danger-700">{{ $message }}</p>@enderror
            </div>
            @if ($noUsers)
                <div>
                    <label for="name" class="mb-1.5 block font-semibold text-steel-950">Your name</label>
                    <input id="name" name="name" autocomplete="name" required value="{{ old('name') }}" class="{{ $input }}" {{ $field('name') }}>
                    @error('name')<p id="name-error" class="mt-1 text-sm font-semibold text-danger-700">{{ $message }}</p>@enderror
                </div>
            @endif
            <div>
                <label for="email" class="mb-1.5 block font-semibold text-steel-950">{{ $noUsers ? 'Your email' : 'Owner email' }}</label>
                <input id="email" name="email" type="email" autocomplete="username" required value="{{ old('email') }}" class="{{ $input }}" {{ $field('email') }}>
                @error('email')<p id="email-error" class="mt-1 text-sm font-semibold text-danger-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="mb-1.5 block font-semibold text-steel-950">New password</label>
                <p id="password-hint" class="-mt-0.5 mb-1.5 text-sm text-steel-600">At least {{ \App\Support\Passwords::MIN }} characters.</p>
                <input id="password" name="password" type="password" autocomplete="new-password" required class="{{ $input }}" aria-describedby="password-hint @error('password') password-error @enderror" @error('password') aria-invalid="true" @enderror>
                @error('password')<p id="password-error" class="mt-1 text-sm font-semibold text-danger-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="mb-1.5 block font-semibold text-steel-950">New password again</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="{{ $input }}">
            </div>
            <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-[var(--radius-control)] bg-steel-900 px-4 font-semibold text-white hover:bg-steel-800">{{ $noUsers ? 'Create owner account' : 'Reset password' }}</button>
        </form>
    @endif
    <p class="mt-8 text-sm"><a href="/admin/login" class="font-semibold text-steel-900 underline">← Back to sign-in</a></p>
</x-admin.bare>
