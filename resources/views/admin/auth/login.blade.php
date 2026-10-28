@php
    $input = 'min-h-12 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3.5 text-base text-steel-950 focus:border-steel-900';
@endphp
<x-admin.bare title="Sign in">
    <h1 class="mt-8 text-2xl font-extrabold text-steel-950">Staff sign-in</h1>
    <p class="mt-2 text-steel-600">For JFL Solar staff who manage the website.</p>
    <x-admin.flash class="mt-5 text-sm" />

    @if ($noUsers)
        <div role="status" class="mt-6 rounded-[var(--radius-control)] border-2 border-dashed border-gold-600 bg-gold-50 p-4 text-steel-900">
            <p class="font-bold">No staff accounts yet.</p>
            <p class="mt-1 text-sm">Create the first owner account with the <a href="/admin/recover" class="font-semibold underline">set-up and recovery page</a>.</p>
        </div>
    @else
        <form method="post" action="/admin/login" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="mb-1.5 block font-semibold text-steel-950">Email</label>
                <input id="email" name="email" type="email" autocomplete="username" required value="{{ old('email') }}" class="{{ $input }}" @if (! old('email')) autofocus @endif>
            </div>
            <div>
                <label for="password" class="mb-1.5 block font-semibold text-steel-950">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required class="{{ $input }}" @if (old('email')) autofocus @endif>
            </div>
            <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-[var(--radius-control)] bg-steel-900 px-4 font-semibold text-white hover:bg-steel-800">Sign in</button>
        </form>
        <p class="mt-4 text-sm text-steel-600">Forgot your password? Ask an owner to reset it from the Staff page. Owners who are locked out can use the <a href="/admin/recover" class="font-semibold text-steel-900 underline">recovery page</a>.</p>
    @endif
    <p class="mt-8 text-sm"><a href="/" class="font-semibold text-steel-900 underline">← Back to the website</a></p>
</x-admin.bare>
