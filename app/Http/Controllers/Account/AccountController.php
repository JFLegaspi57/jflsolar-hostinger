<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Input;
use App\Support\Members;
use App\Support\Newsletter;
use App\Support\Passwords;
use App\Support\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** The member's own account page: details, newsletter choice, password and past orders. */
class AccountController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $member = Members::current() ?? abort(redirect('/login?next=%2Faccount'));
        $orders = Shop::hasTables() && \Illuminate\Support\Facades\Schema::hasColumn('orders', 'member_id')
            ? Order::query()->where('member_id', $member->id)->withCount('items')->latest('id')->limit(50)->get()
            : collect();

        return view('pages.account.show', [
            'member' => $member,
            'orders' => $orders,
            'subscribed' => Newsletter::isSubscribed($member->email),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $member = Members::current() ?? abort(redirect('/login?next=%2Faccount'));
        $name = trim(Input::str($request, 'name'));
        $phone = trim(Input::str($request, 'phone'));
        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors['name'] = 'Please enter your name.';
        }
        $digits = preg_replace('/\D/', '', $phone);
        if ($phone !== '' && (! preg_match('/^[0-9+()\s-]{7,30}$/', $phone) || strlen($digits) < 7 || strlen($digits) > 15)) {
            $errors['phone'] = 'Please enter a mobile or phone number, e.g. 0917 123 4567, or leave it empty.';
        }
        if ($errors) {
            return redirect('/account#details')->withInput(['name' => $name, 'phone' => $phone])->withErrors($errors);
        }
        $member->forceFill(['name' => $name, 'phone' => $phone ?: null])->save();
        if ($request->input('newsletter') === '1') {
            Newsletter::subscribe($member->email, $member->name, 'account');
        } else {
            Newsletter::unsubscribeEmail($member->email);
        }

        return redirect('/account#details')->with('account_status', 'Your details were saved.');
    }

    public function password(Request $request): RedirectResponse
    {
        $member = Members::current() ?? abort(redirect('/login?next=%2Faccount'));
        if (! Hash::check(Input::str($request, 'current_password'), $member->password)) {
            return redirect('/account#password')->withErrors(['current_password' => 'Your current password is not correct.']);
        }
        if ($problem = Passwords::problem(Input::str($request, 'password'), Input::str($request, 'password_confirmation'), $member->email)) {
            return redirect('/account#password')->withErrors(['password' => $problem]);
        }
        $member->forceFill(['password' => Input::str($request, 'password'), 'remember_token' => Str::random(60)])->save();
        // Signs out "remember me" on other devices; keeps this browser signed in.
        Auth::guard('member')->login($member);
        $request->session()->regenerate();
        Members::stampSession($member);   // this browser stays signed in; other sessions end

        return redirect('/account#password')->with('account_status', 'Your password has been changed.');
    }

    /**
     * Deletes the account. Orders stay (sales records) but are no longer linked to it;
     * forum posts stay readable as "Former member"; the email leaves the promo list.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $member = Members::current() ?? abort(redirect('/login?next=%2Faccount'));
        if (! Hash::check(Input::str($request, 'password'), $member->password)) {
            return redirect('/account#delete')->withErrors(['delete_password' => 'Your password is not correct.']);
        }
        \Illuminate\Support\Facades\DB::transaction(function () use ($member) {
            if (Shop::hasTables() && \Illuminate\Support\Facades\Schema::hasColumn('orders', 'member_id')) {
                Order::query()->where('member_id', $member->id)->update(['member_id' => null]);
            }
            if (\App\Support\Forum::hasTables()) {
                \App\Models\ForumTopic::query()->where('member_id', $member->id)->update(['member_id' => null, 'author_name' => 'Former member']);
                \App\Models\ForumPost::query()->where('member_id', $member->id)->update(['member_id' => null, 'author_name' => 'Former member']);
            }
            \Illuminate\Support\Facades\DB::table('member_password_resets')->where('email', $member->email)->delete();
            $member->delete();
        });
        Newsletter::unsubscribeEmail($member->email);
        Auth::guard('member')->logout();
        $request->session()->forget('member_pw');
        $request->session()->regenerate();

        return redirect('/')->with('account_status', 'Your account has been deleted.');
    }
}
