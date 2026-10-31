@php
    use App\Support\Ui;
    $input = 'min-h-12 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3.5 text-base text-steel-950 placeholder:text-steel-600 focus:border-steel-900 aria-[invalid=true]:border-danger-700 aria-[invalid=true]:bg-danger-50/40';
    $check = 'mt-1 size-5 shrink-0 rounded border-steel-500';
    $err = fn ($k) => $errors->first($k) ?: null;
    $aria = fn ($id, $k) => $err($k) ? 'aria-invalid=true aria-describedby='.$id.'-error' : '';
@endphp
<x-site-layout title="Create an account" path="/signup" :noindex="true" :reviews-above-footer="false">
    <x-container class="py-10 sm:py-14">
        <div class="mx-auto max-w-xl">
            <h1 class="text-3xl font-extrabold text-steel-950 sm:text-4xl">Create your account</h1>
            <p class="mt-3 text-lg text-steel-700">See your orders, check out faster, and get our promos if you like.</p>
            <div class="mt-6 space-y-4">@include('partials.account-flash')</div>

            <form method="post" action="/signup" novalidate class="mt-6 space-y-5 rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6 sm:p-8">
                @csrf
                @if ($next)<input type="hidden" name="next" value="{{ $next }}">@endif
                <div aria-hidden="true" class="absolute -left-[9999px] h-px w-px overflow-hidden"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off" value=""></label></div>

                <x-form-field id="s-name" label="Your name" :error="$err('name')" required>
                    <input id="s-name" name="name" type="text" autocomplete="name" value="{{ old('name') }}" maxlength="100" required {{ $aria('s-name', 'name') }} class="{{ $input }}">
                </x-form-field>
                <x-form-field id="s-email" label="Email" hint="You sign in with this email." :error="$err('email')" required>
                    <input id="s-email" name="email" type="email" autocomplete="email" spellcheck="false" value="{{ old('email') }}" maxlength="254" required aria-describedby="s-email-hint{{ $err('email') ? ' s-email-error' : '' }}" @if ($err('email')) aria-invalid="true" @endif class="{{ $input }}">
                </x-form-field>
                <x-form-field id="s-phone" label="Mobile or phone (optional)" hint="Filled in for you at checkout." :error="$err('phone')">
                    <input id="s-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" value="{{ old('phone') }}" maxlength="30" aria-describedby="s-phone-hint{{ $err('phone') ? ' s-phone-error' : '' }}" @if ($err('phone')) aria-invalid="true" @endif class="{{ $input }} tabular">
                </x-form-field>
                <x-form-field id="s-password" label="Password" hint="At least 10 characters. A short sentence is easy to remember." :error="$err('password')" required>
                    <input id="s-password" name="password" type="password" autocomplete="new-password" maxlength="200" required aria-describedby="s-password-hint{{ $err('password') ? ' s-password-error' : '' }}" @if ($err('password')) aria-invalid="true" @endif class="{{ $input }}">
                </x-form-field>
                <x-form-field id="s-password2" label="Type the password again" required>
                    <input id="s-password2" name="password_confirmation" type="password" autocomplete="new-password" maxlength="200" required class="{{ $input }}">
                </x-form-field>

                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="newsletter" value="1" @checked(old('newsletter') === '1') class="{{ $check }}">
                    <span class="text-steel-800">Email me JFL Solar promos and news (optional). You can unsubscribe at any time.</span>
                </label>
                <div>
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" name="agree" value="1" required @if ($err('agree')) aria-invalid="true" aria-describedby="s-agree-error" @endif class="{{ $check }}">
                        <span class="text-steel-800">I agree to the <a href="/terms" class="underline" target="_blank" rel="noopener">terms of use</a> and <a href="/privacy" class="underline" target="_blank" rel="noopener">privacy notice</a>.</span>
                    </label>
                    @if ($err('agree'))<p id="s-agree-error" class="mt-1.5 text-sm font-semibold text-danger-700">{{ $err('agree') }}</p>@endif
                </div>

                <button type="submit" class="{{ Ui::button('primary', 'lg', 'w-full') }}">Create account</button>
                <p class="text-center text-steel-700">Already have an account? <a href="/login{{ $next ? '?next='.rawurlencode($next) : '' }}" class="font-semibold underline">Sign in</a></p>
            </form>
        </div>
    </x-container>
</x-site-layout>
