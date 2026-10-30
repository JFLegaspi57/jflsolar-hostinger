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
    public const SEEN_COOKIE = 'jfl_vd';

    /** Create the two tables the first time they are needed (no phpMyAdmin step). */
    public static function ensureTables(): void
    {
        if (Cache::get('visitor-tables-v1')) {
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
        Cache::forever('visitor-tables-v1', true);
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
    public static function record(string $visitorId): void
    {
        self::ensureTables();
        $day = self::today()->toDateString();
        $hash = self::hash($visitorId);

        $inserted = DB::table('visitor_days')->insertOrIgnore(['day' => $day, 'id_hash' => $hash]);
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

    /** Total distinct visitors ever recorded. */
    public static function allTime(): int
    {
        self::ensureTables();

        return (int) DB::table('visitor_ids')->count();
    }
}
