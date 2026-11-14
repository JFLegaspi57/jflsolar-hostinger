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

    /** Products a member can pick: everything visible in the public catalogue, by category. */
    public static function catalogue(): Collection
    {
        return PublicContent::snapshot()['products']->keyBy('id');
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

    /**
     * The quotation's lines, priced from today's catalogue.
     *
     * @return array{lines: list<array{i: int, kind: string, name: string, qty: int, unit: string, note: string, price: ?float, total: ?float, slug: ?string, missing: bool}>, total: float, unpriced: int}
     */
    public static function lines(MemberQuote $q): array
    {
        $catalogue = self::catalogue();
        $lines = [];
        foreach (array_values((array) $q->items) as $i => $item) {
            $qty = self::qty($item['qty'] ?? 1);
            if (($item['kind'] ?? '') === 'product') {
                $p = $catalogue->get((string) ($item['product_id'] ?? ''));
                $price = self::refPrice($p);
                $unit = $p && Shop::perMetre($p) ? ($qty === 1 ? 'metre' : 'metres') : ($qty === 1 ? 'piece' : 'pieces');
                $lines[] = ['i' => $i, 'kind' => 'product', 'name' => $p?->name ?? (string) ($item['name'] ?? 'Product'), 'qty' => $qty, 'unit' => $unit,
                    'note' => (string) ($item['note'] ?? ''), 'price' => $price, 'total' => $price === null ? null : round($price * $qty, 2),
                    'slug' => $p?->slug, 'missing' => $p === null];
            } else {
                $lines[] = ['i' => $i, 'kind' => 'custom', 'name' => (string) ($item['name'] ?? ''), 'qty' => $qty, 'unit' => (string) ($item['unit'] ?? ''),
                    'note' => (string) ($item['note'] ?? ''), 'price' => null, 'total' => null, 'slug' => null, 'missing' => false];
            }
        }
        $priced = array_filter($lines, fn ($l) => $l['total'] !== null);

        return ['lines' => $lines, 'total' => round(array_sum(array_column($priced, 'total')), 2), 'unpriced' => count($lines) - count($priced)];
    }

    /** Plain-text request for the staff inbox and email. */
    public static function message(MemberQuote $q): string
    {
        $sum = self::lines($q);
        $out = [self::NAME.' '.$q->reference().': '.$q->title, ''];
        foreach ($sum['lines'] as $n => $l) {
            $qty = $l['qty'].($l['unit'] !== '' ? ' '.$l['unit'] : '');
            $price = $l['kind'] === 'custom' ? 'to be quoted (own item)' : ($l['price'] === null ? 'price to be confirmed' : Pricing::formatPhp($l['price']).' each = '.Pricing::formatPhp($l['total']));
            $out[] = ($n + 1).'. '.$l['name'].($l['missing'] ? ' (no longer listed on the website)' : '').' — '.$qty.' — '.$price;
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
