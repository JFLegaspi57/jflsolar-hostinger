<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

/**
 * The Gmail account (address + Google app password) the owner enters in Admin → Subscribers so the
 * automatic welcome email is sent from Gmail. Kept encrypted with the app key in storage (outside the
 * web folder, kept across deploys); takes the place of GMAIL_USERNAME / GMAIL_APP_PASSWORD. Never shown back.
 */
final class GmailSender
{
    public static function file(): string
    {
        return storage_path('app/gmail-sender.enc');
    }

    public static function saved(): bool
    {
        return is_file(self::file());
    }

    /** @return array{user: string, pass: string}|null */
    public static function get(): ?array
    {
        if (! self::saved()) {
            return null;
        }
        try {
            $v = json_decode(Crypt::decryptString((string) file_get_contents(self::file())), true);

            return is_array($v) && ($v['user'] ?? '') !== '' && ($v['pass'] ?? '') !== '' ? ['user' => (string) $v['user'], 'pass' => (string) $v['pass']] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function set(string $user, string $pass): void
    {
        @mkdir(dirname(self::file()), 0755, true);
        // Google shows app passwords in groups of four ("abcd efgh ijkl mnop"); the spaces are not part of it.
        file_put_contents(self::file(), Crypt::encryptString(json_encode(['user' => trim($user), 'pass' => preg_replace('/\s+/', '', $pass)])), LOCK_EX);
        @chmod(self::file(), 0600);
        self::apply();
    }

    public static function forget(): void
    {
        @unlink(self::file());
        config(['mail.mailers.gmail.username' => env('GMAIL_USERNAME'), 'mail.mailers.gmail.password' => env('GMAIL_APP_PASSWORD')]);
        Mail::purge('gmail');
    }

    /** Uses the saved Gmail account (called at start-up). */
    public static function apply(): void
    {
        if ($g = self::get()) {
            config(['mail.mailers.gmail.username' => $g['user'], 'mail.mailers.gmail.password' => $g['pass']]);
            Mail::purge('gmail');
        }
    }
}
