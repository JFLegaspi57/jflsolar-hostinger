<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Online shop: which products can be bought online, the visitor's cart (kept in the
 * session as product id => quantity) and the order tables.
 *
 * Prices are never taken from the browser: every cart line is priced from the product
 * as it is published right now, and items that stop being sellable drop out of the cart.
 */
final class Shop
{
    public const SESSION_KEY = 'cart';

    /** Order statuses, in the order they normally happen. */
    public const STATUSES = [
        'pending_payment' => 'Awaiting payment',
        'paid' => 'Paid — to prepare',
        'ready' => 'Ready for pickup',
        'completed' => 'Picked up',
        'cancelled' => 'Cancelled',
    ];

    /** Cart and checkout are live only when switched on and PayMongo is configured. */
    public static function enabled(): bool
    {
        return (bool) config('shop.enabled')
            && config('shop.paymongo.secret_key') !== ''
            && config('shop.paymongo.webhook_secret') !== '';
    }

    private static ?bool $hasTables = null;

    /** Forget the per-request "tables exist" answer (tests, after dropping tables). */
    public static function flushState(): void
    {
        self::$hasTables = null;
    }

    /** Whether the order tables exist (never creates them). */
    public static function hasTables(): bool
    {
        return self::$hasTables ??= Schema::hasTable('orders');
    }

    /**
     * Order pages and Admin → Orders work while the shop is on, and afterwards for orders
     * already taken. A site that never switched the shop on shows and creates nothing.
     */
    public static function ordersVisible(): bool
    {
        return self::enabled() || self::hasTables();
    }

    /** Fixed-price, published, real (not sample) products that are available to order. */
    public static function sellable(?Product $p): bool
    {
        return $p !== null
            && $p->pricing_mode === 'fixed'
            && $p->price_php !== null && (float) $p->price_php > 0
            && ! $p->price_is_sample
            && ! $p->is_sample
            && $p->availability === 'available';
    }

    /** Products priced per metre (e.g. cable, conduit) are sold in whole metres. */
    public static function perMetre(Product $p): bool
    {
        return (bool) preg_match('/\bper\s*(met(er|re)s?|m)\b/i', (string) $p->price_basis);
    }

    public static function unitLabel(Product $p, int $qty = 1): string
    {
        return self::perMetre($p) ? ($qty === 1 ? 'metre' : 'metres') : ($qty === 1 ? 'piece' : 'pieces');
    }

    /** @return array<string, int> product id => quantity */
    public static function raw(): array
    {
        $cart = session(self::SESSION_KEY, []);

        return is_array($cart) ? array_filter(array_map('intval', $cart), fn ($q) => $q > 0) : [];
    }

    public static function put(array $cart): void
    {
        session([self::SESSION_KEY => array_filter($cart, fn ($q) => $q > 0)]);
    }

    public static function clampQuantity(mixed $qty): int
    {
        return max(0, min((int) config('shop.max_quantity', 500), (int) $qty));
    }

    /** Sellable products currently visible on the site, by id. */
    public static function catalog(): \Illuminate\Support\Collection
    {
        return PublicContent::snapshot()['products']->filter(fn ($p) => self::sellable($p))->keyBy('id');
    }

    /**
     * The cart priced from the current catalog. Lines for items no longer sellable are
     * dropped (and reported in "removed") so the shopper never pays for something stale.
     *
     * @return array{lines: list<array{product: Product, qty: int, unit_price: float, line_total: float, unit: string}>, total: float, count: int, removed: list<string>}
     */
    public static function summary(): array
    {
        $catalog = self::catalog();
        $lines = [];
        $removed = [];
        $cart = self::raw();
        foreach ($cart as $id => $qty) {
            $p = $catalog->get($id);
            if (! $p) {
                $removed[] = $id;
                unset($cart[$id]);

                continue;
            }
            $qty = self::clampQuantity($qty);
            $price = round((float) $p->price_php, 2);
            $lines[] = ['product' => $p, 'qty' => $qty, 'unit_price' => $price, 'line_total' => round($price * $qty, 2), 'unit' => self::unitLabel($p, $qty)];
        }
        if ($removed) {
            self::put($cart);
        }

        return [
            'lines' => $lines,
            'total' => round(array_sum(array_column($lines, 'line_total')), 2),
            'count' => count($lines),
            'removed' => $removed,
        ];
    }

