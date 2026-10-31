<?php

namespace App\Support;

use App\Models\Member;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/** Customer (member) accounts: tables and the signed-in member. */
final class Members
{
    private const TABLES_KEY = 'member-tables-v3';

    private static ?bool $hasTables = null;

    /** The signed-in member, if their account is active (a disabled account is signed out). */
    public static function current(): ?Member
    {
        if (! self::hasTables()) {
            return null;
        }
        $guard = Auth::guard('member');
        $member = $guard->user();
        if (! $member) {
            return null;
        }
        // Signed out when disabled, or when the password changed since this session signed in
        // (a password reset or change ends every other session of the account).
        $seen = session('member_pw');
        if (! $member->isActive() || (is_string($seen) && ! hash_equals($seen, self::passwordStamp($member)))) {
            $guard->logout();
            session()->forget('member_pw');

            return null;
        }
        if (! is_string($seen)) {
            self::stampSession($member);
        }

        return $member;
    }

    private static function passwordStamp(Member $member): string
    {
        return hash_hmac('sha256', 'member-session|'.$member->id.'|'.$member->password, (string) config('app.key'));
    }

    /** Call after signing a member in or changing their password (in this browser). */
    public static function stampSession(Member $member): void
    {
        session(['member_pw' => self::passwordStamp($member)]);
    }

    public static function hasTables(): bool
    {
        return self::$hasTables ??= Schema::hasTable('members');
    }

    public static function flushState(): void
    {
        self::$hasTables = null;
    }

    /** Creates the member tables on first use (no SSH on Hostinger, so no migration step). */
    public static function ensureTables(): void
    {
        if (Cache::get(self::TABLES_KEY)) {
            return;
        }
        try {
            self::createTables();
        } catch (\Throwable $e) {
            if (! Schema::hasTable('members') || ! Schema::hasTable('member_password_resets') || ! Schema::hasColumn('members', 'tiktok_url') || ! Schema::hasTable('member_activity')) {
                throw $e;   // not the "someone else created them at the same moment" case
            }
        }
        self::$hasTables = true;
        Cache::forever(self::TABLES_KEY, true);
    }

