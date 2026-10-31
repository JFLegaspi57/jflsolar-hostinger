<?php

namespace App\Http\Controllers;

use App\Support\Newsletter;
use Illuminate\View\View;

/** The unsubscribe page (link in every promo email). People join at sign-up or on their account page. */
class NewsletterController extends Controller
{
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
