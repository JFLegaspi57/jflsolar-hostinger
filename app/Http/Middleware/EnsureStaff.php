<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every admin page and action: signed in, account still active, and — after
 * an owner created or reset the account — the person has chosen their own
 * password. Deactivating someone signs them out on their next request.
 */
class EnsureStaff
{
    public function handle(Request $request, Closure $next, ?string $role = null): Response
    {
        $user = $request->user();
        if (! $user || ! $user->active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/admin/login')->with('status_error', $user ? 'Your account has been deactivated. Ask an owner if you need access.' : 'Please sign in.');
        }
        if ($user->must_change_password && ! $request->is('admin/password')) {
            return redirect('/admin/password');
        }
        if ($role === 'owner' && ! $user->isOwner()) {
            return redirect('/admin')->with('status_error', 'That page is available to owners only.');
        }

        return $next($request);
    }
}
