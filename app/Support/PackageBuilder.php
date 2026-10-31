<?php

namespace App\Support;

use App\Models\Package;

/**
 * "Build your package": a package's components that are linked to products sold online
 * (4th column of the admin Components field). Customers see each part's own price, pick
 * between the linked products, change quantities (0 = leave it out) and add the parts to
 * the basket as separate products, priced from the live catalog.
 */
final class PackageBuilder
{
    /**
     * @return list<array{index: int, label: string, detail: string, quantity: int, options: list<\App\Models\Product>}>
     */
    public static function lines(Package $pkg): array
    {
        if (! Shop::visible() || empty($pkg->components)) {
            return [];
        }
        $bySlug = Shop::catalog()->keyBy('slug');
        $lines = [];
        foreach (array_values($pkg->components) as $i => $c) {
            $options = collect($c['products'] ?? [])->map(fn ($slug) => $bySlug->get($slug))->filter()->values()->all();
            if (! $options) {
                continue;
            }
            $lines[] = [
                'index' => $i,
                'label' => (string) ($c['label'] ?? ''),
                'detail' => (string) ($c['detail'] ?? ''),
                'quantity' => self::defaultQuantity($c['quantity'] ?? null),
                'options' => $options,
            ];
        }

        return $lines;
    }

    /** Build Your Own page: main part categories, label and starting quantity. */
    public const CUSTOM_PARTS = [
        'inverters' => ['Hybrid Inverter', 1],
        'solar-panels' => ['Solar Panels', 10],
        'batteries' => ['Battery', 1],
    ];

    /**
     * Lines for the Build Your Own page: every product sold online in each main category
     * (cheapest first), starting from a typical small system the customer then changes.
     */
    /**
     * @param  array<string, array{0: string, 1: int}>  $preset  category slug => [product slug, quantity]
     *                                                         (from the PV Calculator); that product becomes the default.
     */
    public static function customLines(array $preset = []): array
    {
        if (! Shop::visible()) {
            return [];
        }
        $products = Shop::catalog();
        $categories = PublicContent::snapshot()['categories']->keyBy('slug');
        $lines = [];
        $i = 0;
        foreach (self::CUSTOM_PARTS as $slug => [$label, $qty]) {
            $category = $categories->get($slug);
            $options = $category ? $products->where('category_id', $category->id)->sortBy(fn ($p) => [(float) $p->price_php, mb_strtolower($p->name)])->values() : collect();
            if (isset($preset[$slug])) {
                [$pick, $count] = $preset[$slug];
                $chosen = $options->firstWhere('slug', $pick);
                if ($chosen) {
                    $options = $options->reject(fn ($p) => $p->id === $chosen->id)->prepend($chosen)->values();
                }
                $qty = Shop::clampQuantity($count);
            }
            if ($options->isNotEmpty()) {
                $lines[] = ['index' => $i, 'label' => $label, 'detail' => '', 'quantity' => $qty, 'options' => $options->all()];
            }
            $i++;
        }

        return $lines;
    }

    /** Size in kW, kWh or W read from a product's name or model ("6kW …", "16.5kWh …", "620W …"). */
    public static function rating(\App\Models\Product $p, string $unit): ?float
    {
        $text = $p->name.' '.$p->model;
        $re = match ($unit) {
            'kW' => '/(\d+(?:\.\d+)?)\s*kW(?!h)/i',
            'kWh' => '/(\d+(?:\.\d+)?)\s*kWh/i',
            default => '/(\d{3,4})\s*W(?!h)\b/i',
        };

        return preg_match($re, $text, $m) ? (float) $m[1] : null;
    }

