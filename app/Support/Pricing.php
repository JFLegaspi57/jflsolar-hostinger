<?php

namespace App\Support;

use Carbon\CarbonImmutable;

final class Pricing
{
    public const TIME_ZONE = 'Asia/Manila';

    /** Today's date (Y-m-d) in the Philippines. */
    public static function manilaToday(?CarbonImmutable $now = null): string
    {
        return ($now ?? CarbonImmutable::now())->setTimezone(self::TIME_ZONE)->format('Y-m-d');
    }

    /**
     * Promotion state. Dates are inclusive calendar days in Asia/Manila: a promo
     * ending 2026-10-31 is active until 23:59:59 PHT that day.
     *
     * @return 'none'|'upcoming'|'active'|'ended'
     */
    public static function promoState(?string $start, ?string $end, ?string $today = null): string
    {
        $today ??= self::manilaToday();
        if (! $start && ! $end) {
            return 'none';
        }
        if ($start && $today < $start) {
            return 'upcoming';
        }
        if ($end && $today > $end) {
            return 'ended';
        }

        return 'active';
    }

    public static function formatPhp(float|int|string $amount): string
    {
        $n = (float) $amount;
        $decimals = floor($n) == $n ? 0 : 2;

        return '₱'.number_format($n, $decimals);
    }

    /**
     * What the public sees for a price, after promotion rules are applied.
     *
     * @return array{headline: string, hasAmount: bool, basis: ?string, isSample: bool, withdrawn: bool}
     */
    public static function display(string $mode, mixed $price, ?string $basis, bool $isSample, string $promo = 'none'): array
    {
        $withdrawn = $promo === 'ended';
        if ($withdrawn || $mode === 'quote' || $price === null) {
            return [
                'headline' => $withdrawn ? 'Promotion ended — request current price' : 'Price to be confirmed',
                'hasAmount' => false,
                'basis' => null,
                'isSample' => false,
                'withdrawn' => $withdrawn,
            ];
        }
        $amount = self::formatPhp($price);

        return [
            'headline' => $mode === 'starting' ? "From {$amount}" : $amount,
            'hasAmount' => true,
            'basis' => $basis,
            'isSample' => $isSample,
            'withdrawn' => false,
        ];
    }

    public static function formatDate(string $isoDate): string
    {
        return CarbonImmutable::createFromFormat('Y-m-d', substr($isoDate, 0, 10), 'UTC')->format('M j, Y');
    }

    public static function promoWindow(?string $start, ?string $end): ?string
    {
        if ($start && $end) {
            return self::formatDate($start).' – '.self::formatDate($end);
        }
        if ($end) {
            return 'Until '.self::formatDate($end);
        }
        if ($start) {
            return 'From '.self::formatDate($start);
        }

        return null;
    }

    public static function formatCapacity(mixed $value, string $unit): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $n = (float) $value;
        $text = floor($n) == $n ? (string) (int) $n : rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');

        return "{$text} {$unit}";
    }
}
