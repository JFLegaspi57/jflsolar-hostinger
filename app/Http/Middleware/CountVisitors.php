<?php

namespace App\Http\Middleware;

use App\Support\GeoIp;
use App\Support\Visitors;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Counts people (not robots or staff) who open public pages. At most one database write
 * per browser per day: a second cookie remembers that today's visit is already counted.
 * Browsers that send "Do Not Track" or "Global Privacy Control" are not counted.
 */
class CountVisitors
{
    private const BOTS = '/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|embedly|preview|monitor|uptime|curl|wget|python|httpclient|headless|lighthouse|pingdom|scan/i';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            if (! $this->shouldCount($request, $response)) {
                return $response;
            }
            $today = Visitors::today()->toDateString();
            $vid = (string) $request->cookie(Visitors::COOKIE, '');
            if (! preg_match('/^[a-f0-9]{32}$/', $vid)) {
                $vid = bin2hex(random_bytes(16));
                $response->headers->setCookie(Cookie::make(Visitors::COOKIE, $vid, 60 * 24 * 400, '/', null, $request->isSecure(), true, false, 'lax'));
            }
            if ($request->cookie(Visitors::SEEN_COOKIE) === $today) {
                return $response;
            }
            GeoIp::refreshIfDue();
            Visitors::record($vid, GeoIp::lookup($request->ip()));
            // Remember until midnight (Philippine time) that today's visit is counted.
            $minutes = (int) max(1, now(Visitors::TZ)->diffInMinutes(Visitors::today()->addDay()));
            $response->headers->setCookie(Cookie::make(Visitors::SEEN_COOKIE, $today, $minutes, '/', null, $request->isSecure(), true, false, 'lax'));
        } catch (\Throwable $e) {
            Log::warning('Visitor count skipped: '.$e->getMessage());
        }

        return $response;
    }

    private function shouldCount(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return false;
        }
        if ($request->is('admin', 'admin/*', 'media/*', 'robots.txt', 'sitemap.xml', 'build/*')) {
            return false;
        }
        if (! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return false;
        }
        if (auth()->check()) {
            return false;   // signed-in staff
        }
        if ($request->header('DNT') === '1' || $request->header('Sec-GPC') === '1') {
            return false;
        }
        $ua = (string) $request->userAgent();

        return $ua !== '' && ! preg_match(self::BOTS, $ua);
    }
}
