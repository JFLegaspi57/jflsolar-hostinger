<?php

namespace App\Http\Middleware;

use App\Support\Site;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Security headers on every response; preview sites and the admin are never indexed or cached. */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $h = $response->headers;
        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('X-Frame-Options', 'DENY');
        // The checkout form is answered with a redirect to PayMongo's payment page; browsers apply
        // form-action to that redirect too, so PayMongo's checkout host is allowed there.
        $formAction = "'self'".(\App\Support\Shop::enabled() ? ' https://checkout.paymongo.com' : '');
        $h->set('Content-Security-Policy', "frame-ancestors 'none'; base-uri 'self'; form-action {$formAction}; object-src 'none'");
        $h->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()');
        $h->remove('X-Powered-By');

        if (! Site::allowsIndexing() || $request->is('admin', 'admin/*')) {
            $h->set('X-Robots-Tag', 'noindex, nofollow');
        }
        if ($request->is('admin', 'admin/*')) {
            $h->set('Cache-Control', 'private, no-store');
        }

        return $response;
    }
}
