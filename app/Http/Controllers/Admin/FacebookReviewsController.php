<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\FacebookReviews;
use App\Support\Reviews;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Owner-only: connect the Facebook page so new recommendations appear on the website automatically. */
class FacebookReviewsController extends Controller
{
    public function show(): View
    {
        $data = FacebookReviews::data();

        return view('admin.facebook-reviews', [
            'connection' => FacebookReviews::connection(),
            'data' => $data,
            'fromFacebook' => count(FacebookReviews::items()),
            'total' => count(Reviews::all()),
        ]);
    }

    public function connect(Request $request): RedirectResponse
    {
        $token = trim((string) $request->input('token', ''));
        $appId = trim((string) $request->input('app_id', ''));
        $secret = trim((string) $request->input('app_secret', ''));
        if ($token === '' || mb_strlen($token) > 1000 || mb_strlen($appId) > 40 || mb_strlen($secret) > 100) {
            return redirect('/admin/facebook-reviews')->with('status_error', 'Paste the access token from Facebook (and the app ID and secret, if you have them).');
        }
        if (($appId === '') !== ($secret === '')) {
            return redirect('/admin/facebook-reviews')->with('status_error', 'Enter both the app ID and the app secret, or leave both empty.');
        }
        try {
            $page = FacebookReviews::connect($token, $appId ?: null, $secret ?: null);
            $count = FacebookReviews::sync();
        } catch (\Throwable $e) {
            return redirect('/admin/facebook-reviews')->with('status_error', 'Could not connect. '.FacebookReviews::clean($e->getMessage()));
        }

        return redirect('/admin/facebook-reviews')->with('status', "Connected to {$page['page_name']}. Found {$count} positive reviews with text.");
    }

    public function sync(): RedirectResponse
    {
        try {
            $count = FacebookReviews::sync();
        } catch (\Throwable $e) {
            return redirect('/admin/facebook-reviews')->with('status_error', 'Check failed. '.FacebookReviews::clean($e->getMessage()));
        }

        return redirect('/admin/facebook-reviews')->with('status', "Checked Facebook just now: {$count} positive reviews with text.");
    }

    public function disconnect(): RedirectResponse
    {
        FacebookReviews::disconnect();

        return redirect('/admin/facebook-reviews')->with('status', 'Facebook disconnected. Reviews already on the website stay.');
    }
}
