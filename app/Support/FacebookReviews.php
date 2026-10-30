<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Pulls new recommendations from the JFL Solar Facebook page through the Graph API.
 *
 * - The owner connects the page once in Admin → Facebook reviews (a Page access token,
 *   optionally exchanged for a non-expiring one with the app ID + secret, which are not stored).
 * - The token is kept encrypted (APP_KEY) in storage/app/private/facebook — never in Git or the database.
 * - Only "positive" recommendations that have written text are kept.
 * - Checked at most every SYNC_HOURS, after the visitor's page has been sent (no delay for visitors).
 */
final class FacebookReviews
{
    private const GRAPH = 'https://graph.facebook.com';
    private const CONNECTION = 'facebook/connection.enc';
    private const DATA = 'facebook/reviews.json';
    private const SYNC_HOURS = 6;
    private const RETRY_MINUTES = 60;   // after a failed check
    private const MAX_PAGES = 20;       // 20 × 100 reviews

    private static function disk()
    {
        return Storage::disk('local');
    }

    // ---- Connection -------------------------------------------------------------------------

    /** @return array{page_id: string, page_name: string, token: string, connected_at: string, never_expires: bool}|null */
    public static function connection(): ?array
    {
        if (! self::disk()->exists(self::CONNECTION)) {
            return null;
        }
        try {
            $c = json_decode(Crypt::decryptString((string) self::disk()->get(self::CONNECTION)), true);
        } catch (\Throwable) {
            return null;
        }

        return is_array($c) && ! empty($c['token']) && ! empty($c['page_id']) ? $c : null;
    }

    public static function isConnected(): bool
    {
        return self::connection() !== null;
    }

    /**
     * Connect with a token copied from Facebook. If an app ID and secret are given, a user token is
     * first swapped for a long-lived one, then for the page's own token (which does not expire).
     *
     * @return array{page_name: string, never_expires: bool}
     */
    public static function connect(string $token, ?string $appId = null, ?string $appSecret = null): array
    {
        $token = trim($token);
        if ($token === '') {
            throw new RuntimeException('Paste the access token from Facebook.');
        }
        $neverExpires = false;
        if ($appId && $appSecret) {
            $long = self::get('/oauth/access_token', [
                'grant_type' => 'fb_exchange_token', 'client_id' => $appId, 'client_secret' => $appSecret, 'fb_exchange_token' => $token,
            ])['access_token'] ?? null;
            if (! $long) {
                throw new RuntimeException('Facebook did not accept the app ID, app secret and token together.');
            }
            $token = $long;
            $neverExpires = true;
        }

        // A user token lists the pages it manages; a page token describes the page itself.
        $pages = self::get('/me/accounts', ['fields' => 'id,name,access_token', 'limit' => 100, 'access_token' => $token])['data'] ?? null;
        if (is_array($pages) && $pages) {
            $page = collect($pages)->first(fn ($p) => stripos($p['name'] ?? '', 'JFL') !== false) ?? $pages[0];
            $pageId = $page['id'];
            $pageName = $page['name'] ?? 'Facebook page';
            $token = $page['access_token'];
        } else {
            $me = self::get('/me', ['fields' => 'id,name', 'access_token' => $token]);
            $pageId = $me['id'] ?? null;
            $pageName = $me['name'] ?? 'Facebook page';
            if (! $pageId) {
                throw new RuntimeException('That token does not belong to a Facebook page you manage.');
            }
        }

        // Prove it can read the reviews before saving.
        self::get("/{$pageId}/ratings", ['fields' => 'recommendation_type', 'limit' => 1, 'access_token' => $token]);

        self::disk()->put(self::CONNECTION, Crypt::encryptString(json_encode([
            'page_id' => (string) $pageId, 'page_name' => (string) $pageName, 'token' => $token,
            'connected_at' => now()->toIso8601String(), 'never_expires' => $neverExpires,
        ])));
        Cache::forget('fb-reviews-retry');

        return ['page_name' => (string) $pageName, 'never_expires' => $neverExpires];
    }

    public static function disconnect(): void
    {
        self::disk()->delete(self::CONNECTION);
    }

    // ---- Sync -------------------------------------------------------------------------------

