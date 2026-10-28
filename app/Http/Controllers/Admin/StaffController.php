<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Passwords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Owners only. No email involved: owners hand over a one-time temporary password. */
class StaffController extends Controller
{
    public function index(): View
    {
        return view('admin.staff', ['staff' => User::query()->orderByDesc('active')->orderBy('role')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $name = trim((string) $request->input('name'));
        $email = mb_strtolower(trim((string) $request->input('email')));
        $role = $request->input('role');
        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            $errors['name'] = 'Enter the person’s name.';
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif (User::query()->where('email', $email)->exists()) {
            $errors['email'] = 'This email is already on the staff list.';
        }
        if (! in_array($role, ['owner', 'editor'], true)) {
            $errors['role'] = 'Choose a role.';
        }
        if ($errors) {
            return back()->withInput()->withErrors($errors, 'add')->with('form_error', 'Please fix the highlighted fields.');
        }
        $temp = Passwords::temporary();
        User::query()->create(['name' => $name, 'email' => $email, 'role' => $role, 'password' => $temp, 'active' => true, 'must_change_password' => true]);

        return redirect('/admin/staff')->with('temporary', ['email' => $email, 'password' => $temp, 'new' => true]);
    }

    /** Runs a change only if at least one other active owner remains (serialised, so two owners cannot demote each other at once). */
    private function keepingAnOwner(User $user, callable $change): ?string
    {
        return DB::transaction(function () use ($user, $change) {
            $owners = User::query()->where('role', 'owner')->where('active', true)->lockForUpdate()->pluck('id');
            $affectsOwner = $user->role === 'owner' && $user->active;
            if ($affectsOwner && $owners->reject(fn ($id) => $id === $user->id)->isEmpty()) {
                return 'You cannot remove, deactivate or demote the last active owner.';
            }
            $change();

            return null;
        });
    }

    public function role(Request $request, User $user): RedirectResponse
    {
        $role = $request->input('role') === 'owner' ? 'owner' : 'editor';
        if ($role === 'owner') {
            $user->forceFill(['role' => 'owner'])->save();
            $error = null;
        } else {
            $error = $this->keepingAnOwner($user, fn () => $user->forceFill(['role' => 'editor'])->save());
        }

        return $this->done($request, $user, $error, "{$user->email} is now ".($role === 'owner' ? 'an owner' : 'an editor').'.');
    }

    public function active(Request $request, User $user): RedirectResponse
    {
        $active = $request->boolean('active');
        if ($active) {
            $user->forceFill(['active' => true])->save();
            $error = null;
        } else {
            $error = $this->keepingAnOwner($user, function () use ($user) {
                $user->forceFill(['active' => false])->save();
                DB::table('sessions')->where('user_id', $user->id)->delete(); // signed out immediately
            });
        }

        return $this->done($request, $user, $error, $active ? "{$user->email} can sign in again." : "{$user->email} has been deactivated and signed out.");
    }

    public function reset(User $user): RedirectResponse
    {
        $temp = Passwords::temporary();
        $user->forceFill(['password' => $temp, 'must_change_password' => true])->save();
        DB::table('sessions')->where('user_id', $user->id)->delete();

        return redirect('/admin/staff')->with('temporary', ['email' => $user->email, 'password' => $temp, 'new' => false]);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $error = $this->keepingAnOwner($user, function () use ($user) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->delete();
        });

        return $this->done($request, $user, $error, "{$user->email} has been removed from the staff list.");
    }

    private function done(Request $request, User $user, ?string $error, string $message): RedirectResponse
    {
        // Demoting or deactivating yourself takes effect immediately.
        if (! $error && $user->id === $request->user()->id && (! $user->exists || ! $user->fresh()?->active || ! $user->fresh()?->isOwner())) {
            return redirect('/admin')->with('status', $message);
        }

        return redirect('/admin/staff')->with($error ? 'status_error' : 'status', $error ?? $message);
    }
}
