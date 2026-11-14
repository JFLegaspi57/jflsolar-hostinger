<?php

namespace App\Support;

use App\Models\MemberQuote;
use App\Models\Product;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * "Make a Quotation": registered members list products from the catalogue (current website price
 * filled in) and items of their own (priced by JFL Solar), then send the list as a quote request.
 * A sent request lands in Admin → Inquiries like the contact form, so staff handle it the usual way.
 */
final class Quotations
{
    public const NAME = 'Make a Quotation';

    public const MAX_QUOTES = 30;           // per member

    public const MAX_LINES = 60;            // per quotation

    public const MAX_QTY = 10000;

    public const LIMITS = ['title' => 120, 'location' => 120, 'phone' => 30, 'notes' => 2000, 'name' => 160, 'unit' => 30, 'note' => 200];

    public const SEND_LIMIT = 5;            // sends per member per hour

    private const TABLES_KEY = 'quotation-tables-v1';

    public static function ensureTables(): void
    {
        if (Cache::get(self::TABLES_KEY)) {
            return;
        }
        try {
            if (! Schema::hasTable('member_quotes')) {
                Schema::create('member_quotes', function (Blueprint $t) {
                    $t->id();
                    $t->unsignedBigInteger('member_id')->index();
                    $t->string('title', 120);
                    $t->string('location', 120)->nullable();
                    $t->string('phone', 30)->nullable();
                    $t->text('notes')->nullable();
                    $t->json('items')->nullable();          // list of lines, see self::lines()
                    $t->timestamp('sent_at')->nullable();   // last time it was sent to JFL Solar
                    $t->unsignedBigInteger('inquiry_id')->nullable();
                    $t->boolean('edited_after_send')->default(false);
                    $t->timestamps();
                });
            }
        } catch (\Throwable $e) {
            if (! Schema::hasTable('member_quotes')) {
                throw $e;
            }
        }
        Cache::forever(self::TABLES_KEY, true);
    }

    /** Every product visible in the public catalogue (used to show lines already on a quotation). */
    public static function catalogue(): Collection
    {
        return PublicContent::snapshot()['products']->keyBy('id');
    }

    /** Products a member can add: only those marked "Available to order". */
    public static function available(): Collection
    {
        return self::catalogue()->filter(fn (Product $p) => $p->availability === 'available');
    }

    /** Website price used as a reference (null = "to be quoted": quote-only, sample or no price). */
    public static function refPrice(?Product $p): ?float
    {
        if (! $p || $p->pricing_mode !== 'fixed' || $p->price_php === null || (float) $p->price_php <= 0 || $p->price_is_sample || ($p->promo_state ?? 'none') === 'ended') {
            return null;
        }

        return round((float) $p->price_php, 2);
    }

    public static function clean(mixed $raw, int $max, bool $multiline = false): string
    {
        return Inquiries::clean($raw, $max, $multiline);
    }

    public static function qty(mixed $raw): int
    {
        return max(1, min(self::MAX_QTY, (int) (is_numeric($raw) ? $raw : 1)));
    }

    /** Category order of the shop: [category id => position]; own items and unlisted products come last. */
    public static function categoryOrder(): array
    {
        return PublicContent::snapshot()['categories']->values()->mapWithKeys(fn ($c, $n) => [$c->id => $n])->all();
    }

    public const OWN_ITEMS = 'Your own items';

    /**
     * The quotation's lines, priced from today's catalogue, arranged by category in the shop's order
     * (the order the member added them is kept within a category). 'i' is the line's place in the saved list.
     *
     * @return array{lines: list<array{i: int, kind: string, name: string, qty: int, unit: string, note: string, price: ?float, total: ?float, slug: ?string, missing: bool}>, total: float, unpriced: int}
     */
    public static function lines(MemberQuote $q): array
    {
        $catalogue = self::catalogue();
        $order = self::categoryOrder();
        $lines = [];
        foreach (array_values((array) $q->items) as $i => $item) {
            $qty = self::qty($item['qty'] ?? 1);
            if (($item['kind'] ?? '') === 'product') {
                $p = $catalogue->get((string) ($item['product_id'] ?? ''));
                $price = self::refPrice($p);
                $unit = $p && Shop::perMetre($p) ? ($qty === 1 ? 'metre' : 'metres') : ($qty === 1 ? 'piece' : 'pieces');
                $unavailable = $p !== null && $p->availability !== 'available';
                $price = $unavailable ? null : $price;
                $lines[] = ['i' => $i, 'kind' => 'product', 'name' => $p?->name ?? (string) ($item['name'] ?? 'Product'), 'qty' => $qty, 'unit' => $unit,
                    'note' => (string) ($item['note'] ?? ''), 'price' => $price, 'total' => $price === null ? null : round($price * $qty, 2),
                    'slug' => $p?->slug, 'missing' => $p === null, 'unavailable' => $unavailable,
                    'category' => $p?->category?->name ?? 'Other products', 'sort' => $p ? ($order[$p->category_id] ?? 900) : 950];
            } else {
                $lines[] = ['i' => $i, 'kind' => 'custom', 'name' => (string) ($item['name'] ?? ''), 'qty' => $qty, 'unit' => (string) ($item['unit'] ?? ''),
                    'note' => (string) ($item['note'] ?? ''), 'price' => null, 'total' => null, 'slug' => null, 'missing' => false, 'unavailable' => false,
                    'category' => self::OWN_ITEMS, 'sort' => 1000];
            }
        }
        usort($lines, fn ($a, $b) => [$a['sort'], $a['i']] <=> [$b['sort'], $b['i']]);
        $priced = array_filter($lines, fn ($l) => $l['total'] !== null);

        return ['lines' => $lines, 'total' => round(array_sum(array_column($priced, 'total')), 2), 'unpriced' => count($lines) - count($priced)];
    }

    /** Plain-text request for the staff inbox and email. */
    public static function message(MemberQuote $q): string
    {
        $sum = self::lines($q);
        $out = [self::NAME.' '.$q->reference().': '.$q->title];
        $category = null;
        foreach ($sum['lines'] as $n => $l) {
            if ($l['category'] !== $category) {
                $category = $l['category'];
                $out[] = '';
                $out[] = mb_strtoupper($category);
            }
            $qty = $l['qty'].($l['unit'] !== '' ? ' '.$l['unit'] : '');
            $price = $l['kind'] === 'custom' ? 'to be quoted (own item)' : ($l['price'] === null ? 'price to be confirmed' : Pricing::formatPhp($l['price']).' each = '.Pricing::formatPhp($l['total']));
            $out[] = ($n + 1).'. '.$l['name'].($l['missing'] ? ' (no longer listed on the website)' : '').($l['unavailable'] ? ' (currently not available)' : '').' — '.$qty.' — '.$price;
            if ($l['note'] !== '') {
                $out[] = '   Note: '.$l['note'];
            }
        }
        $out[] = '';
        $out[] = 'Estimated total of website prices: '.Pricing::formatPhp($sum['total']).($sum['unpriced'] ? ' (plus '.$sum['unpriced'].' item(s) to be quoted)' : '');
        if (trim((string) $q->notes) !== '') {
            $out[] = '';
            $out[] = 'Notes from the customer:';
            $out[] = (string) $q->notes;
        }

        return implode("\n", $out);
    }
}
