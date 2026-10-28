<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class Catalog
{
    /** @return array{q: string, category: string, brand: string} */
    public static function filters(Request $request): array
    {
        $first = fn ($v) => is_array($v) ? (string) ($v[0] ?? '') : (string) ($v ?? '');

        return [
            'q' => mb_substr(trim($first($request->query('q'))), 0, 80),
            'category' => mb_substr(trim($first($request->query('category'))), 0, 80),
            'brand' => mb_substr(trim($first($request->query('brand'))), 0, 60),
        ];
    }

    private static function norm(string $s): string
    {
        return mb_strtolower(Str::ascii($s));
    }

    /** Case/accent-insensitive search across name, brand, model, SKU, category, summary and spec values; every word must match. */
    public static function filter(Collection $products, array $f): Collection
    {
        $terms = array_values(array_filter(preg_split('/\s+/', self::norm($f['q']))));

        return $products->filter(function ($p) use ($f, $terms) {
            if ($f['category'] !== '' && $p->category?->slug !== $f['category']) {
                return false;
            }
            if ($f['brand'] !== '' && mb_strtolower((string) $p->brand) !== mb_strtolower($f['brand'])) {
                return false;
            }
            if (! $terms) {
                return true;
            }
            $specs = collect($p->specs ?? [])->map(fn ($s) => ($s['label'] ?? '').' '.($s['value'] ?? ''))->all();
            $hay = self::norm(implode(' ', array_filter([$p->name, $p->brand, $p->model, $p->sku, $p->category?->name, $p->summary, ...$specs])));
            foreach ($terms as $t) {
                if (! str_contains($hay, $t)) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    public static function brands(Collection $products): array
    {
        $brands = $products->pluck('brand')->filter()->unique()->values()->all();
        natcasesort($brands);

        return array_values($brands);
    }
}