    /**
     * Turn a PV Calculator result into a Build Your Own preset: the products we sell that fit
     * the recommended inverter, panels and battery. Empty when nothing suitable is sold online.
     *
     * @return array<string, array{0: string, 1: int}>
     */
    public static function presetFromCalculator(array $in, array $r): array
    {
        if (! Shop::visible()) {
            return [];
        }
        $products = Shop::catalog();
        $cats = PublicContent::snapshot()['categories']->keyBy('slug');
        $in_ = fn (string $slug) => ($c = $cats->get($slug)) ? $products->where('category_id', $c->id)->values() : collect();
        $preset = [];

        // Inverter: the smallest one sold that covers the recommended size (hybrid for hybrid/off-grid).
        $needKw = (float) ($r['inverter']['kw'] ?? 0) * max(1, (int) ($r['inverter']['count'] ?? 1));
        $inverters = $in_('inverters')->map(fn ($p) => [$p, self::rating($p, 'kW')])->filter(fn ($x) => $x[1] > 0)
            ->filter(fn ($x) => ($in['type'] ?? '') === 'offgrid' ? true : ! str_contains(mb_strtolower($x[0]->name), 'off-grid'))
            ->sortBy(fn ($x) => [$x[1], (float) $x[0]->price_php])->values();
        if ($inverters->isNotEmpty() && $needKw > 0) {
            $fit = $inverters->first(fn ($x) => $x[1] >= $needKw - 0.01);
            [$p, $kw] = $fit ?? $inverters->last();
            $preset['inverters'] = [$p->slug, $fit ? 1 : (int) ceil($needKw / $kw)];
        }

        // Panels: the wattage chosen in the calculator if we sell it, else the closest; enough for the kWp.
        $panels = $in_('solar-panels')->map(fn ($p) => [$p, self::rating($p, 'W')])->filter(fn ($x) => $x[1] > 0)
            ->sortBy(fn ($x) => [abs($x[1] - (float) ($in['panel'] ?? 620)), (float) $x[0]->price_php])->values();
        if ($panels->isNotEmpty() && ($r['kwp'] ?? 0) > 0) {
            [$p, $w] = $panels->first();
            $preset['solar-panels'] = [$p->slug, (int) max(1, ceil($r['kwp'] * 1000 / $w - 0.001))];
        }

        // Battery: none for grid-tied; else the model that covers the kWh for the lowest total price.
        $needKwh = (float) ($r['battery'] ?? 0);
        $units = fn (float $kwh) => $needKwh > 0 ? (int) max(1, ceil($needKwh / $kwh - 0.05)) : 1;
        $batteries = $in_('batteries')->map(fn ($p) => [$p, self::rating($p, 'kWh')])->filter(fn ($x) => $x[1] > 0)
            ->sortBy(fn ($x) => [$units($x[1]) * (float) $x[0]->price_php, -$x[1]])->values();
        if ($batteries->isNotEmpty()) {
            [$p, $kwh] = $batteries->first();
            $preset['batteries'] = [$p->slug, $needKwh > 0 ? $units($kwh) : 0];
        }

        return $preset;
    }

    /** /packages/build-your-own link carrying a preset. */
    public static function presetUrl(array $preset, array $summary = []): string
    {
        $q = [];
        foreach (['inverters' => ['inverter', 'iq'], 'solar-panels' => ['panel', 'pq'], 'batteries' => ['battery', 'bq']] as $slug => [$k, $qk]) {
            if (isset($preset[$slug])) {
                $q[$k] = $preset[$slug][0];
                $q[$qk] = $preset[$slug][1];
            }
        }

        return '/packages/build-your-own?'.http_build_query($q + $summary).'#build';
    }

    /** Read a preset back from the Build Your Own query string. */
    public static function presetFromQuery(array $query): array
    {
        $preset = [];
        foreach (['inverters' => ['inverter', 'iq'], 'solar-panels' => ['panel', 'pq'], 'batteries' => ['battery', 'bq']] as $slug => [$k, $qk]) {
            $pick = is_string($query[$k] ?? null) && preg_match('/^[a-z0-9-]{1,160}$/', $query[$k]) ? $query[$k] : null;
            $qty = isset($query[$qk]) && is_string($query[$qk]) && ctype_digit($query[$qk]) ? (int) $query[$qk] : null;
            if ($pick !== null && $qty !== null) {
                $preset[$slug] = [$pick, $qty];
            }
        }

        return $preset;
    }

    /** Product categories offered as optional add-ons under "Build your package", in this order. */
    public const ADDON_CATEGORIES = ['mounting-hardware', 'wire-and-cables', 'ac-safety-devices', 'dc-safety-devices', 'accessories'];

    /**
     * Optional add-ons: every sellable product in the add-on categories (not already a main part),
     * grouped by category.
     *
     * @param  list<array>  $lines  from lines()
     * @return list<array{category: \App\Models\ProductCategory, products: list<\App\Models\Product>}>
     */
    public static function addons(array $lines): array
    {
        if (! $lines) {
            return [];
        }
        $taken = collect($lines)->flatMap(fn ($l) => collect($l['options'])->pluck('id'))->all();
        $products = Shop::catalog()->reject(fn ($p) => in_array($p->id, $taken, true));
        $categories = PublicContent::snapshot()['categories']->keyBy('slug');
        $groups = [];
        foreach (self::ADDON_CATEGORIES as $slug) {
            $category = $categories->get($slug);
            if (! $category) {
                continue;
            }
            $items = $products->where('category_id', $category->id)->sortBy(fn ($p) => mb_strtolower($p->name), SORT_NATURAL)->values()->all();
            if ($items) {
                $groups[] = ['category' => $category, 'products' => $items];
            }
        }

        return $groups;
    }

    /** "10 pcs" → 10, "1 unit" → 1, empty → 1. */
    public static function defaultQuantity(?string $text): int
    {
        return preg_match('/\d+/', (string) $text, $m) ? max(1, Shop::clampQuantity($m[0])) : 1;
    }

    /** Price of the parts at their default choices and quantities. */
    public static function defaultTotal(array $lines): float
    {
        return array_sum(array_map(fn ($l) => (float) $l['options'][0]->price_php * $l['quantity'], $lines));
    }
}
