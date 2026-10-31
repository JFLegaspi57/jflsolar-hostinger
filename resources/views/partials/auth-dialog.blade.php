{{--
    "Log in / Create account" pop-up, opened from the header (any element with data-auth-open).
    Without JavaScript the same links simply open the /login and /signup pages.
    After signing in or up, the visitor returns to the page they were on.
--}}
@php
    use App\Support\Ui;
    $here = request()->getRequestUri();
    $next = strlen($here) <= 200 && ! request()->is('login', 'signup', 'account', 'forgot-password', 'reset-password/*') ? $here : null;
    $input = 'min-h-12 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3.5 text-base text-steel-950 placeholder:text-steel-600 focus:border-steel-900';
    $label = 'mb-1.5 block font-semibold text-steel-950';
    $tab = 'flex-1 min-h-12 border-b-2 px-3 font-semibold text-steel-700';
@endphp
<style>#auth-dialog [role=tab] { border-color: transparent; } #auth-dialog [role=tab][aria-selected=true] { border-color: var(--color-gold-500); color: var(--color-steel-950); }</style>
<dialog id="auth-dialog" aria-labelledby="auth-title" data-backdrop-close class="rounded-[var(--radius-panel)] bg-white text-steel-900 shadow-2xl"
    style="margin: auto; width: min(30rem, calc(100vw - 1.5rem)); max-height: calc(100dvh - 1.5rem); border: 0; padding: 0; overflow: auto">
    <div class="flex items-center justify-between border-b border-steel-200 px-5 py-3">
        <h2 id="auth-title" class="font-display text-lg font-bold">Your JFL Solar account</h2>
        <button type="button" data-close-dialog class="inline-flex size-11 items-center justify-center rounded-[var(--radius-control)] hover:bg-steel-100">
            <x-icon name="x" class="size-6" /><span class="sr-only">Close</span>
        </button>
    </div>
    <div role="tablist" aria-label="Log in or create an account" class="flex border-b border-steel-200 px-5">
        <button type="button" role="tab" id="auth-tab-login" aria-controls="auth-panel-login" aria-selected="true" data-auth-tab="login" class="{{ $tab }}">Log in</button>
        <button type="button" role="tab" id="auth-tab-signup" aria-controls="auth-panel-signup" aria-selected="false" tabindex="-1" data-auth-tab="signup" class="{{ $tab }}">Create account</button>
    </div>

    <div id="auth-panel-login" role="tabpanel" aria-labelledby="auth-tab-login" class="p-5 sm:p-6">
        <form method="post" action="/login" class="space-y-4">
            @csrf
            @if ($next)<input type="hidden" name="next" value="{{ $next }}">@endif
            <div><label for="ad-email" class="{{ $label }}">Email</label><input id="ad-email" name="email" type="email" autocomplete="email" spellcheck="false" maxlength="254" required class="{{ $input }}"></div>
            <div><label for="ad-password" class="{{ $label }}">Password</label><input id="ad-password" name="password" type="password" autocomplete="current-password" maxlength="200" required class="{{ $input }}"></div>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <label class="flex cursor-pointer items-center gap-2 text-steel-800"><input type="checkbox" name="remember" value="1" class="size-5 rounded border-steel-500"> Keep me signed in</label>
                <a href="/forgot-password" class="font-semibold underline">Forgot password?</a>
            </div>
            <button type="submit" class="{{ Ui::button('primary', 'lg', 'w-full') }}">Log in</button>
            <p class="text-center text-steel-700">New here? <button type="button" data-auth-tab="signup" class="font-semibold underline">Create an account</button></p>
        </form>
    </div>

    <div id="auth-panel-signup" role="tabpanel" aria-labelledby="auth-tab-signup" hidden class="p-5 sm:p-6">
        <p class="mb-4 text-steel-700">See your orders, check out faster and join the forum.</p>
        <form method="post" action="/signup" class="space-y-4">
            @csrf
            @if ($next)<input type="hidden" name="next" value="{{ $next }}">@endif
            <div aria-hidden="true" class="absolute -left-[9999px] h-px w-px overflow-hidden"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off" value=""></label></div>
            <div><label for="as-name" class="{{ $label }}">Your name</label><input id="as-name" name="name" type="text" autocomplete="name" maxlength="100" required class="{{ $input }}"></div>
            <div><label for="as-email" class="{{ $label }}">Email</label><input id="as-email" name="email" type="email" autocomplete="email" spellcheck="false" maxlength="254" required class="{{ $input }}"></div>
            <div><label for="as-phone" class="{{ $label }}">Mobile or phone <span class="text-sm font-normal text-steel-600">(optional)</span></label><input id="as-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" maxlength="30" class="{{ $input }} tabular"></div>
            <div><label for="as-password" class="{{ $label }}">Password</label><p id="as-password-hint" class="-mt-1 mb-1.5 text-sm text-steel-600">At least 10 characters. A short sentence is easy to remember.</p><input id="as-password" name="password" type="password" autocomplete="new-password" maxlength="200" required aria-describedby="as-password-hint" class="{{ $input }}"></div>
            <div><label for="as-password2" class="{{ $label }}">Type the password again</label><input id="as-password2" name="password_confirmation" type="password" autocomplete="new-password" maxlength="200" required class="{{ $input }}"></div>
            <label class="flex cursor-pointer items-start gap-3"><input type="checkbox" name="newsletter" value="1" class="mt-1 size-5 shrink-0 rounded border-steel-500"><span class="text-steel-800">Email me JFL Solar promos and news (optional)</span></label>
            <label class="flex cursor-pointer items-start gap-3"><input type="checkbox" name="agree" value="1" required class="mt-1 size-5 shrink-0 rounded border-steel-500"><span class="text-steel-800">I agree to the <a href="/terms" class="underline" target="_blank" rel="noopener">terms of use</a> and <a href="/privacy" class="underline" target="_blank" rel="noopener">privacy notice</a>.</span></label>
            <button type="submit" class="{{ Ui::button('primary', 'lg', 'w-full') }}">Create account</button>
            <p class="text-center text-steel-700">Already have an account? <button type="button" data-auth-tab="login" class="font-semibold underline">Log in</button></p>
        </form>
    </div>
</dialog>
<script>
(() => {
    const dialog = document.getElementById('auth-dialog');
    const show = (name) => {
        for (const t of ['login', 'signup']) {
            const on = t === name;
            document.getElementById('auth-panel-' + t).hidden = !on;
            const tab = document.getElementById('auth-tab-' + t);
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
            tab.tabIndex = on ? 0 : -1;
        }
        document.querySelector('#auth-panel-' + name + ' input:not([type=hidden]):not([tabindex="-1"])')?.focus();
    };
    document.addEventListener('click', (e) => {
        const opener = e.target.closest('[data-auth-open]');
        if (opener && dialog?.showModal) {
            e.preventDefault();
            opener.closest('dialog')?.close();   // e.g. the mobile menu
            dialog.showModal();
            show(opener.dataset.authOpen || 'login');
            return;
        }
        const tab = e.target.closest('[data-auth-tab]');
        if (tab && dialog?.contains(tab)) {
            e.preventDefault();
            show(tab.dataset.authTab);
        }
    });
    // Arrow keys move between the two tabs.
    dialog?.querySelector('[role=tablist]')?.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
            const next = document.getElementById('auth-tab-signup').getAttribute('aria-selected') === 'true' ? 'login' : 'signup';
            show(next);
            document.getElementById('auth-tab-' + next).focus();
        }
    });
})();
</script>
