@php
    use App\Support\Ui;
    $input = 'min-h-12 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3.5 text-base text-steel-950 placeholder:text-steel-600 focus:border-steel-900 aria-[invalid=true]:border-danger-700';
@endphp
<x-site-layout title="Choose a new password" path="/reset-password" :noindex="true" :reviews-above-footer="false">
    <x-container class="py-10 sm:py-14">
        <div class="mx-auto max-w-xl">
            <h1 class="text-3xl font-extrabold text-steel-950 sm:text-4xl">Choose a new password</h1>
            <form method="post" action="/reset-password" class="mt-6 space-y-5 rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6 sm:p-8">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <x-form-field id="r-email" label="Email">
                    <input id="r-email" name="email" type="email" autocomplete="email" value="{{ $email }}" maxlength="254" required class="{{ $input }}">
                </x-form-field>
                <x-form-field id="r-password" label="New password" hint="At least 10 characters." :error="$errors->first('password') ?: null">
                    <input id="r-password" name="password" type="password" autocomplete="new-password" maxlength="200" required aria-describedby="r-password-hint{{ $errors->has('password') ? ' r-password-error' : '' }}" @if ($errors->has('password')) aria-invalid="true" @endif class="{{ $input }}">
                </x-form-field>
                <x-form-field id="r-password2" label="Type it again">
                    <input id="r-password2" name="password_confirmation" type="password" autocomplete="new-password" maxlength="200" required class="{{ $input }}">
                </x-form-field>
                <button type="submit" class="{{ Ui::button('primary', 'lg', 'w-full') }}">Save new password</button>
            </form>
        </div>
    </x-container>
</x-site-layout>
