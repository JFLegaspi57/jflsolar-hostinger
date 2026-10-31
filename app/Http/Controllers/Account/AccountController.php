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
        Members::ensureTables();   // adds the profile columns on sites created before they existed
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

    /** Profile photo and social links (only the member and staff see them). */
    public function profile(Request $request): RedirectResponse
    {
        $member = Members::current() ?? abort(redirect('/login?next=%2Faccount'));
        Members::ensureTables();
        $back = redirect('/account#profile');
        $links = [];
        $errors = [];
        foreach (Members::SOCIALS as $field => [$label]) {
            $value = Members::socialUrl($field, Input::str($request, $field));
            if ($value === null) {
                $errors[$field] = 'Please paste a link to your '.$label.' page, e.g. '.sprintf(Members::SOCIALS[$field][2], 'yourname').'';
            } else {
                $links[$field] = $value ?: null;
            }
        }
        if ($errors) {
            return $back->withInput($request->only(array_keys(Members::SOCIALS)))->withErrors($errors);
        }

        $photo = $member->photo;
        if ($request->hasFile('photo')) {
            $name = \Illuminate\Support\Str::random(24).'.webp';
            $result = \App\Support\ImageProcessor::avatar($request->file('photo'), Members::photoDir($member).'/'.$name);
            if (isset($result['error'])) {
                return $back->withInput($request->only(array_keys(Members::SOCIALS)))->withErrors(['photo' => $result['error']]);
            }
            $old = $photo;
            $photo = $name;
        } elseif ($request->input('remove_photo') === '1') {
            $old = $photo;
            $photo = null;
        }
        $member->forceFill($links + ['photo' => $photo])->save();
        if (! empty($old) && $old !== $photo) {
            @unlink(Members::photoDir($member).'/'.basename($old));
        }

        return $back->with('account_status', 'Your profile was saved.');
    }

    /** The profile photo: shown to the member themselves and to staff only. */
    public function photo(int $member, string $file): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $me = Members::current();
        $staff = auth('web')->user();
        abort_unless(($me && $me->id === $member) || ($staff && $staff->active), 404);
        $m = $me && $me->id === $member ? $me : \App\Models\Member::query()->find($member);
        abort_unless($m && $m->photo === $file, 404);
        $path = Members::photoDir($m).'/'.basename($file);
        abort_unless(is_file($path), 404);

        return response()->file($path, ['Content-Type' => 'image/webp', 'Cache-Control' => 'private, max-age=86400', 'X-Content-Type-Options' => 'nosniff']);
    }

    /**
     * Deletes the account. Orders stay (sales records) but are no longer linked to it;
     * the email leaves the promo list.
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
            \Illuminate\Support\Facades\DB::table('member_password_resets')->where('email', $member->email)->delete();
            $member->delete();
        });
        Members::deletePhotos($member);
        Newsletter::unsubscribeEmail($member->email);
        Auth::guard('member')->logout();
        $request->session()->forget('member_pw');
        $request->session()->regenerate();

        return redirect('/')->with('account_status', 'Your account has been deleted.');
    }
}
