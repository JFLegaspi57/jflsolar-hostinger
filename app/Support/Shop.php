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
        'pending_payment' => 'Awaiting online payment',
        'reserved' => 'Reserved — pay at pickup',
        'paid' => 'Paid — to prepare',
        'ready' => 'Ready for pickup',
        'completed' => 'Picked up',
        'cancelled' => 'Cancelled',
    ];

    /** Ways to pay, in the order offered: in person at pickup, online through PayMongo. */
    public const PAYMENT_MODES = ['in_person' => 'Pay in person when you pick up', 'online' => 'Pay online now'];

    /**
     * Ways to pay without PayMongo that the customer picks at checkout, with what they are told.
     * GCash and bank details are sent by staff when they confirm the order (not shown on the site).
     */
    public const IN_PERSON_CHOICES = [
        'cash' => ['label' => 'Cash', 'hint' => 'Pay in cash when you pick up.'],
        'gcash' => ['label' => 'GCash', 'hint' => 'Pay by GCash before or at pickup. We send our GCash number when we confirm your order.'],
        'bank_transfer' => ['label' => 'Bank transfer', 'hint' => 'Transfer before pickup. We send our bank details when we confirm your order.'],
        'check' => ['label' => 'Check', 'hint' => 'Pay by check when you pick up.'],
        // 'only': the one pickup place where this way to pay is possible (the card terminal is there).
        'card' => ['label' => 'Card (credit or debit)', 'hint' => 'Pay by credit or debit card when you pick up — available at our warehouse only.', 'only' => 'warehouse'],
    ];

    /** @return list<string> pay-at-pickup choices offered at checkout */
    public static function inPersonChoices(?\App\Models\SiteSetting $settings = null): array
    {
        if (! self::inPersonPayment()) {
            return [];
        }
        $list = array_values(array_intersect((array) config('shop.in_person_methods'), array_keys(self::IN_PERSON_CHOICES)));
        $list = $list ?: array_keys(self::IN_PERSON_CHOICES);
        if ($settings) {
            // Drop ways to pay tied to a pickup place that is not offered (e.g. card without the warehouse).
            $places = self::pickupLocations($settings);
            $list = array_values(array_filter($list, fn ($c) => ! isset(self::IN_PERSON_CHOICES[$c]['only']) || isset($places[self::IN_PERSON_CHOICES[$c]['only']])));
        }

        return $list;
    }

    /** The pickup place a way to pay is limited to, if any (card → warehouse). */
    public static function choiceOnlyAt(string $choice): ?string
    {
        return self::IN_PERSON_CHOICES[$choice]['only'] ?? null;
    }

    /** @return list<string> every choice on the checkout form: pay-at-pickup methods, then "online" */
    public static function paymentChoices(?\App\Models\SiteSetting $settings = null): array
    {
        return array_merge(self::inPersonChoices($settings), self::onlinePayment() ? ['online'] : []);
    }

    public static function choiceLabel(?string $choice): string
    {
        return $choice === 'online' ? 'Online (PayMongo)' : (self::IN_PERSON_CHOICES[$choice]['label'] ?? self::methodName($choice));
    }

    /** How staff record a payment taken at the counter. */
    public const COUNTER_METHODS = ['cash' => 'Cash', 'gcash' => 'GCash', 'maya' => 'Maya', 'card' => 'Card', 'bank_transfer' => 'Bank transfer', 'check' => 'Check'];

    /** Online payment is possible: both PayMongo values are set. */
    public static function onlinePayment(): bool
    {
        return config('shop.paymongo.secret_key') !== '' && config('shop.paymongo.webhook_secret') !== '';
    }

    public static function inPersonPayment(): bool
    {
        return (bool) config('shop.pay_in_person');
    }

    /** @return list<string> payment modes customers can choose right now */
    public static function paymentModes(): array
    {
        return array_keys(array_filter(['in_person' => self::inPersonPayment(), 'online' => self::onlinePayment()]));
    }

    /** The shop is live for customers: switched on, and at least one way to pay is available. */
    public static function enabled(): bool
    {
        return (bool) config('shop.enabled') && self::paymentModes() !== [];
    }

    /**
     * @return array<string, string> pickup places customers can choose: key => address.
     * Only the places switched on in config (default: warehouse); if none of those has an
     * address yet, any place with an address, so checkout never breaks.
     */
    public static function pickupLocations(\App\Models\SiteSetting $settings): array
    {
        $all = array_filter(['warehouse' => (string) $settings->warehouse_address, 'office' => (string) $settings->office_address], fn ($a) => trim($a) !== '');
        $chosen = array_intersect_key($all, array_flip((array) config('shop.pickup_locations', ['warehouse'])));

        return $chosen ?: $all;
    }

    /** "our warehouse", "our office" or "our office or warehouse". */
    public static function pickupWhere(\App\Models\SiteSetting $settings): string
    {
        $places = self::pickupLocations($settings);

        return count($places) > 1 ? 'our office or warehouse' : 'our '.strtolower(self::LOCATION_NAMES[array_key_first($places) ?? 'office']);
    }

    public const LOCATION_NAMES = ['warehouse' => 'Warehouse', 'office' => 'Office'];

    /** Name customers search on Google Maps or Waze for directions to the pickup place. */
    public const MAPS_NAME = 'JFL Solar & Renewables';

    /** @return array{google: string, waze: string} map links that search MAPS_NAME */
    public static function directionLinks(): array
    {
        $q = rawurlencode(self::MAPS_NAME);

        return ['google' => 'https://www.google.com/maps/search/?api=1&query='.$q, 'waze' => 'https://waze.com/ul?q='.$q.'&navigate=yes'];
    }

    /**
     * Staff preview: the shop is off, but the visitor is signed in to the admin, so they
     * see the cart and checkout (no order is saved, nothing is charged).
     */
    public static function previewing(): bool
    {
        if (self::enabled() || ! config('shop.staff_preview')) {
            return false;
        }
        $user = auth('web')->user();

        return $user !== null && $user->active && ! $user->must_change_password;
    }

    /** Cart and checkout pages are shown: shop on, or a staff preview. */
    public static function visible(): bool
    {
        return self::enabled() || self::previewing();
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

    public const METHOD_NAMES = ['gcash' => 'GCash', 'paymaya' => 'Maya', 'card' => 'card', 'qrph' => 'QR Ph', 'grab_pay' => 'GrabPay', 'shopee_pay' => 'ShopeePay', 'billease' => 'BillEase', 'dob' => 'online banking', 'brankas_bdo' => 'BDO online', 'brankas_landbank' => 'Landbank online', 'brankas_metrobank' => 'Metrobank online', 'cash' => 'cash', 'maya' => 'Maya', 'bank_transfer' => 'bank transfer', 'check' => 'check'];

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

    /** Address of a pickup place (falls back to the first one listed). */
    public static function pickupAddress(\App\Models\SiteSetting $settings, ?string $location = null): string
    {
        $places = self::pickupLocations($settings);

        return $places[$location ?? ''] ?? (string) (reset($places) ?: $settings->office_address);
    }

    /** One line for product pages: how customers pay and collect. */
    public static function howToBuy(\App\Models\SiteSetting $settings): string
    {
        $where = self::pickupWhere($settings);
        $modes = self::paymentModes();

        $multi = count(self::pickupLocations($settings)) > 1;
        $offline = array_map(fn ($c) => ['cash' => 'cash', 'gcash' => 'GCash', 'bank_transfer' => 'bank transfer', 'check' => 'check', 'card' => $multi ? 'card (warehouse only)' : 'card'][$c], self::inPersonChoices($settings));
        $offlineText = count($offline) > 1 ? implode(', ', array_slice($offline, 0, -1)).' or '.end($offline) : ($offline[0] ?? '');

        return match (true) {
            $modes === ['in_person'] => "Order online, then pay by {$offlineText} and pick up at {$where}.",
            $modes === ['online'] => 'Pay online with '.self::methodsText().", then pick up at {$where}.",
            default => "Order online and pay by {$offlineText}, or online with ".self::methodsText().", then pick up at {$where}.",
        }.' Please call our Sales team before you come.';
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
        if (Cache::get('shop-tables-v4')) {
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
        Cache::forever('shop-tables-v4', true);
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
        // v2: pay in person at pickup, and the choice of pickup place.
        if (! Schema::hasColumn('orders', 'payment_mode')) {
            Schema::table('orders', function (Blueprint $t) {
                $t->string('payment_mode', 10)->default('online');
                $t->string('pickup_location', 10)->default('warehouse');
            });
        }
        // v3: how the customer said they will pay at pickup (cash, GCash, bank transfer, check).
        if (! Schema::hasColumn('orders', 'payment_preference')) {
            Schema::table('orders', function (Blueprint $t) {
                $t->string('payment_preference', 20)->nullable();
            });
        }
        // v4: the member account that placed the order (null for guest checkout).
        if (! Schema::hasColumn('orders', 'member_id')) {
            Schema::table('orders', function (Blueprint $t) {
                $t->unsignedBigInteger('member_id')->nullable()->index();
            });
        }
    }
}
