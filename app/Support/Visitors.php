<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * First-party visitor counts for the admin "Visitors" page.
 *
 * Each browser gets a random visitor number in a cookie; only a keyed hash of it is stored,
 * with the days it visited. No IP address, name or other personal data is kept.
 * Days and weeks follow Philippine time; weeks run Monday to Sunday.
 */
final class Visitors
{
    public const TZ = 'Asia/Manila';
    public const COOKIE = 'jfl_vid';

    /** Create the two tables the first time they are needed (no phpMyAdmin step). */
    public static function ensureTables(): void
    {
        if (Cache::get('visitor-tables-v3')) {
            return;
        }
        if (! Schema::hasTable('visitor_ids')) {
            Schema::create('visitor_ids', function (Blueprint $t) {
                $t->char('id_hash', 64)->primary();
                $t->date('first_seen')->index();
                $t->date('last_seen');
                $t->unsignedInteger('days')->default(1);
            });
        }
        if (! Schema::hasTable('visitor_days')) {
            Schema::create('visitor_days', function (Blueprint $t) {
                $t->date('day');
                $t->char('id_hash', 64);
                $t->primary(['day', 'id_hash']);
                $t->index('id_hash');
            });
        }
        // v2: approximate place of each visit-day (country code + name, city). No IP address is kept.
        if (! Schema::hasColumn('visitor_days', 'country')) {
            Schema::table('visitor_days', function (Blueprint $t) {
                $t->char('country', 2)->nullable();
                $t->string('country_name', 60)->nullable();
                $t->string('city', 80)->nullable();
            });
        }
        // v3: one row per page view (page, hour of day, seconds the page was on screen).
        if (! Schema::hasTable('page_views')) {
            Schema::create('page_views', function (Blueprint $t) {
                $t->id();
                $t->char('pv', 24)->unique();
                $t->char('id_hash', 64)->index();
                $t->date('day')->index();
                $t->unsignedTinyInteger('hour');
                $t->string('path', 190);
                $t->unsignedInteger('duration_s')->nullable();
                $t->timestamp('created_at')->nullable();
            });
        }
        Cache::forever('visitor-tables-v3', true);
    }

