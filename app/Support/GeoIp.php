<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Country and city for a visitor's IP address, looked up on our own server in the free
 * "IP to City Lite" database by DB-IP (https://db-ip.com, licensed CC BY 4.0).
 * The website downloads a fresh copy each month by itself. No address is sent anywhere
 * and none is stored — only the resulting country and city.
 */
final class GeoIp
{
    public const ATTRIBUTION_URL = 'https://db-ip.com';
    private const FILE = 'geo/dbip-city-lite.mmdb';
    private const META = 'geo/dbip-city-lite.json';
    private const URL = 'https://download.db-ip.com/free/dbip-city-lite-%s.mmdb.gz';

    private static ?MmdbReader $reader = null;

    public static function path(): string
    {
        return storage_path('app/private/'.self::FILE);
    }

    /** @return array{edition?: string, downloaded_at?: string, error?: string, error_at?: string} */
    public static function status(): array
    {
        $f = storage_path('app/private/'.self::META);
        $d = is_file($f) ? json_decode((string) file_get_contents($f), true) : null;

        return is_array($d) ? $d : [];
    }

    public static function ready(): bool
    {
        return is_file(self::path());
    }

    /** @return array{country: string, country_name: string, city: ?string}|null */
    public static function lookup(?string $ip): ?array
    {
        if (! $ip || ! self::ready() || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }
        try {
            self::$reader ??= new MmdbReader(self::path());
            $r = self::$reader->get($ip);
        } catch (\Throwable $e) {
            Log::warning('Location lookup failed: '.$e->getMessage());

            return null;
        }
        $code = $r['country']['iso_code'] ?? null;
        if (! is_string($code) || strlen($code) !== 2) {
            return null;
        }

        return [
            'country' => strtoupper($code),
            'country_name' => mb_substr((string) ($r['country']['names']['en'] ?? $code), 0, 60),
            'city' => isset($r['city']['names']['en']) ? mb_substr((string) $r['city']['names']['en'], 0, 80) : null,
        ];
    }

    /** Download this month's database after the response is sent, if we don't have it yet. */
    public static function refreshIfDue(): void
    {
        try {
            $want = CarbonImmutable::now('UTC')->format('Y-m');
            if ((self::status()['edition'] ?? null) === $want && self::ready()) {
                return;
            }
            if (Cache::has('geoip-retry') || ! Cache::add('geoip-downloading', true, now()->addMinutes(20))) {
                return;
            }
            \Illuminate\Support\defer(function () {
                try {
                    self::download();
                } catch (\Throwable $e) {
                    Cache::put('geoip-retry', true, now()->addHours(6));
                    self::writeStatus(['error' => mb_substr($e->getMessage(), 0, 300), 'error_at' => now()->toIso8601String()] + self::status());
                    Log::warning('Location database download failed: '.$e->getMessage());
                } finally {
                    Cache::forget('geoip-downloading');
                }
            });
        } catch (\Throwable $e) {
            Log::warning('Location database check skipped: '.$e->getMessage());
        }
    }

    /** Fetch the newest monthly file (this month, else last month), unpack, check it, then swap it in. */
    public static function download(): string
    {
        @set_time_limit(900);
        $dir = dirname(self::path());
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $gz = $dir.'/download.mmdb.gz';
        $tmp = $dir.'/download.mmdb';
        $now = CarbonImmutable::now('UTC');
        $edition = null;
        foreach ([$now, $now->subMonthNoOverflow()] as $m) {
            $res = Http::timeout(600)->withOptions(['sink' => $gz])->get(sprintf(self::URL, $m->format('Y-m')));
            if ($res->successful() && filesize($gz) > 1_000_000) {
                $edition = $m->format('Y-m');
                break;
            }
        }
        if (! $edition) {
            @unlink($gz);
            throw new \RuntimeException('Could not download the location database from DB-IP.');
        }

        $in = gzopen($gz, 'rb');
        $out = fopen($tmp, 'wb');
        while (! gzeof($in)) {
            fwrite($out, (string) gzread($in, 1 << 20));
        }
        gzclose($in);
        fclose($out);
        @unlink($gz);

        $test = (new MmdbReader($tmp))->get('8.8.8.8');
        if (($test['country']['iso_code'] ?? null) === null) {
            @unlink($tmp);
            throw new \RuntimeException('The downloaded location database did not pass its check.');
        }
        self::$reader = null;
        rename($tmp, self::path());
        self::writeStatus(['edition' => $edition, 'downloaded_at' => now()->toIso8601String()]);
        Cache::forget('geoip-retry');

        return $edition;
    }

    private static function writeStatus(array $data): void
    {
        $f = storage_path('app/private/'.self::META);
        if (! is_dir(dirname($f))) {
            mkdir(dirname($f), 0755, true);
        }
        file_put_contents($f, json_encode($data));
    }
}