    public const METHOD_NAMES = ['gcash' => 'GCash', 'paymaya' => 'Maya', 'card' => 'card', 'qrph' => 'QR Ph', 'grab_pay' => 'GrabPay', 'shopee_pay' => 'ShopeePay', 'billease' => 'BillEase', 'dob' => 'online banking', 'brankas_bdo' => 'BDO online', 'brankas_landbank' => 'Landbank online', 'brankas_metrobank' => 'Metrobank online'];

    public static function methodName(?string $method): string
    {
        return self::METHOD_NAMES[$method] ?? (string) $method;
    }

    /** "GCash, Maya, card or QR Ph" — the payment methods switched on in config/shop.php. */
    public static function methodsText(): string
    {
        $list = array_values(array_map(fn ($m) => self::methodName($m), (array) config('shop.paymongo.methods')));
        if (count($list) < 2) {
            return $list[0] ?? 'online payment';
        }
        $last = array_pop($list);

        return implode(', ', $list).' or '.$last;
    }

    /** Where online orders are collected: the warehouse, or the office if no warehouse is listed. */
    public static function pickupAddress(\App\Models\SiteSetting $settings): string
    {
        return (string) ($settings->warehouse_address ?: $settings->office_address);
    }

    /** Fingerprint of what the customer saw at checkout (items, quantities, prices). */
    public static function fingerprint(array $summary): string
    {
        $parts = array_map(fn ($l) => $l['product']->id.':'.$l['qty'].':'.number_format($l['unit_price'], 2, '.', ''), $summary['lines']);

        return hash_hmac('sha256', implode('|', $parts), (string) config('app.key'));
    }

    /** Number of different items in the cart, for the header link (no catalog lookup). */
    public static function count(): int
    {
        return count(self::raw());
    }

    /** Human order number, e.g. JFL-261031-K7QD (no 0/O/1/I to avoid misreading). */
    public static function newReference(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < 4; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return 'JFL-'.now(Pricing::TIME_ZONE)->format('ymd').'-'.$code;
    }

    public static function newToken(): string
    {
        return Str::random(40);
    }

    /** Create the order tables the first time they are needed (no phpMyAdmin step). */
    public static function ensureTables(): void
    {
        if (Cache::get('shop-tables-v1')) {
            return;
        }
        try {
            self::createTables();
        } catch (\Throwable $e) {
            // Two first requests at the same moment: the other one created the tables.
            if (! Schema::hasTable('orders') || ! Schema::hasTable('order_items')) {
                throw $e;
            }
        }
        self::$hasTables = true;
        Cache::forever('shop-tables-v1', true);
    }

    private static function createTables(): void
    {
        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $t) {
                $t->id();
                $t->string('reference', 24)->unique();
                $t->char('access_token', 40);
                $t->string('status', 20)->default('pending_payment')->index();
                $t->string('customer_name', 100);
                $t->string('customer_phone', 30);
                $t->string('customer_email', 254);
                $t->text('customer_notes')->nullable();
                $t->string('fulfilment', 20)->default('pickup');
                $t->decimal('total_php', 12, 2);
                $t->string('paymongo_checkout_id', 64)->nullable()->index();
                $t->string('paymongo_payment_id', 64)->nullable();
                $t->string('payment_method', 40)->nullable();
                $t->decimal('amount_paid_php', 12, 2)->nullable();
                $t->timestamp('paid_at')->nullable();
                $t->timestamp('emailed_at')->nullable();
                $t->text('staff_notes')->nullable();
                $t->unsignedInteger('lock_version')->default(0);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $t) {
                $t->id();
                $t->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $t->uuid('product_id')->nullable();
                $t->string('name', 140);
                $t->string('brand', 60)->nullable();
                $t->string('model', 80)->nullable();
                $t->string('unit', 10);
                $t->decimal('unit_price_php', 12, 2);
                $t->unsignedInteger('quantity');
                $t->decimal('line_total_php', 12, 2);
            });
        }
    }
}