    /** Fetch every recommendation and keep the positive ones with text. Returns how many were kept. */
    public static function sync(): int
    {
        $c = self::connection();
        if (! $c) {
            throw new RuntimeException('Facebook is not connected yet.');
        }
        try {
            $items = [];
            $visited = [];
            $params = ['fields' => 'created_time,recommendation_type,review_text,reviewer{name}', 'limit' => 100, 'access_token' => $c['token']];
            $url = "/{$c['page_id']}/ratings";
            for ($i = 0; $i < self::MAX_PAGES && $url; $i++) {
                $res = self::get($url, $params);
                foreach ($res['data'] ?? [] as $r) {
                    $text = trim((string) ($r['review_text'] ?? ''));
                    if (($r['recommendation_type'] ?? '') === 'positive' && $text !== '' && ! isset($items[$text])) {
                        $items[$text] = [(string) ($r['reviewer']['name'] ?? ''), $text, (string) ($r['created_time'] ?? '')];
                    }
                }
                $next = $res['paging']['next'] ?? null;
                $visited[$url] = true;
                [$url, $params] = $next && ! isset($visited[$next]) ? [$next, []] : [null, []];
            }
            self::save(['fetched_at' => now()->toIso8601String(), 'items' => array_values($items), 'error' => null, 'error_at' => null]);

            return count($items);
        } catch (\Throwable $e) {
            $data = self::data();
            self::save(['error' => self::clean($e->getMessage()), 'error_at' => now()->toIso8601String()] + $data);
            Cache::put('fb-reviews-retry', true, now()->addMinutes(self::RETRY_MINUTES));
            throw new RuntimeException(self::clean($e->getMessage()));
        }
    }

    /** Called on page views: if the last check is old, check again after the response is sent. */
    public static function refreshIfDue(): void
    {
        try {
            if (! self::isConnected() || Cache::has('fb-reviews-retry')) {
                return;
            }
            $fetched = self::data()['fetched_at'] ?? null;
            if ($fetched && \Carbon\CarbonImmutable::parse($fetched)->diffInHours(now()) < self::SYNC_HOURS) {
                return;
            }
            if (! Cache::add('fb-reviews-running', true, now()->addMinutes(10))) {
                return;   // another visit is already checking
            }
            \Illuminate\Support\defer(function () {
                try {
                    self::sync();
                } catch (\Throwable $e) {
                    Log::warning('Facebook reviews check failed: '.self::clean($e->getMessage()));
                } finally {
                    Cache::forget('fb-reviews-running');
                }
            });
        } catch (\Throwable $e) {
            Log::warning('Facebook reviews refresh skipped: '.self::clean($e->getMessage()));
        }
    }

    // ---- Stored results ---------------------------------------------------------------------

    /** @return array{fetched_at?: ?string, items?: list<array{0:string,1:string,2:string}>, error?: ?string, error_at?: ?string} */
    public static function data(): array
    {
        if (! self::disk()->exists(self::DATA)) {
            return [];
        }
        $d = json_decode((string) self::disk()->get(self::DATA), true);

        return is_array($d) ? $d : [];
    }

    /** @return list<array{0:string,1:string}> [name, text] pairs of positive reviews from the last check. */
    public static function items(): array
    {
        return array_values(array_filter(array_map(
            fn ($r) => is_array($r) && isset($r[1]) ? [(string) ($r[0] ?? ''), (string) $r[1]] : null,
            self::data()['items'] ?? []
        )));
    }

    private static function save(array $data): void
    {
        self::disk()->put(self::DATA, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    // ---- HTTP -------------------------------------------------------------------------------

    /** Error text without any token or secret (connection errors can include the full address). */
    public static function clean(string $message): string
    {
        return mb_substr((string) preg_replace('/(access_token|client_secret|fb_exchange_token)=[^&\s"\']+/i', '$1=[hidden]', $message), 0, 400);
    }

    private static function get(string $pathOrUrl, array $params): array
    {
        $url = str_starts_with($pathOrUrl, 'https://') ? $pathOrUrl : self::GRAPH.$pathOrUrl;
        if (! str_starts_with($url, self::GRAPH.'/')) {
            throw new RuntimeException('Unexpected Facebook address.');
        }
        try {
            // Facebook's "next page" links already carry their own query (incl. the token); passing
            // an empty query array would wipe it, so only add one when there is something to add.
            $res = $params ? Http::timeout(15)->acceptJson()->get($url, $params) : Http::timeout(15)->acceptJson()->get($url);
        } catch (\Throwable $e) {
            throw new RuntimeException('Could not reach Facebook: '.self::clean($e->getMessage()));
        }
        $body = $res->json() ?? [];
        if ($res->failed() || isset($body['error'])) {
            $msg = $body['error']['message'] ?? ('HTTP '.$res->status());
            throw new RuntimeException('Facebook said: '.mb_substr((string) $msg, 0, 300));
        }

        return $body;
    }
}
