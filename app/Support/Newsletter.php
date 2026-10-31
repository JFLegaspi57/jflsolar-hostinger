<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Promo / news email list. People join from the footer form, at account sign-up or on
 * their account page, and can leave with one click (unsubscribe link). Staff see and
 * export the list in Admin → Subscribers; emails are sent from the business's own email.
 */
final class Newsletter
{
    private const TABLES_KEY = 'newsletter-tables-v1';

    public const SOURCES = ['footer' => 'Website footer', 'signup' => 'Account sign-up', 'account' => 'Account page'];

    public static function hasTables(): bool
    {
        return Schema::hasTable('newsletter_subscribers');
    }

    public static function ensureTables(): void
    {
        if (Cache::get(self::TABLES_KEY)) {
            return;
        }
        try {
            if (! Schema::hasTable('newsletter_subscribers')) {
                Schema::create('newsletter_subscribers', function (Blueprint $t) {
                    $t->id();
                    $t->string('email', 254)->unique();
                    $t->string('name', 100)->nullable();
                    $t->string('source', 20);
                    $t->char('token', 40)->unique();
                    $t->timestamp('subscribed_at');
                    $t->timestamp('unsubscribed_at')->nullable()->index();
                    $t->timestamps();
                });
            }
        } catch (\Throwable $e) {
            if (! Schema::hasTable('newsletter_subscribers')) {
                throw $e;
            }
        }
        Cache::forever(self::TABLES_KEY, true);
    }

    public static function forgetTables(): void
    {
        Cache::forget(self::TABLES_KEY);
    }

    /** Adds (or re-adds) an email. Returns false if it was already subscribed. */
    public static function subscribe(string $email, ?string $name, string $source): bool
    {
        self::ensureTables();
        $email = mb_strtolower(trim($email));
        $row = DB::table('newsletter_subscribers')->where('email', $email)->first();
        if ($row && ! $row->unsubscribed_at) {
            return false;
        }
        $now = now();
        if ($row) {
            DB::table('newsletter_subscribers')->where('id', $row->id)->update(['unsubscribed_at' => null, 'subscribed_at' => $now, 'source' => $source, 'name' => $name ? mb_substr($name, 0, 100) : $row->name, 'updated_at' => $now]);
        } else {
            DB::table('newsletter_subscribers')->insertOrIgnore(['email' => $email, 'name' => $name ? mb_substr($name, 0, 100) : null, 'source' => $source, 'token' => Str::random(40), 'subscribed_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        }

        return true;
    }

    public static function unsubscribeEmail(string $email): void
    {
        if (self::hasTables()) {
            DB::table('newsletter_subscribers')->where('email', mb_strtolower(trim($email)))->whereNull('unsubscribed_at')->update(['unsubscribed_at' => now(), 'updated_at' => now()]);
        }
    }

    /** One-click leave from an email link. Returns the email, or null for an unknown link. */
    public static function unsubscribeToken(string $token): ?string
    {
        if (strlen($token) !== 40 || ! self::hasTables()) {
            return null;
        }
        $row = DB::table('newsletter_subscribers')->where('token', $token)->first();
        if (! $row) {
            return null;
        }
        if (! $row->unsubscribed_at) {
            DB::table('newsletter_subscribers')->where('id', $row->id)->update(['unsubscribed_at' => now(), 'updated_at' => now()]);
        }

        return $row->email;
    }

    public static function isSubscribed(string $email): bool
    {
        return self::hasTables() && DB::table('newsletter_subscribers')->where('email', mb_strtolower(trim($email)))->whereNull('unsubscribed_at')->exists();
    }

    public static function unsubscribeUrl(string $token): string
    {
        return Site::url('/newsletter/unsubscribe/'.$token);
    }
}
