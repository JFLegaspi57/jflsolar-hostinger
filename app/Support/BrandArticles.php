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

    /** Our Brands page (/brands): group => [heading, short line], then each brand's group and one-line summary. */
    public const GROUPS = [
        'inverters' => ['Inverters', 'The heart of the solar PV system: it turns solar DC power into AC power for your home and manages the battery.'],
        'panels' => ['Solar panels', 'N-type bifacial panels that make more power from the same roof space.'],
        'batteries' => ['Batteries', 'LiFePO4 home batteries that store solar power for the night and for brownouts.'],
        'protection' => ['Safety and protection', 'Rapid shutdown and surge protection for a safer rooftop system.'],
    ];

    public const OVERVIEW = [
        'goodwe' => ['inverters', 'Hybrid inverters and Lynx batteries from one of the world’s largest inverter makers, with a strong support team in the Philippines.'],
        'solis' => ['inverters', 'Hybrid and grid-tie inverters for homes and businesses, with a local office and service in the Philippines.'],
        'sungrow' => ['inverters', 'One of the most trusted inverter makers in the world. We carry the MG RL single-phase hybrid inverters.'],
        'luxpower' => ['inverters', 'Practical hybrid and off-grid inverters with generator and smart-load ports, popular with installers.'],
        'jinko-solar' => ['panels', 'Tiger Neo N-type TOPCon bifacial panels from one of the biggest panel makers in the world.'],
        'ae-solar' => ['panels', 'German-brand N-type bifacial panels with strong independent test results.'],
        'seraphim' => ['panels', 'N-type bifacial panels, a repeat Kiwa PVEL Top Performer, with large projects in the Philippines.'],
        'dyness' => ['batteries', 'PowerBrick LiFePO4 home batteries that work with GoodWe, Luxpower and Solis inverters.'],
        'alltopelec' => ['batteries', 'A 16.5 kWh LiFePO4 home battery with local presence in the Philippines.'],
        'projoy' => ['protection', 'Rapid shutdown (firefighter safety) switches and AC surge protectors.'],
    ];

    /**
     * Extra Our Brands cards: an article slug shown in a second group, or a brand without an article (by its /products brand name).
     * [slug or brand name, group, summary, product category slug or null, article section or null, tier or null]
     */
    public const OVERVIEW_EXTRA = [
        ['goodwe', 'batteries', 'The Lynx A G4, a 16 kWh LiFePO4 battery made for GoodWe ES-C10 hybrid inverters.', 'batteries', 'lynx', null],
        // Trina: BloombergNEF Tier 1 PV modules (Q1 2026) and S&P Global Tier 1 PV modules (2026).
        ['Trina Solar', 'panels', 'Vertex N i-TOPCon bifacial panels from one of the world’s largest panel makers.', 'solar-panels', null, 'Tier 1 panel maker'],
    ];

    /**
     * Tier classification of the inverter and solar panel brands (checked November 2026).
     * "Tier 1" here means listed by BloombergNEF and/or S&P Global; it measures bankability, not product quality.
     * [tier label, is Tier 1, list of [listing, period], sources [label => url], badge (optional)]
     * BloombergNEF publishes only a Tier 1 list; "Tier 2" and "Tier 3" are informal industry terms.
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
        'luxpower' => ['Tier 2 inverter maker (unofficial)', false, [
            ['Not listed by BloombergNEF or S&P Global as a Tier 1 inverter maker', '2026'],
            ['Established specialist inverter brand with over 350,000 systems installed in 100+ countries since 2017, which fits the industry’s informal “Tier 2” description', '2026'],
        ], ['S&P Global 2026 Tier 1 list (pv Europe)' => 'https://www.pveurope.eu/markets/sps-2026-tier-1-list-marks-maturing-supplier-market', 'BloombergNEF tier methodology' => 'https://www.solarchoice.net.au/wp-content/uploads/BNEF-tier-ranking-explanation.pdf', 'Tier 1, 2 and 3 explained (Sunhub)' => 'https://www.sunhub.com/blog/what-tier-1-solar-panels-means'], 'Tier 2'],
        'jinko-solar' => ['Tier 1 panel maker', true, [
            ['BloombergNEF Tier 1 PV module manufacturer', 'Q3 2026'],
        ], ['BloombergNEF Tier 1 module list, Q3 2026 (Vico Export)' => 'https://www.vicoexport.com/en/68446/tier-1-list-of-solar-panel-manufacturers-q3-2026/']],
        'ae-solar' => ['Tier 1 panel maker', true, [
            ['BloombergNEF Tier 1 PV module manufacturer', 'Q3 2026'],
        ], ['BloombergNEF Tier 1 module list, Q3 2026 (Vico Export)' => 'https://www.vicoexport.com/en/68446/tier-1-list-of-solar-panel-manufacturers-q3-2026/', '19th BloombergNEF Tier 1 listing (TaiyangNews)' => 'https://taiyangnews.info/pressreleases/aesolar-marks-19th-inclusion-in-bloombergnef-tier-1-pv-manufacturer-ranking']],
        'seraphim' => ['Tier 2 panel maker (unofficial; former Tier 1)', false, [
            ['Not on BloombergNEF’s Tier 1 PV module list for Q3 2026; was Tier 1 until Q1 2025', '2026'],
            ['Makes its own panels in China and abroad (about 13 GW a year), which fits the industry’s informal “Tier 2” description', '2024'],
            ['Kiwa PVEL Top Performer in independent reliability testing, sixth time', '2025'],
        ], ['BloombergNEF Tier 1 module list, Q3 2026 (Vico Export)' => 'https://www.vicoexport.com/en/68446/tier-1-list-of-solar-panel-manufacturers-q3-2026/', 'Kiwa PVEL Top Performer 2025 (pv magazine)' => 'https://www.pv-magazine.com/press-releases/seraphim-awarded-top-performer-in-kiwa-pvels-2025-pv-module-reliability-scorecard-for-sixth-time', 'BloombergNEF tier methodology' => 'https://www.solarchoice.net.au/wp-content/uploads/BNEF-tier-ranking-explanation.pdf', 'Tier 1, 2 and 3 explained (Sunhub)' => 'https://www.sunhub.com/blog/what-tier-1-solar-panels-means'], 'Tier 2'],
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
