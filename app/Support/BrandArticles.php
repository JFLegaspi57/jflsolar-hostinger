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

    /**
     * Tier classification of the inverter and solar panel brands (checked November 2026).
     * "Tier 1" here means listed by BloombergNEF and/or S&P Global; it measures bankability, not product quality.
     * [tier label, is Tier 1, list of [listing, period], sources [label => url]]
     */
    public const TIERS = [
        'goodwe' => ['Tier 1 inverter maker', true, [
            ['BloombergNEF Tier 1 PV inverter manufacturer', 'Q3 2026'],
            ['S&P Global Energy Tier 1 PV inverter supplier', '2026'],
        ], ['BloombergNEF Tier 1, Q3 2026 (Renewable Mirror)' => 'https://www.renewablemirror.com/goodwe-included-in-bloombergnef-tier-1-power-inverter-list-for-sixth-consecutive-quarter/', 'S&P Global 2026 Tier 1 list (pv Europe)' => 'https://www.pveurope.eu/markets/sps-2026-tier-1-list-marks-maturing-supplier-market']],
        'solis' => ['Tier 1 inverter maker', true, [
            ['BloombergNEF Tier 1 PV inverter manufacturer', '2026'],
            ['S&P Global Energy Tier 1 PV inverter supplier (as Ginlong Technologies)', '2026'],
        ], ['Solis Tier 1 recognition (PV Tech, September 2026)' => 'https://www.pv-tech.org/industry-updates/solis-earns-tier-1-recognition-from-sp-global-energy-and-bloombergnef/', 'S&P Global 2026 Tier 1 list (pv Europe)' => 'https://www.pveurope.eu/markets/sps-2026-tier-1-list-marks-maturing-supplier-market']],
        'sungrow' => ['Tier 1 inverter maker', true, [
            ['S&P Global Energy Tier 1 PV inverter supplier', '2026'],
            ['No. 1 most bankable inverter brand, BloombergNEF bankability survey', '2025'],
        ], ['S&P Global 2026 Tier 1 list (pv Europe)' => 'https://www.pveurope.eu/markets/sps-2026-tier-1-list-marks-maturing-supplier-market', 'BloombergNEF bankability survey (Sungrow)' => 'https://www.sungrowpower.com/eu/en/sungrow-secures-top-rank-again-in-bloombergnef-bankability-survey']],
        'luxpower' => ['Not on the Tier 1 lists', false, [
            ['Not listed by BloombergNEF or S&P Global as a Tier 1 inverter maker', '2026'],
        ], ['S&P Global 2026 Tier 1 list (pv Europe)' => 'https://www.pveurope.eu/markets/sps-2026-tier-1-list-marks-maturing-supplier-market']],
        'jinko-solar' => ['Tier 1 panel maker', true, [
            ['BloombergNEF Tier 1 PV module manufacturer', 'Q3 2026'],
        ], ['BloombergNEF Tier 1 module list, Q3 2026 (Vico Export)' => 'https://www.vicoexport.com/en/68446/tier-1-list-of-solar-panel-manufacturers-q3-2026/']],
        'ae-solar' => ['Tier 1 panel maker', true, [
            ['BloombergNEF Tier 1 PV module manufacturer', 'Q3 2026'],
        ], ['BloombergNEF Tier 1 module list, Q3 2026 (Vico Export)' => 'https://www.vicoexport.com/en/68446/tier-1-list-of-solar-panel-manufacturers-q3-2026/', '19th BloombergNEF Tier 1 listing (TaiyangNews)' => 'https://taiyangnews.info/pressreleases/aesolar-marks-19th-inclusion-in-bloombergnef-tier-1-pv-manufacturer-ranking']],
        'seraphim' => ['Not on the current Tier 1 list', false, [
            ['Not on BloombergNEF’s Tier 1 PV module list for Q3 2026 (last listed Q1 2025)', '2026'],
            ['Kiwa PVEL Top Performer in independent reliability testing, sixth time', '2025'],
        ], ['BloombergNEF Tier 1 module list, Q3 2026 (Vico Export)' => 'https://www.vicoexport.com/en/68446/tier-1-list-of-solar-panel-manufacturers-q3-2026/', 'Kiwa PVEL Top Performer 2025 (pv magazine)' => 'https://www.pv-magazine.com/press-releases/seraphim-awarded-top-performer-in-kiwa-pvels-2025-pv-module-reliability-scorecard-for-sixth-time']],
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
