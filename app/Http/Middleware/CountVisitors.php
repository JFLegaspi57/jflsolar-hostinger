<?php

namespace App\Http\Middleware;

use App\Support\GeoIp;
use App\Support\Visitors;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prepares counting for people (not robots or staff) who open public pages.
 *
 * Nothing is written here. The page remembers (for 2 hours) which page this view is and which
 * browser asked for it; the visit is only recorded when the page's own script checks in
 * (POST /pv, see Visitors::start). Real browsers run that script; uptime checkers and simple
 * robots don't, so they never appear in the numbers.
 * Browsers that send "Do Not Track" or "Global Privacy Control" are not counted.
 */
class CountVisitors
{
    private const BOTS = '/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|embedly|preview|monitor|uptime|curl|wget|python|httpclient|headless|lighthouse|pingdom|scan/i';

    public function handle(Request $request, Closure $next): Response
    {
        // An id for this page view; the page's small timing script reports back with it.
        $pv = bin2hex(random_bytes(12));
        View::share('pageviewId', $pv);

        $response = $next($request);

        // Signed-in customers: pages they open are listed on their record in Admin → Members.
        try {
            if ($this->isPageView($request, $response) && ! auth()->check() && ($member = \App\Support\Members::current())) {
                $query = $request->getQueryString();
                \App\Support\Members::logActivity($member, 'page', '/'.ltrim($request->path(), '/').($query ? '?'.$query : ''), $request->ip());
            }
        } catch (\Throwable $e) {
            Log::warning('Member page view skipped: '.$e->getMessage());
        }

        try {
            if (! $this->shouldCount($request, $response)) {
                return $response;
            }
            $vid = (string) $request->cookie(Visitors::COOKIE, '');
            if (! preg_match('/^[a-f0-9]{32}$/', $vid)) {
                $vid = bin2hex(random_bytes(16));
                $response->headers->setCookie(Cookie::make(Visitors::COOKIE, $vid, 60 * 24 * 400, '/', null, $request->isSecure(), true, false, 'lax'));
            }
            Visitors::pending($pv, $vid, $request->path());
            GeoIp::refreshIfDue();
        } catch (\Throwable $e) {
            Log::warning('Visitor count skipped: '.$e->getMessage());
        }

        return $response;
    }

    /** A public web page opened by a real browser that hasn't asked not to be tracked. */
    private function isPageView(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return false;
        }
        if ($request->is('admin', 'admin/*', 'media/*', 'robots.txt', 'sitemap.xml', 'build/*', 'account/photo/*')) {
            return false;
        }
        if (! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return false;
        }

        return $request->header('DNT') !== '1' && $request->header('Sec-GPC') !== '1';
    }

    private function shouldCount(Request $request, Response $response): bool
    {
        if (! $this->isPageView($request, $response)) {
            return false;
        }
        if (auth()->check()) {
            return false;   // signed-in staff
        }
        $ua = (string) $request->userAgent();

        return $ua !== '' && ! preg_match(self::BOTS, $ua);
    }
}
