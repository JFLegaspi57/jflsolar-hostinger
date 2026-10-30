<?php

namespace App\Support;

/**
 * Customer recommendations copied from the JFL Solar Facebook page
 * (https://www.facebook.com/jflsolar/reviews). Real reviews only — update
 * resources/data/facebook-reviews.json by hand, never invent entries.
 */
final class Reviews
{
    public const SOURCE_URL = 'https://www.facebook.com/jflsolar/reviews';

    private const MAX_CHARS = 260;

    /** @return list<array{name: string, text: string}> */
    public static function all(): array
    {
        static $all;
        if ($all !== null) {
            return $all;
        }
        $saved = json_decode((string) @file_get_contents(resource_path('data/facebook-reviews.json')), true);
        // Reviews from the live Facebook check first (full text), then the saved list; same review only once.
        $rows = array_merge(FacebookReviews::items(), is_array($saved) ? $saved : []);
        $all = [];
        $seen = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ! isset($row[1]) || trim((string) $row[1]) === '') {
                continue;
            }
            $key = mb_substr(mb_strtolower(preg_replace('/[^\p{L}\p{N}]+/u', '', (string) $row[1])), 0, 60);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $all[] = ['name' => self::shortName((string) ($row[0] ?? '')), 'text' => self::excerpt((string) $row[1])];
        }

        return $all;
    }

    /** A few reviews chosen at random for this page view. */
    public static function random(int $count = 3): array
    {
        $all = self::all();
        if (count($all) <= $count) {
            return $all;
        }
        $keys = (array) array_rand($all, $count);
        shuffle($keys);

        return array_map(fn ($k) => $all[$k], $keys);
    }

    /** "Francis Caesar M Pira" → "Francis P." (first name and last initial only). */
    public static function shortName(string $name): string
    {
        if (trim($name) === '') {
            return 'Facebook customer';   // Facebook sometimes hides the reviewer's name
        }
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        if (count($parts) < 2) {
            return trim($name);
        }
        $last = end($parts);

        return $parts[0].' '.mb_strtoupper(mb_substr($last, 0, 1)).'.';
    }

    /** Drop Facebook's "… See more" and keep reviews short, ending on a full sentence where possible. */
    public static function excerpt(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        $cut = (bool) preg_match('/…\s*See more$/u', $text);
        $text = trim(preg_replace('/…\s*See more$/u', '', $text));
        if (! $cut && mb_strlen($text) <= self::MAX_CHARS) {
            return $text;
        }
        $head = mb_substr($text, 0, self::MAX_CHARS);
        // Last sentence end that leaves a reasonable amount of text.
        // A full stop after a title ("Mr.", "Engr.", "Prof.") is not the end of a sentence.
        if (preg_match_all('/(?<!\bMr)(?<!\bMrs)(?<!\bMs)(?<!\bDr)(?<!\bEngr)(?<!\bProf)(?<!\bSr)(?<!\bJr)[.!?](?=\s|$)/u', $head, $m, PREG_OFFSET_CAPTURE)) {
            $end = end($m[0])[1];
            $byteHead = substr($head, 0, $end + 1);
            if (mb_strlen($byteHead) >= 80) {
                return trim($byteHead).' …';
            }
        }

        return rtrim(preg_replace('/\s+\S*$/u', '', $head), ' ,;:-').' …';
    }
}
