<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;

/** Brand articles at /brands/{slug}. The brand logos on /products open these pages. */
final class BrandArticles
{
    /** slug => display name and the brand value used by the /products filter (same order as the logo strip). */
    public const ARTICLES = [
        'goodwe' => ['brand' => 'GoodWe', 'filter' => 'GoodWe'],
        'jinko-solar' => ['brand' => 'Jinko Solar', 'filter' => 'Jinko Solar'],
        'solis' => ['brand' => 'Solis', 'filter' => 'Solis'],
        'luxpower' => ['brand' => 'Luxpower', 'filter' => 'LuxPower'],
        'seraphim' => ['brand' => 'Seraphim', 'filter' => 'Seraphim'],
        'ae-solar' => ['brand' => 'AE Solar', 'filter' => 'AE Solar'],
        'sungrow' => ['brand' => 'Sungrow', 'filter' => 'Sungrow'],
        'dyness' => ['brand' => 'Dyness', 'filter' => 'Dyness'],
        'alltopelec' => ['brand' => 'Alltopelec (ATE)', 'filter' => 'Alltopelec (ATE)'],
        'projoy' => ['brand' => 'Projoy Electric', 'filter' => 'Projoy'],
    ];

    /** Articles that are written (a view exists), so unfinished ones never show up or get linked. */
    public static function available(): array
    {
        return array_filter(self::ARTICLES, fn ($slug) => View::exists('pages.brands.'.$slug), ARRAY_FILTER_USE_KEY);
    }

    /** Route constraint for the slugs above. */
    public static function slugPattern(): string
    {
        return implode('|', array_map('preg_quote', array_keys(self::ARTICLES)));
    }

    /** Article address for a /products brand filter value, or null when the brand has no article. */
    public static function urlForFilter(string $filter): ?string
    {
        foreach (self::available() as $slug => $a) {
            if (strcasecmp($a['filter'], $filter) === 0) {
                return '/brands/'.$slug;
            }
        }

        return null;
    }

    /** The brand's published products (prices and availability come from the shop). */
    public static function products(Collection $products, string $slug): Collection
    {
        $filter = self::ARTICLES[$slug]['filter'];

        return $products->filter(fn ($p) => strcasecmp((string) $p->brand, $filter) === 0)->values();
    }
}
