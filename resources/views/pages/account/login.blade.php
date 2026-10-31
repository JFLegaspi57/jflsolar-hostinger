@php
    use App\Support\Ui;
    $input = 'min-h-12 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3.5 text-base text-steel-950 placeholder:text-steel-600 focus:border-steel-900';
@endphp
<x-site-layout title="Sign in" path="/login" :noindex="true" :reviews-above-footer="false">
    <x-container class="py-10 sm:py-14">
        <div class="mx-auto max-w-xl">
            <h1 class="text-3xl font-extrabold text-steel-950 sm:text-4xl">Sign in</h1>
            <p class="mt-3 text-lg text-steel-700">Sign in to see your orders, check out faster and post in the forum.</p>
            <div class="mt-6 space-y-4">@include('partials.account-flash')</div>

            <form method="post" action="/login" class="mt-6 space-y-5 rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6 sm:p-8">
                @csrf
                @if ($next)<input type="hidden" name="next" value="{{ $next }}">@endif
                <x-form-field id="l-email" label="Email">
                    <input id="l-email" name="email" type="email" autocomplete="email" spellcheck="false" value="{{ old('email') }}" maxlength="254" required class="{{ $input }}">
                </x-form-field>
                <x-form-field id="l-password" label="Password">
                    <input id="l-password" name="password" type="password" autocomplete="current-password" maxlength="200" required class="{{ $input }}">
                </x-form-field>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <label class="flex cursor-pointer items-center gap-2 text-steel-800"><input type="checkbox" name="remember" value="1" class="size-5 rounded border-steel-500"> Keep me signed in</label>
                    <a href="/forgot-password" class="font-semibold underline">Forgot your password?</a>
                </div>
                <button type="submit" class="{{ Ui::button('primary', 'lg', 'w-full') }}">Sign in</button>
                <p class="text-center text-steel-700">New here? <a href="/signup{{ $next ? '?next='.rawurlencode($next) : '' }}" class="font-semibold underline">Create an account</a></p>
            </form>
            <p class="mt-4 text-sm text-steel-600">Staff: use the <a href="/admin/login" class="underline">staff sign-in</a>.</p>
        </div>
    </x-container>
</x-site-layout>
