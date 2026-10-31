<?php

namespace App\Http\Controllers;

use App\Support\Newsletter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/** Footer "Get promos and news" form and the unsubscribe page. */
class NewsletterController extends Controller
{
    public function subscribe(Request $request): RedirectResponse
    {
        $back = redirect()->to(url()->previous('/').'#newsletter');
        if ((string) $request->input('website', '') !== '') {
            return $back;   // honeypot
        }
        $email = mb_strtolower(trim((string) $request->input('email', '')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) {
            return $back->withInput(['newsletter_email' => $email])->with('newsletter_error', 'Please enter a valid email address.');
        }
        if ($request->input('consent') !== '1') {
            return $back->withInput(['newsletter_email' => $email])->with('newsletter_error', 'Please tick the box to agree to receive our emails.');
        }
        $key = 'newsletter:'.hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return $back->with('newsletter_error', 'Too many sign-ups from your connection. Please try again later.');
        }
        RateLimiter::hit($key, 3600);
        Newsletter::subscribe($email, null, 'footer');

        // Same message for new and existing addresses (no way to test who is on the list).
        return $back->with('newsletter_status', 'Thank you! '.$email.' will receive our promos and news. You can unsubscribe at any time.');
    }

    public function showUnsubscribe(string $token): View
    {
        return view('pages.newsletter-unsubscribe', ['token' => $token, 'done' => null]);
    }

    /** A button press, not the link itself, so email scanners that open links don't unsubscribe people. */
    public function unsubscribe(string $token): View
    {
        $email = Newsletter::unsubscribeToken($token);

        return view('pages.newsletter-unsubscribe', ['token' => $token, 'done' => $email !== null, 'email' => $email]);
    }
}
