{{-- "Continue with Google / Facebook" (only the providers whose keys are set in Admin). $mode: 'signup' or 'login'. --}}
@php $providers = \App\Support\SocialLogin::active(); @endphp
@if ($providers)
    <form method="get" action="/auth/start" class="mt-6 rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6 sm:p-8">
        @if ($next ?? null)<input type="hidden" name="next" value="{{ $next }}">@endif
        <div class="grid gap-3">
            @if (in_array('google', $providers, true))
                <button type="submit" name="provider" value="google" class="flex min-h-12 w-full items-center justify-center gap-3 rounded-[var(--radius-control)] border border-steel-400 bg-white px-4 text-base font-semibold text-steel-950 hover:bg-steel-50">
                    <svg aria-hidden="true" width="20" height="20" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
                    {{ $mode === 'signup' ? 'Sign up with Google' : 'Continue with Google' }}
                </button>
            @endif
            @if (in_array('facebook', $providers, true))
                <button type="submit" name="provider" value="facebook" class="flex min-h-12 w-full items-center justify-center gap-3 rounded-[var(--radius-control)] px-4 text-base font-semibold text-white" style="background:#1877F2">
                    <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24"><path fill="#fff" d="M24 12.07C24 5.41 18.63 0 12 0S0 5.41 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.95.93-1.95 1.89v2.26h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07z"/></svg>
                    {{ $mode === 'signup' ? 'Sign up with Facebook' : 'Continue with Facebook' }}
                </button>
            @endif
        </div>
        @if ($mode === 'signup')
            <label class="mt-4 flex cursor-pointer items-start gap-3">
                <input type="checkbox" name="newsletter" value="1" class="mt-1 size-5 shrink-0 rounded border-steel-500">
                <span class="text-steel-800">Email me JFL Solar promos and news (optional).</span>
            </label>
        @endif
        <p class="mt-3 text-sm text-steel-700">{{ $mode === 'signup' ? 'One click, no password to remember.' : 'For accounts made with Google or Facebook, or with the same email.' }} By continuing you agree to our <a href="/terms" class="underline" target="_blank" rel="noopener">terms of use</a> and <a href="/privacy" class="underline" target="_blank" rel="noopener">privacy notice</a>. We only receive your name and email address.</p>
    </form>
    <p class="my-5 flex items-center gap-3 text-sm font-semibold text-steel-600" aria-hidden="true"><span class="h-px flex-1 bg-steel-300"></span>{{ $mode === 'signup' ? 'or sign up with your email' : 'or sign in with your email' }}<span class="h-px flex-1 bg-steel-300"></span></p>
@endif
