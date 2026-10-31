@php
    use App\Support\Ui;
    $input = 'min-h-12 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3.5 text-base text-steel-950 placeholder:text-steel-600 focus:border-steel-900 aria-[invalid=true]:border-danger-700';
@endphp
<x-site-layout title="Forgot your password" path="/forgot-password" :noindex="true" :reviews-above-footer="false">
    <x-container class="py-10 sm:py-14">
        <div class="mx-auto max-w-xl">
            <h1 class="text-3xl font-extrabold text-steel-950 sm:text-4xl">Forgot your password?</h1>
            <p class="mt-3 text-lg text-steel-700">Enter your account email and we’ll send you a link to choose a new password.</p>
            <div class="mt-6 space-y-4">@include('partials.account-flash')</div>
            <form method="post" action="/forgot-password" class="mt-6 space-y-5 rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6 sm:p-8">
                @csrf
                <x-form-field id="f-email" label="Email" :error="$errors->first('email') ?: null">
                    <input id="f-email" name="email" type="email" autocomplete="email" spellcheck="false" value="{{ old('email') }}" maxlength="254" required @if ($errors->has('email')) aria-invalid="true" aria-describedby="f-email-error" @endif class="{{ $input }}">
                </x-form-field>
                <button type="submit" class="{{ Ui::button('primary', 'lg', 'w-full') }}">Email me a reset link</button>
                <p class="text-center text-steel-700"><a href="/login" class="font-semibold underline">Back to sign in</a></p>
            </form>
        </div>
    </x-container>
</x-site-layout>
