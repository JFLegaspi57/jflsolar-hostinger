<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Passwords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AuthController extends Controller
{
    /** Hostinger File Manager path: <site>/storage/app/admin-recovery.txt */
    public const RECOVERY_FILE = 'app/admin-recovery.txt';

    private const RECOVERY_MAX_AGE = 24 * 60 * 60;

    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->user()?->active) {
            return redirect('/admin');
        }

        return view('admin.auth.login', ['noUsers' => ! User::query()->exists()]);
    }

    public function login(Request $request): RedirectResponse
    {
        $email = mb_strtolower(trim((string) $request->input('email')));
        $key = 'login:'.hash_hmac('sha256', $email.'|'.$request->ip(), (string) config('app.key'));
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            return back()->withInput(['email' => $email])->with('status_error', "Too many attempts. Wait {$minutes} minute(s) and try again.");
        }

        $user = User::query()->where('email', $email)->first();
        // Same message whether the email exists or not; inactive accounts cannot sign in.
        if (! $user || ! $user->active || ! Hash::check((string) $request->input('password'), $user->password)) {
            RateLimiter::hit($key, 15 * 60);

            return back()->withInput(['email' => $email])->with('status_error', 'That email and password do not match an active staff account.');
        }

        RateLimiter::clear($key);
        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return $user->must_change_password ? redirect('/admin/password') : redirect()->intended('/admin');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login')->with('status', 'You are signed out.');
    }

    public function showPassword(Request $request): View
    {
        return view('admin.auth.password', ['forced' => (bool) $request->user()->must_change_password]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->must_change_password && ! Hash::check((string) $request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Your current password is not correct.']);
        }
        if ($problem = Passwords::problem($request->input('password'), $request->input('password_confirmation'), $user->email)) {
            return back()->withErrors(['password' => $problem]);
        }
        $user->forceFill(['password' => $request->input('password'), 'must_change_password' => false])->save();
        // Sign out other sessions of this account.
        DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();
        $request->session()->regenerate();

        return redirect('/admin')->with('status', 'Your password has been changed.');
    }

    /*
     * Recovery / first owner, without email or any outside service:
     * someone with access to the Hostinger account creates the file
     * storage/app/admin-recovery.txt (File Manager) containing a phrase of
     * their choice. This page then lets them set an owner password — or create
     * the first owner when there are no accounts — and deletes the file.
     */
    private function recoveryPhrase(): ?string
    {
        $file = storage_path(self::RECOVERY_FILE);
        if (! is_file($file)) {
            return null;
        }
        if (time() - filemtime($file) > self::RECOVERY_MAX_AGE) {
            return '';
        }

        return trim((string) file_get_contents($file));
    }

    public function showRecover(): View
    {
        $phrase = $this->recoveryPhrase();

        return view('admin.auth.recover', [
            'state' => $phrase === null ? 'missing' : ($phrase === '' ? 'stale' : (mb_strlen($phrase) < 12 ? 'short' : 'ready')),
            'noUsers' => ! User::query()->exists(),
        ]);
    }

    public function recover(Request $request): RedirectResponse
    {
        $key = 'recover:'.hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->with('status_error', 'Too many attempts. Wait 15 minutes and try again.');
        }
        $email = mb_strtolower(trim((string) $request->input('email')));
        $input = ['email' => $email, 'name' => $request->input('name')];
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->withInput($input)->withErrors(['email' => 'Enter a valid email address.']);
        }
        if ($problem = Passwords::problem($request->input('password'), $request->input('password_confirmation'), $email)) {
            return back()->withInput($input)->withErrors(['password' => $problem]);
        }

        // One recovery at a time: the file is checked, consumed and the account
        // changed under a lock, so two simultaneous requests cannot both use it.
        $result = Cache::lock('admin-recovery', 30)->block(10, function () use ($request, $email, $input, $key) {
            $phrase = $this->recoveryPhrase();
            if (! $phrase || mb_strlen($phrase) < 12) {
                return redirect('/admin/recover');
            }
            if (! hash_equals($phrase, trim((string) $request->input('phrase')))) {
                RateLimiter::hit($key, 15 * 60);

                return back()->withInput($input)->withErrors(['phrase' => 'This does not match the phrase in the recovery file.']);
            }
            $noUsers = ! User::query()->exists();
            $user = User::query()->where('email', $email)->first();
            $name = trim((string) $request->input('name'));
            if ($noUsers && mb_strlen($name) < 2) {
                return back()->withInput($input)->withErrors(['name' => 'Enter your name.']);
            }
            if (! $noUsers && (! $user || ! $user->isOwner())) {
                return back()->withInput($input)->withErrors(['email' => 'Recovery works for owner accounts only. Enter the email of an owner.']);
            }

            // Consume the recovery file first (rename is atomic). If that fails,
            // stop: never change an account while the phrase stays usable.
            $file = storage_path(self::RECOVERY_FILE);
            $used = $file.'.used-'.bin2hex(random_bytes(4));
            if (! @rename($file, $used)) {
                return back()->withInput($input)->with('status_error', 'The recovery file could not be removed, so nothing was changed. Check the file permissions in File Manager (or delete and recreate the file), then try again.');
            }
            $removed = @unlink($used);

            if ($noUsers) {
                User::query()->create(['name' => mb_substr($name, 0, 120), 'email' => $email, 'password' => $request->input('password'), 'role' => 'owner', 'active' => true, 'must_change_password' => false]);
            } else {
                $user->forceFill(['password' => $request->input('password'), 'active' => true, 'must_change_password' => false])->save();
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
            RateLimiter::clear($key);

            // The phrase can no longer be used either way (the file was renamed);
            // say so accurately if the renamed copy could not be deleted.
            $fileNote = $removed
                ? 'The recovery file has been deleted.'
                : 'The recovery file can no longer be used, but a copy named '.basename($used).' remains in storage/app — delete it in File Manager.';

            return redirect('/admin/login')->withInput(['email' => $email])->with('status', ($noUsers
                ? 'Owner account created. Sign in with your new password. '
                : 'Password reset. Sign in with your new password. ').$fileNote);
        });

        return $result;
    }
}
