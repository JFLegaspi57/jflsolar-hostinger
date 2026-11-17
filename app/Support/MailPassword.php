<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * The website mailbox password (info@jflsolar.ph), entered by the owner in Admin → Subscribers instead of
 * editing .env by hand. Kept encrypted with the app key in storage (outside the web folder, kept across deploys),
 * and used instead of MAIL_PASSWORD when it is set. Never shown back on any page.
 */
final class MailPassword
{
    public static function file(): string
    {
        return storage_path('app/mail-password.enc');
    }

    public static function saved(): bool
    {
        return is_file(self::file());
    }

    public static function get(): ?string
    {
        if (! self::saved()) {
            return null;
        }
        try {
            $value = Crypt::decryptString((string) file_get_contents(self::file()));

            return $value === '' ? null : $value;
        } catch (\Throwable $e) {
            Log::warning('[mail] saved mailbox password could not be read');

            return null;
        }
    }

    public static function set(string $password): void
    {
        $dir = dirname(self::file());
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        file_put_contents(self::file(), Crypt::encryptString($password), LOCK_EX);
        @chmod(self::file(), 0600);
        self::apply();
    }

    public static function forget(): void
    {
        @unlink(self::file());
    }

    /** Uses the saved password for the website mailbox (called at start-up). */
    public static function apply(): void
    {
        if ($pw = self::get()) {
            config(['mail.mailers.smtp.password' => $pw]);
            Mail::purge('smtp');
        }
    }
}
