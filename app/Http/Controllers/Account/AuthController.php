<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Support\Input;
use App\Support\Members;
use App\Support\Newsletter;
use App\Support\Passwords;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Sign up, sign in, sign out and password reset for member (customer) accounts. */
class AuthController extends Controller
{
    public function showSignup(Request $request): View|RedirectResponse
    {
        if (Members::current()) {
            return redirect('/account');
        }

        return view('pages.account.signup', ['next' => $this->next($request)]);
    }

    public function signup(Request $request): RedirectResponse
    {
        Members::ensureTables();
        $values = [
            'name' => trim(Input::str($request, 'name')),
            'email' => mb_strtolower(trim(Input::str($request, 'email'))),
            'phone' => trim(Input::str($request, 'phone')),
            'newsletter' => $request->input('newsletter') === '1' ? '1' : '',
        ];
        $back = fn (array $errors) => redirect('/signup'.$this->nextQuery($request))->withInput($values)->withErrors($errors);

        if (Input::str($request, 'website') !== '') {
            return redirect('/');   // honeypot: bots fill every field
        }
        $limit = 'member-signup:'.hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
        if (RateLimiter::tooManyAttempts($limit, 10)) {
            return $back(['email' => 'Too many sign-up attempts from your connection. Please try again later.']);
        }
        RateLimiter::hit($limit, 3600);   // every attempt counts, so the form can't be used to test many emails

        $errors = [];
        if (mb_strlen($values['name']) < 2 || mb_strlen($values['name']) > 100) {
            $errors['name'] = 'Please enter your name.';
        }
        if (! filter_var($values['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($values['email']) > 254) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        $digits = preg_replace('/\D/', '', $values['phone']);
        if ($values['phone'] !== '' && (! preg_match('/^[0-9+()\s-]{7,30}$/', $values['phone']) || strlen($digits) < 7 || strlen($digits) > 15)) {
            $errors['phone'] = 'Please enter a mobile or phone number, e.g. 0917 123 4567, or leave it empty.';
        }
        if ($problem = Passwords::problem(Input::str($request, 'password'), Input::str($request, 'password_confirmation'), $values['email'])) {
            $errors['password'] = $problem;
        }
        if ($request->input('agree') !== '1') {
            $errors['agree'] = 'Please agree to the terms and privacy notice.';
        }
        if (! $errors && Member::query()->where('email', $values['email'])->exists()) {
            $errors['email'] = 'There is already an account with this email. Sign in, or reset your password if you forgot it.';
        }
        if ($errors) {
            return $back($errors);
        }

        $member = Member::query()->create([
            'name' => $values['name'],
            'email' => $values['email'],
            'phone' => $values['phone'] ?: null,
            'password' => Input::str($request, 'password'),
            'status' => 'active',
        ]);
        if ($values['newsletter'] === '1' && class_exists(Newsletter::class)) {
            Newsletter::subscribe($member->email, $member->name, 'signup');
        }
        Auth::guard('member')->login($member);
        $request->session()->regenerate();
        Members::stampSession($member);
        $member->forceFill(['last_login_at' => now()])->save();
        Members::logActivity($member, 'login', null, $request->ip());

        return redirect($this->next($request) ?? '/account')->with('account_status', 'Welcome, '.$member->name.'! Your account is ready.');
    }

    public function showLogin(Request $request): View|RedirectResponse
    {
        if (Members::current()) {
            return redirect($this->next($request) ?? '/account');
        }

        return view('pages.account.login', ['next' => $this->next($request)]);
    }

    public function login(Request $request): RedirectResponse
    {
        $email = mb_strtolower(trim(Input::str($request, 'email')));
        $back = fn (string $message) => redirect('/login'.$this->nextQuery($request))->withInput(['email' => $email])->with('account_error', $message);
        $key = 'member-login:'.hash_hmac('sha256', $email.'|'.$request->ip(), (string) config('app.key'));
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            return $back("Too many attempts. Wait {$minutes} minute(s) and try again, or reset your password.");
        }
        if (Members::hasTables()) {
            Members::ensureTables();   // adds newer columns/tables (profile, activity) on first sign-in after an update
        }
        $member = Members::hasTables() ? Member::query()->where('email', $email)->first() : null;
        // Same message whether or not the email exists; disabled accounts cannot sign in.
        if (! $member || ! $member->isActive() || ! Hash::check(Input::str($request, 'password'), $member->password)) {
            RateLimiter::hit($key, 15 * 60);

            return $back('That email and password do not match an account.');
        }
        RateLimiter::clear($key);
        Auth::guard('member')->login($member, $request->input('remember') === '1');
        $request->session()->regenerate();
        Members::stampSession($member);
        $member->forceFill(['last_login_at' => now()])->save();
        Members::logActivity($member, 'login', null, $request->ip());

        return redirect($this->next($request) ?? '/account');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('member')->logout();
        $request->session()->forget('member_pw');
        // Keep a signed-in staff session (same browser) working; only refresh the form token.
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect('/')->with('account_status', 'You are signed out.');
    }

    public function showForgot(): View
    {
        return view('pages.account.forgot');
    }

    public function forgot(Request $request): RedirectResponse
    {
        $email = mb_strtolower(trim(Input::str($request, 'email')));
        $done = redirect('/forgot-password')->with('account_status', 'If there is an account for '.$email.', we have emailed a link to choose a new password. It works for 60 minutes. No email? Check your spam folder, or call us.');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect('/forgot-password')->withInput(['email' => $email])->withErrors(['email' => 'Please enter a valid email address.']);
        }
        $key = 'member-forgot:'.hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return $done;
        }
        RateLimiter::hit($key, 3600);
        if (! Members::hasTables()) {
            return $done;
        }
        try {
            Password::broker('members')->sendResetLink(['email' => $email]);
        } catch (\Throwable $e) {
            Log::error('[members] password reset email failed', ['error' => $e::class]);
        }

        return $done;   // same answer whether or not the account exists
    }

    public function showReset(Request $request, string $token): View
    {
        return view('pages.account.reset', ['token' => $token, 'email' => Input::str($request, 'email')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $email = mb_strtolower(trim(Input::str($request, 'email')));
        $token = Input::str($request, 'token');
        $back = fn (array $errors) => redirect('/reset-password/'.rawurlencode($token).'?email='.rawurlencode($email))->withErrors($errors);
        if ($problem = Passwords::problem(Input::str($request, 'password'), Input::str($request, 'password_confirmation'), $email)) {
            return $back(['password' => $problem]);
        }
        $status = Members::hasTables() ? Password::broker('members')->reset(
            ['email' => $email, 'token' => $token, 'password' => Input::str($request, 'password'), 'password_confirmation' => Input::str($request, 'password_confirmation')],
            function (Member $member, string $password) {
                $member->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($member));
            },
        ) : Password::INVALID_TOKEN;

        if ($status !== Password::PASSWORD_RESET) {
            return $back(['password' => 'This reset link is invalid or has expired. Please ask for a new one.']);
        }

        return redirect('/login')->withInput(['email' => $email])->with('account_status', 'Your password has been changed. Sign in with your new password.');
    }

    /** Where to go after signing in: only a path on this site (no open redirects). */
    private function next(Request $request): ?string
    {
        $next = Input::str($request, 'next');

        return preg_match('#^/(?![/\\\\])[A-Za-z0-9/_\-?=&.%]{0,200}$#D', $next) && ! str_starts_with($next, '/admin') ? $next : null;
    }

    private function nextQuery(Request $request): string
    {
        return ($n = $this->next($request)) ? '?next='.rawurlencode($n) : '';
    }
}