    private static function createTables(): void
    {
        if (! Schema::hasTable('members')) {
            Schema::create('members', function (Blueprint $t) {
                $t->id();
                $t->string('name', 100);
                $t->string('email', 254)->unique();
                $t->string('phone', 30)->nullable();
                $t->string('password');
                $t->rememberToken();
                $t->string('status', 10)->default('active');   // active | disabled
                $t->timestamp('last_login_at')->nullable();
                $t->timestamps();
            });
        }
        // v2: profile photo and social links (seen only by the member and staff).
        if (! Schema::hasColumn('members', 'tiktok_url')) {
            Schema::table('members', function (Blueprint $t) {
                $t->string('photo', 80)->nullable();
                $t->string('facebook_url', 300)->nullable();
                $t->string('youtube_url', 300)->nullable();
                $t->string('instagram_url', 300)->nullable();
                $t->string('tiktok_url', 300)->nullable();
            });
        }
        // v3: sign-ins (time, approximate place) and pages viewed while signed in.
        if (! Schema::hasTable('member_activity')) {
            Schema::create('member_activity', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('member_id');
                $t->string('kind', 10);          // login | page
                $t->string('path', 255)->nullable();
                $t->string('place', 140)->nullable();   // "City, Country" from the internet address (address not kept)
                $t->timestamp('created_at')->useCurrent();
                $t->index(['member_id', 'kind', 'id']);
                $t->index('created_at');
            });
        }
        if (! Schema::hasTable('member_password_resets')) {
            Schema::create('member_password_resets', function (Blueprint $t) {
                $t->string('email', 254)->primary();
                $t->string('token');
                $t->timestamp('created_at')->nullable();
            });
        }
    }

    public static function forgetTables(): void
    {
        Cache::forget(self::TABLES_KEY);
        self::$hasTables = null;
    }

    /** Social profiles a member can add: field => [label, allowed web addresses, how a bare @name becomes a link]. */
    public const SOCIALS = [
        'facebook_url' => ['Facebook', ['facebook.com', 'www.facebook.com', 'm.facebook.com', 'web.facebook.com', 'fb.com'], 'https://www.facebook.com/%s'],
        'youtube_url' => ['YouTube', ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be'], 'https://www.youtube.com/@%s'],
        'instagram_url' => ['Instagram', ['instagram.com', 'www.instagram.com'], 'https://www.instagram.com/%s'],
        'tiktok_url' => ['TikTok', ['tiktok.com', 'www.tiktok.com', 'vm.tiktok.com'], 'https://www.tiktok.com/@%s'],
    ];

    /**
     * A social link as typed (full link, link without https://, or @name) → a clean https link,
     * '' for empty, or null when it isn't a link to that site.
     */
    public static function socialUrl(string $field, string $input): ?string
    {
        [, $hosts, $pattern] = self::SOCIALS[$field];
        $input = trim($input);
        if ($input === '') {
            return '';
        }
        // "@name" is always a username; a bare "name" too, unless it looks like a web address.
        if (preg_match('/^(@?)([A-Za-z0-9._-]{2,60})$/', $input, $m) && ! str_contains($m[2], '..') && ($m[1] === '@' || ! preg_match('/\.[a-z]{2,6}$/i', $m[2]))) {
            $m[1] = $m[2];
            return sprintf($pattern, $m[1]);
        }
        if (! preg_match('#^https?://#i', $input)) {
            $input = 'https://'.$input;
        }
        $parts = parse_url($input);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');
        if (! $parts || ! in_array($host, $hosts, true) || trim($path, '/') === '' || isset($parts['user']) || isset($parts['port'])
            || preg_match('/[\s<>"\'\\\\]/', $input) || strlen($input) > 300) {
            return null;
        }

        return 'https://'.$host.$path.(isset($parts['query']) ? '?'.$parts['query'] : '');
    }

    public static function photoDir(Member $member): string
    {
        return storage_path('app/members/'.$member->id);
    }

    public static function photoUrl(Member $member): ?string
    {
        return $member->photo ? '/account/photo/'.$member->id.'/'.$member->photo : null;
    }

    /** Removes the member's photo files (all of them). */
    public static function deletePhotos(Member $member): void
    {
        foreach (glob(self::photoDir($member).'/*.webp') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir(self::photoDir($member));
    }

    /** Activity older than this is deleted (same as the visitor cookie lifetime). */
    public const ACTIVITY_DAYS = 400;

    /**
     * Records a sign-in or a page view for a member, with the approximate place worked out
     * from the internet address (the address itself is not stored). Never breaks the page.
     */
    public static function logActivity(Member $member, string $kind, ?string $path, ?string $ip): void
    {
        try {
            if (! Schema::hasTable('member_activity')) {
                return;
            }
            $geo = \App\Support\GeoIp::lookup($ip);
            $place = $geo ? trim(($geo['city'] ? $geo['city'].', ' : '').$geo['country_name']) : null;
            \Illuminate\Support\Facades\DB::table('member_activity')->insert([
                'member_id' => $member->id, 'kind' => $kind, 'path' => $path !== null ? mb_substr($path, 0, 255) : null,
                'place' => $place ? mb_substr($place, 0, 140) : null, 'created_at' => now(),
            ]);
            if (random_int(1, 200) === 1) {
                \Illuminate\Support\Facades\DB::table('member_activity')->where('created_at', '<', now()->subDays(self::ACTIVITY_DAYS))->delete();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[members] activity not recorded: '.$e->getMessage());
        }
    }

    /** Latest sign-in (time and place) for each of these members: [member_id => row]. */
    public static function lastLogins(array $memberIds): array
    {
        if (! $memberIds || ! Schema::hasTable('member_activity')) {
            return [];
        }
        $ids = \Illuminate\Support\Facades\DB::table('member_activity')->where('kind', 'login')->whereIn('member_id', $memberIds)
            ->selectRaw('max(id) as id')->groupBy('member_id')->pluck('id');

        return \Illuminate\Support\Facades\DB::table('member_activity')->whereIn('id', $ids)->get()->keyBy('member_id')->all();
    }
}