    /**
     * All-time numbers for the homepage banner (live: recalculated at most every 15 seconds):
     * first-time visitors (each browser once), returning visitors (came back on another day),
     * registered users (active accounts) and total visits (one per visitor per day).
     *
     * @return array{first:int, returning:int, members:int, visits:int}|null
     */
    public static function publicStats(): ?array
    {
        try {
            return Cache::remember('public-visitor-stats', 15, function () {
                self::ensureTables();

                return [
                    'first' => (int) DB::table('visitor_ids')->count(),
                    'returning' => (int) DB::table('visitor_ids')->where('days', '>', 1)->count(),
                    'members' => \App\Support\Members::hasTables() ? (int) \App\Models\Member::query()->where('status', 'active')->count() : 0,
                    'visits' => (int) DB::table('visitor_days')->count(),
                ];
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Visitor stats unavailable: '.$e->getMessage());

            return null;
        }
    }

    /** Longest time counted for one page: a tab left open overnight should not count as hours of reading. */
    public const MAX_PAGE_SECONDS = 1800;

    public static function recordPage(string $pv, string $visitorId, string $path): void
    {
        self::ensureTables();
        $now = CarbonImmutable::now(self::TZ);
        DB::table('page_views')->insertOrIgnore([
            'pv' => $pv, 'id_hash' => self::hash($visitorId), 'day' => $now->toDateString(), 'hour' => (int) $now->format('G'),
            'path' => mb_substr('/'.ltrim($path, '/'), 0, 190), 'created_at' => now(),
        ]);
    }

    /** The page was served: remember for 2 hours which page and browser this view id belongs to. */
    public static function pending(string $pv, string $visitorId, string $path): void
    {
        Cache::put('pv:'.$pv, ['vid' => $visitorId, 'path' => '/'.ltrim($path, '/')], now()->addHours(2));
    }

    /**
     * The page's script checked in, so a real browser is showing it: record the visit-day
     * (with country and city) and the page view. Unknown or already used ids do nothing.
     */
    public static function start(string $pv, ?string $ip): bool
    {
        $p = Cache::pull('pv:'.$pv);
        if (! is_array($p) || empty($p['vid'])) {
            return false;
        }
        self::record($p['vid'], GeoIp::lookup($ip));
        self::recordPage($pv, $p['vid'], (string) $p['path']);

        return true;
    }

    /** Time-on-page report from the browser (sent when the visitor leaves or switches away). */
    public static function recordDuration(string $pv, int $seconds): void
    {
        self::ensureTables();
        $seconds = max(0, min($seconds, self::MAX_PAGE_SECONDS));
        $row = DB::table('page_views')->where('pv', $pv)->where('created_at', '>=', now()->subHours(3))->first(['id', 'duration_s']);
        if ($row && $seconds > (int) $row->duration_s) {
            DB::table('page_views')->where('id', $row->id)->update(['duration_s' => $seconds]);
        }
    }

    /**
     * Page, duration and time-of-day figures between two days.
     *
     * @return array{pages: list<array>, hours: array<int,int>, visits: array{measured: int, average: ?int, median: ?int, buckets: array<string,int>}, views: int}
     */
    public static function activity(CarbonImmutable $from, CarbonImmutable $to, int $limit = 15): array
    {
        self::ensureTables();
        $range = [$from->toDateString(), $to->toDateString()];
        $base = fn () => DB::table('page_views')->whereBetween('day', $range);

        $pages = $base()->selectRaw('path, COUNT(*) AS views, COUNT(DISTINCT id_hash) AS visitors, AVG(duration_s) AS avg_s')
            ->groupBy('path')->orderByDesc('views')->limit($limit)->get()
            ->map(fn ($r) => ['path' => $r->path, 'label' => self::pageLabel($r->path), 'views' => (int) $r->views, 'visitors' => (int) $r->visitors, 'avg' => $r->avg_s !== null ? (int) round($r->avg_s) : null])->all();

        $hours = array_fill(0, 24, 0);
        foreach ($base()->selectRaw('hour, COUNT(*) AS n')->groupBy('hour')->pluck('n', 'hour') as $h => $n) {
            $hours[(int) $h] = (int) $n;
        }

        // A "visit" = one visitor on one day; its length = time on all its pages that reported a time.
        $lengths = $base()->whereNotNull('duration_s')->selectRaw('day, id_hash, SUM(duration_s) AS s')
            ->groupBy('day', 'id_hash')->pluck('s')->map(fn ($s) => (int) $s)->sort()->values()->all();
        $buckets = ['Under 10 sec' => 0, '10–30 sec' => 0, '30 sec – 1 min' => 0, '1–3 min' => 0, '3–10 min' => 0, 'Over 10 min' => 0];
        foreach ($lengths as $s) {
            $key = match (true) { $s < 10 => 'Under 10 sec', $s < 30 => '10–30 sec', $s < 60 => '30 sec – 1 min', $s < 180 => '1–3 min', $s < 600 => '3–10 min', default => 'Over 10 min' };
            $buckets[$key]++;
        }
        $n = count($lengths);

        return [
            'views' => (int) $base()->count(),
            'pages' => $pages,
            'hours' => $hours,
            'visits' => [
                'measured' => $n,
                'average' => $n ? (int) round(array_sum($lengths) / $n) : null,
                'median' => $n ? $lengths[intdiv($n, 2)] : null,
                'buckets' => $buckets,
            ],
        ];
    }

    public static function pageLabel(string $path): string
    {
        $names = ['/' => 'Home', '/services' => 'Services', '/pv-calculator' => 'PV Calculator', '/packages' => 'Packages', '/packages/compare' => 'Compare packages',
            '/products' => 'Products', '/projects' => 'Projects', '/about' => 'About', '/contact' => 'Contact', '/privacy' => 'Privacy notice', '/terms' => 'Terms of use'];
        if (isset($names[$path])) {
            return $names[$path];
        }
        foreach (['/packages/' => 'Package', '/products/' => 'Product', '/projects/' => 'Project'] as $prefix => $kind) {
            if (str_starts_with($path, $prefix)) {
                return $kind.': '.ucwords(str_replace('-', ' ', substr($path, strlen($prefix))));
            }
        }

        return $path;
    }

    /** "75" → "1 min 15 sec". */
    public static function duration(?int $s): string
    {
        if ($s === null) {
            return '—';
        }

        return $s < 60 ? "{$s} sec" : intdiv($s, 60).' min'.($s % 60 ? ' '.($s % 60).' sec' : '');
    }

    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TZ)->startOfDay();
    }

    public static function hash(string $visitorId): string
    {
        return hash_hmac('sha256', 'visitor|'.$visitorId, (string) config('app.key'));
    }

    /** Record one visit-day for this visitor (called at most once a day per browser). */
    public static function record(string $visitorId, ?array $place = null): void
    {
        self::ensureTables();
        $day = self::today()->toDateString();
        $hash = self::hash($visitorId);

        $inserted = DB::table('visitor_days')->insertOrIgnore([
            'day' => $day, 'id_hash' => $hash,
            'country' => $place['country'] ?? null, 'country_name' => $place['country_name'] ?? null, 'city' => $place['city'] ?? null,
        ]);
        if ($inserted === 0) {
            return;   // already counted today
        }
        $known = DB::table('visitor_ids')->where('id_hash', $hash)->exists();
        if ($known) {
            DB::table('visitor_ids')->where('id_hash', $hash)->update(['last_seen' => $day, 'days' => DB::raw('days + 1')]);
        } else {
            DB::table('visitor_ids')->insertOrIgnore(['id_hash' => $hash, 'first_seen' => $day, 'last_seen' => $day, 'days' => 1]);
        }
    }

