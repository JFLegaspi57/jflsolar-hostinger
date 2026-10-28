@php
    $input = 'min-h-12 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3.5 text-base text-steel-950 focus:border-steel-900 aria-[invalid=true]:border-danger-700';
@endphp
<x-admin.bare title="Choose your password">
    <h1 class="mt-8 text-2xl font-extrabold text-steel-950">{{ $forced ? 'Choose your own password' : 'Change your password' }}</h1>
    <p class="mt-2 text-steel-600">
        @if ($forced)
            You signed in with a temporary password. Choose a new one that only you know.
        @else
            Signed in as {{ auth()->user()->email }}.
        @endif
    </p>
    <form method="post" action="/admin/password" class="mt-6 space-y-4">
        @csrf
        @unless ($forced)
            <div>
                <label for="current_password" class="mb-1.5 block font-semibold text-steel-950">Current password</label>
                <input id="current_password" name="current_password" type="password" autocomplete="current-password" required class="{{ $input }}" @error('current_password') aria-invalid="true" aria-describedby="current-error" @enderror>
                @error('current_password')<p id="current-error" class="mt-1 text-sm font-semibold text-danger-700">{{ $message }}</p>@enderror
            </div>
        @endunless
        <div>
            <label for="password" class="mb-1.5 block font-semibold text-steel-950">New password</label>
            <p id="password-hint" class="-mt-0.5 mb-1.5 text-sm text-steel-600">At least {{ \App\Support\Passwords::MIN }} characters. A short sentence works well, e.g. “green panels on the roof”.</p>
            <input id="password" name="password" type="password" autocomplete="new-password" required class="{{ $input }}" aria-describedby="password-hint @error('password') password-error @enderror" @error('password') aria-invalid="true" @enderror>
            @error('password')<p id="password-error" class="mt-1 text-sm font-semibold text-danger-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="mb-1.5 block font-semibold text-steel-950">New password again</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="{{ $input }}">
        </div>
        <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-[var(--radius-control)] bg-steel-900 px-4 font-semibold text-white hover:bg-steel-800">Save password</button>
    </form>
    @unless ($forced)
        <p class="mt-6 text-sm"><a href="/admin" class="font-semibold text-steel-900 underline">← Back to the admin</a></p>
    @else
        <form method="post" action="/admin/logout" class="mt-6">@csrf<button type="submit" class="text-sm font-semibold text-steel-900 underline">Sign out</button></form>
    @endunless
</x-admin.bare>
