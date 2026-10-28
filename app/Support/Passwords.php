<?php

namespace App\Support;

/** Password rules for staff accounts. Long passphrases are encouraged over symbols. */
final class Passwords
{
    public const MIN = 10;

    public static function problem(?string $password, ?string $confirm, ?string $email = null): ?string
    {
        $password = (string) $password;
        if (mb_strlen($password) < self::MIN) {
            return 'Use at least '.self::MIN.' characters. A short sentence is easy to remember and hard to guess.';
        }
        if (mb_strlen($password) > 200) {
            return 'Use 200 characters or fewer.';
        }
        if ($password !== (string) $confirm) {
            return 'The two passwords do not match.';
        }
        $lower = mb_strtolower($password);
        if ($email && str_contains($lower, mb_strtolower(strtok($email, '@')))) {
            return 'Do not include your email name in your password.';
        }
        if (in_array($lower, ['jflsolar123', 'password123', '1234567890', 'qwertyuiop', 'jflsolar2026'], true) || preg_match('/^(.)\1+$/', $password)) {
            return 'That password is too easy to guess. Choose another.';
        }

        return null;
    }

    /** A readable temporary password an owner can pass on (the person must change it at first sign-in). */
    public static function temporary(): string
    {
        $words = ['solar', 'panel', 'sun', 'grid', 'volt', 'amber', 'river', 'cedar', 'harbor', 'maple', 'orbit', 'pilot', 'quartz', 'tide', 'ember'];
        $pick = fn () => $words[random_int(0, count($words) - 1)];

        return $pick().'-'.$pick().'-'.random_int(1000, 9999);
    }
}