    /**
     * Unique, new and returning visitors between two days (inclusive).
     * New = first ever visit fell in the period; returning = visited in the period but first came earlier.
     *
     * @return array{unique: int, new: int, returning: int}
     */
    public static function between(CarbonImmutable $from, CarbonImmutable $to): array
    {
        self::ensureTables();
        [$a, $b] = [$from->toDateString(), $to->toDateString()];
        $unique = (int) DB::table('visitor_days')->whereBetween('day', [$a, $b])->distinct()->count('id_hash');
        $new = (int) DB::table('visitor_ids')->whereBetween('first_seen', [$a, $b])->count();

        return ['unique' => $unique, 'new' => min($new, $unique), 'returning' => max(0, $unique - $new)];
    }

    /** @return list<array{key: string, label: string, range: string, from: CarbonImmutable, to: CarbonImmutable}> */
    public static function periods(): array
    {
        $t = self::today();
        $p = [
            ['today', 'Today', $t, $t],
            ['week', 'This week', $t->startOfWeek(CarbonImmutable::MONDAY), $t],
            ['month', 'This month', $t->startOfMonth(), $t],
            ['year', 'This year', $t->startOfYear(), $t],
        ];

        return array_map(fn ($x) => [
            'key' => $x[0], 'label' => $x[1], 'from' => $x[2], 'to' => $x[3],
            'range' => $x[2]->equalTo($x[3]) ? $x[2]->format('D, M j, Y') : $x[2]->format('M j').' – '.$x[3]->format('M j, Y'),
        ], $p);
    }

    /** Last N days, newest first. @return list<array{day: CarbonImmutable, unique: int, new: int, returning: int}> */
    public static function daily(int $days = 30): array
    {
        self::ensureTables();
        $to = self::today();
        $from = $to->subDays($days - 1);
        $unique = DB::table('visitor_days')->selectRaw('day, COUNT(*) AS n')->whereBetween('day', [$from->toDateString(), $to->toDateString()])->groupBy('day')->pluck('n', 'day');
        $new = DB::table('visitor_ids')->selectRaw('first_seen AS day, COUNT(*) AS n')->whereBetween('first_seen', [$from->toDateString(), $to->toDateString()])->groupBy('first_seen')->pluck('n', 'day');
        $out = [];
        for ($d = $to; $d->gte($from); $d = $d->subDay()) {
            $k = $d->toDateString();
            $u = (int) ($unique[$k] ?? 0);
            $n = min((int) ($new[$k] ?? 0), $u);
            $out[] = ['day' => $d, 'unique' => $u, 'new' => $n, 'returning' => $u - $n];
        }

        return $out;
    }

    /**
     * Unique visitors by country, and by city, between two days. Visits with no known place are "Unknown".
     *
     * @return array{countries: list<array{code: ?string, name: string, visitors: int}>, cities: list<array{city: string, country: string, visitors: int}>}
     */
    public static function places(CarbonImmutable $from, CarbonImmutable $to, int $limit = 15): array
    {
        self::ensureTables();
        $range = [$from->toDateString(), $to->toDateString()];
        $countries = DB::table('visitor_days')->whereBetween('day', $range)
            ->selectRaw('country, MAX(country_name) AS country_name, COUNT(DISTINCT id_hash) AS n')
            ->groupBy('country')->orderByDesc('n')->limit($limit)->get()
            ->map(fn ($r) => ['code' => $r->country, 'name' => $r->country ? ($r->country_name ?: $r->country) : 'Unknown', 'visitors' => (int) $r->n])->all();
        $cities = DB::table('visitor_days')->whereBetween('day', $range)
            ->selectRaw('city, MAX(country_name) AS country_name, COUNT(DISTINCT id_hash) AS n')
            ->groupBy('city', 'country')->orderByDesc('n')->limit($limit)->get()
            ->map(fn ($r) => ['city' => $r->city ?: 'Unknown', 'country' => (string) ($r->country_name ?? ''), 'visitors' => (int) $r->n])->all();

        return ['countries' => $countries, 'cities' => $cities];
    }

    /** Total distinct visitors ever recorded. */
    public static function allTime(): int
    {
        self::ensureTables();

        return (int) DB::table('visitor_ids')->count();
    }
}
