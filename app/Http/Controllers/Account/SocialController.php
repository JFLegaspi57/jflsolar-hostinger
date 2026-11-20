<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Support\Input;
use App\Support\Members;
use App\Support\Newsletter;
use App\Support\SocialLogin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/** "Continue with Google / Facebook": signs a member in, or creates the account on first use. */
class SocialController extends Controller
{
    public function start(Request $request): RedirectResponse
    {
        $provider = Input::str($request, 'provider');
        if (! SocialLogin::enabled($provider)) {
            return redirect('/login')->with('account_error', 'That sign-in option is not available right now. Please use your email and password.');
        }
        $state = Str::random(40);
        $next = Input::str($request, 'next');
        $request->session()->put('social_login', [
            'state' => $state, 'provider' => $provider,
            'next' => preg_match('#^/(?![/\\\\])[A-Za-z0-9/_\-?=&.%]{0,200}$#D', $next) && ! str_starts_with($next, '/admin') ? $next : null,
            'newsletter' => $request->input('newsletter') === '1',
        ]);

        return redirect()->away(SocialLogin::authUrl($provider, $state));
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $flow = (array) $request->session()->pull('social_login', []);
        $name = SocialLogin::PROVIDERS[$provider] ?? 'that account';
        $fail = fn (string $msg) => redirect('/login')->with('account_error', $msg);
        if (! SocialLogin::enabled($provider) || ($flow['provider'] ?? '') !== $provider || ! hash_equals((string) ($flow['state'] ?? ''), Input::str($request, 'state'))) {
            return $fail('The sign-in with '.$name.' did not finish. Please try again.');
        }
        if ($request->filled('error') || Input::str($request, 'code') === '') {
            return $fail('You cancelled the sign-in with '.$name.'. You can try again, or use your email and password.');
        }
        $p = SocialLogin::profile($provider, Input::str($request, 'code'));
        if (! $p) {
            return $fail('We could not sign you in with '.$name.' right now. Please try again, or use your email and password.');
        }
        Members::ensureTables();

        $member = ($id = SocialLogin::memberFor($provider, $p['id'])) ? Member::query()->find($id) : null;
        $created = false;
        if (! $member) {
            if (! filter_var($p['email'], FILTER_VALIDATE_EMAIL)) {
                return $fail($name.' did not share a confirmed email address with us. Please sign up with your email instead.');
            }
            // Same email as an existing account: that account is linked and signed in (the email is confirmed by Google / Facebook).
            $member = Member::query()->where('email', $p['email'])->first();
            if ($member && $provider !== 'google') {
                // Only Google confirms that the email is verified, so a Facebook sign-in never takes over an existing account.
                return redirect('/login')->withInput(['email' => $p['email']])->with('account_error', 'There is already an account with this email. Please sign in with your email and password (or "Continue with Google").');
            }
            if (! $member) {
                $member = Member::query()->create([
                    'name' => mb_substr($p['name'] !== '' ? $p['name'] : Str::before($p['email'], '@'), 0, 100),
                    'email' => $p['email'],
                    'password' => Str::random(48),   // they sign in with Google / Facebook, or set a password later with "Forgot your password?"
                    'status' => 'active',
                ]);
                $created = true;
                if (! empty($flow['newsletter']) && class_exists(Newsletter::class)) {
                    Newsletter::subscribe($member->email, $member->name, 'signup');
                }
                \Illuminate\Support\defer(fn () => \App\Support\WelcomeEmail::send($member));
            }
            SocialLogin::link($member->id, $provider, $p['id'], $p['email']);
        }
        if (! $member->isActive()) {
            return $fail('This account is disabled. Please contact us.');
        }

        Auth::guard('member')->login($member, true);
        $request->session()->regenerate();
        Members::stampSession($member);
        $member->forceFill(['last_login_at' => now()])->save();
        Members::logActivity($member, 'login', null, $request->ip());
        \App\Support\Shop::mergeIntoAccount($member);

        // A new account goes where the sign-up started (like email sign-up); a returning member lands on Your account.
        $to = $created ? ($flow['next'] ?? '/account') : AuthController::afterLogin($flow['next'] ?? null);

        return $created
            ? redirect($to)->with('account_status', 'Welcome, '.$member->name.'! Your account is ready. You can sign in with '.$name.' any time.')
            : redirect($to);
    }
}
