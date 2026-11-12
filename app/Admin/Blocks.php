<?php

namespace App\Admin;

/** Plain-language names for content blocks, so staff never need to know keys. */
final class Blocks
{
    public const STANDARD = [
        'about.intro' => 'About page — introduction',
        'about.history' => 'About page — our story',
        'about.mission' => 'About page — mission',
        'about.vision' => 'About page — vision',
        'about.founder' => 'About page — founder profile',
        'why.quality' => 'Why choose us — quality',
        'why.warranty' => 'Why choose us — warranty assistance',
        'why.support' => 'Why choose us — technical support',
        'why.after_sales' => 'Why choose us — after-sales',
    ];

    public const SECTIONS = [
        'about' => 'About page',
        'why' => 'Why choose us (home page and About page)',
        'brand' => 'Brand page banner (key brand.{brand}, e.g. brand.goodwe; the image is the banner)',
    ];

    public static function isStandard(string $key): bool
    {
        return array_key_exists($key, self::STANDARD);
    }

    public static function placement(string $key, string $section): string
    {
        if ($section === 'brand' && str_starts_with($key, 'brand.')) {
            $slug = substr($key, 6);
            $brand = \App\Support\BrandArticles::ARTICLES[$slug]['brand'] ?? null;

            return $brand ? $brand.' brand page (/brands/'.$slug.') — banner poster' : 'Brand page banner — no brand page has the key '.$key;
        }

        return self::STANDARD[$key] ?? (self::SECTIONS[$section] ?? $section).' — additional block';
    }

    /** Public visibility in words, matching how the site actually behaves. */
    public static function visibility(bool $published, string $claimStatus): array
    {
        if (! $published) {
            return ['text' => 'Hidden', 'tone' => 'hidden'];
        }
        if ($claimStatus !== 'confirmed') {
            return ['text' => 'Preview only — awaiting confirmation', 'tone' => 'pending'];
        }

        return ['text' => 'Shown on the website', 'tone' => 'shown'];
    }
}
