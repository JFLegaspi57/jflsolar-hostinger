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
