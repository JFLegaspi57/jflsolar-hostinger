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
    private const TABLES_KEY = 'member-tables-v1';

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
            if (! Schema::hasTable('members') || ! Schema::hasTable('member_password_resets')) {
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
}
