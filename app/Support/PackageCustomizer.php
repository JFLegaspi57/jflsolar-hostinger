<?php

namespace App\Support;

use App\Models\Package;
use App\Models\Product;

/**
 * "Customize this package" on complete (installed) packages: the components linked to
 * products in the admin Components field (4th column, e.g. the solar panels and batteries)
 * can be changed by the visitor. The estimated price is the package price plus or minus
 * those parts at their current website prices; the final price is confirmed after the
 * site ocular inspection. Installed packages are never sold through the basket.
 */
final class PackageCustomizer
{
    /**
     * @return list<array{index: int, label: string, detail: string, quantity: int, options: list<Product>}>
     */
    public static function lines(Package $pkg): array
    {
        if (($pkg->package_type ?? '') !== 'installed_system' || empty($pkg->components) || self::basePrice($pkg) === null) {
            return [];
        }
        $bySlug = PublicContent::snapshot()['products']->filter(fn ($p) => (float) $p->price_php > 0 && ($p->pricing_mode ?? 'fixed') !== 'quote')->keyBy('slug');
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
                'quantity' => PackageBuilder::defaultQuantity($c['quantity'] ?? null),
                'options' => $options,
            ];
        }

        return $lines;
    }

    /** The package's own price, when it shows one (not "price on request" or an ended promo). */
    public static function basePrice(Package $pkg): ?float
    {
        $promo = $pkg->promo_state ?? $pkg->promoState();
        $shown = $pkg->price($promo);

        return $shown['hasAmount'] && (float) $pkg->price_php > 0 ? (float) $pkg->price_php : null;
    }

    /**
     * A visitor's choices (from ?q[i]=…&p[i]=…), clamped to sensible values, with the estimated total.
     *
     * @return array{lines: list<array{label: string, product: Product, quantity: int, base: int, change: float}>, total: float, changed: bool}
     */
    public static function choose(Package $pkg, array $lines, array $query): array
    {
        $q = is_array($query['q'] ?? null) ? $query['q'] : [];
        $p = is_array($query['p'] ?? null) ? $query['p'] : [];
        $total = (float) self::basePrice($pkg);
        $out = [];
        $changed = false;
        foreach ($lines as $line) {
            $i = $line['index'];
            $default = $line['options'][0];
            $pick = collect($line['options'])->firstWhere('slug', is_string($p[$i] ?? null) ? $p[$i] : '') ?? $default;
            $qty = array_key_exists($i, $q) && is_scalar($q[$i]) ? max(0, min(99, (int) $q[$i])) : $line['quantity'];
            $change = $qty * (float) $pick->price_php - $line['quantity'] * (float) $default->price_php;
            $changed = $changed || $qty !== $line['quantity'] || $pick->id !== $default->id;
            $total += $change;
            $out[] = ['label' => $line['label'], 'product' => $pick, 'quantity' => $qty, 'base' => $line['quantity'], 'change' => $change];
        }

        return ['lines' => $out, 'total' => max(0, $total), 'changed' => $changed];
    }

    /** Plain-text summary for the inquiry form. */
    public static function summary(Package $pkg, array $choice): string
    {
        $rows = ['Customized '.$pkg->name.':'];
        foreach ($choice['lines'] as $l) {
            $name = trim(($l['product']->brand && $l['product']->brand !== 'Generic' ? $l['product']->brand.' ' : '').$l['product']->name);
            $rows[] = '- '.$l['label'].': '.$l['quantity'].' × '.$name.($l['quantity'] !== $l['base'] ? ' (package has '.$l['base'].')' : '');
        }
        $rows[] = 'Estimated price: '.Pricing::formatPhp($choice['total']).' (final price after the site ocular inspection)';

        return implode("\n", $rows);
    }
}
