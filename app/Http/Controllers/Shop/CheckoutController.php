<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Mail\NewOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\OrderPayments;
use App\Support\PayMongo;
use App\Support\PublicContent;
use App\Support\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/** Customer details → order saved → PayMongo's payment page → back to an order status page. */
class CheckoutController extends Controller
{
    /** PayMongo does not accept very small payments. */
    public const MIN_TOTAL = 100;

    public function show(): View|RedirectResponse
    {
        abort_unless(Shop::visible(), 404);
        $summary = Shop::summary();
        if (! $summary['lines']) {
            return redirect('/cart');
        }

        $settings = PublicContent::settings();

        return view('pages.checkout', [
            'summary' => $summary, 'settings' => $settings, 'fingerprint' => Shop::fingerprint($summary),
            'choices' => Shop::paymentChoices($settings), 'locations' => Shop::pickupLocations($settings),
            'member' => \App\Support\Members::current(),
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        abort_unless(Shop::visible(), 404);

        // Honeypot: people never see or fill this field.
        if (trim((string) $request->input('website', '')) !== '') {
            return redirect('/cart');
        }

        $summary = Shop::summary();
        if ($summary['removed']) {
            return redirect('/cart')->with('cart_error', 'Some items are no longer available online and were removed. Please check your cart.');
        }
        if (! $summary['lines']) {
            return redirect('/cart');
        }

        $values = [
            'name' => trim((string) $request->input('name', '')),
            'phone' => trim((string) $request->input('phone', '')),
            'email' => trim((string) $request->input('email', '')),
            'notes' => trim((string) $request->input('notes', '')),
            'payment' => (string) $request->input('payment', ''),
            'pickup' => (string) $request->input('pickup', ''),
        ];
        $settings = PublicContent::settings();
        $choices = Shop::paymentChoices($settings);
        $locations = Shop::pickupLocations($settings);
        if (count($choices) === 1) {
            $values['payment'] = $choices[0];
        }
        if (count($locations) === 1) {
            $values['pickup'] = array_key_first($locations);
        }
        $errors = [];
        if (! in_array($values['payment'], $choices, true)) {
            $errors['payment'] = 'Please choose how you will pay.';
        }
        if (! array_key_exists($values['pickup'], $locations)) {
            $errors['pickup'] = 'Please choose where you will pick up your order.';
        } elseif (($only = Shop::choiceOnlyAt($values['payment'])) && $values['pickup'] !== $only) {
            $errors['payment'] = 'Card payment is available only at our '.strtolower(Shop::LOCATION_NAMES[$only]).'. Choose '.strtolower(Shop::LOCATION_NAMES[$only]).' pickup or another way to pay.';
        }
        if (mb_strlen($values['name']) < 2 || mb_strlen($values['name']) > 100) {
            $errors['name'] = 'Please enter your full name.';
        }
        $digits = preg_replace('/\D/', '', $values['phone']);
        if (! preg_match('/^[0-9+()\s-]{7,30}$/', $values['phone']) || strlen($digits) < 7 || strlen($digits) > 15) {
            $errors['phone'] = 'Please enter a mobile or phone number we can call, e.g. 0917 123 4567.';
        }
        if (! filter_var($values['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($values['email']) > 254) {
            $errors['email'] = 'Please enter a valid email address — your order details are sent there.';
        }
        if (mb_strlen($values['notes']) > 1000) {
            $errors['notes'] = 'Notes must be 1,000 characters or fewer.';
        }
        if ($request->input('agree') !== '1') {
            $errors['agree'] = 'Please confirm you will pick up the items, and agree to the terms.';
        }
        if ($values['payment'] === 'online' && $summary['total'] < self::MIN_TOTAL) {
            $errors['total'] = 'Online orders must be at least ₱'.self::MIN_TOTAL.'.';
        }
        if ($values['payment'] === 'online' && $summary['total'] > (float) config('shop.max_total')) {
            $errors['total'] = 'Orders over ₱'.number_format((float) config('shop.max_total')).' can’t be paid online. Please request a quote and we’ll arrange payment with you.';
        }
        if ($errors) {
            return redirect('/checkout')->withInput($values)->withErrors($errors);
        }
        // The customer pays exactly what the checkout page showed: if a price or item changed
        // since then, show the page again with the new amounts.
        if (! hash_equals(Shop::fingerprint($summary), (string) $request->input('fingerprint'))) {
            return redirect('/checkout')->withInput($values)
                ->with('checkout_error', 'A price or item in your cart changed while you were checking out. Please review the new total, then continue to payment.');
        }

        $inPerson = $values['payment'] !== 'online';
        [$orderData, $itemsData] = $this->orderData($values, $summary, $inPerson);

        if (Shop::previewing()) {
            if (! $inPerson) {
                return redirect('/checkout')->withInput($values)
                    ->with('checkout_preview', 'Staff preview: the form is filled in correctly. Customers would now go to PayMongo’s payment page. No order was saved — the shop is not switched on for customers yet.');
            }
            // Show staff exactly what the customer would see, and send the staff email copy.
            $request->session()->put('shop_preview_order', ['order' => $orderData + ['reference' => 'JFL-PREVIEW', 'created_at' => now()->toIso8601String()], 'items' => $itemsData]);
            $this->alert($this->previewOrder(), true);
            Shop::put([]);   // like a real order: the basket is emptied once checked out

            return redirect('/checkout/preview');
        }

        // Reservations cost nothing to place, so one visitor may place only a few per day.
        $limitKey = 'shop-reserve:'.hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
        if ($inPerson && RateLimiter::tooManyAttempts($limitKey, max(1, (int) config('shop.reservations_per_day')))) {
            return redirect('/checkout')->withInput($values)
                ->with('checkout_error', 'You have placed several orders today. Please call or message us to add to them or place another.');
        }

        Shop::ensureTables();
        $order = DB::transaction(function () use ($orderData, $itemsData) {
            $order = Order::query()->create($orderData + ['reference' => $this->uniqueReference(), 'access_token' => Shop::newToken()]);
            foreach ($itemsData as $item) {
                $order->items()->create($item);
            }

            return $order;
        });

        if ($inPerson) {
            RateLimiter::hit($limitKey, 86400);
            $this->alert($order);
            Shop::put([]);   // the cart has become an order

            return redirect($order->publicPath())->with('order_placed', true);
        }

        try {
            $checkout = PayMongo::createCheckout($order);
        } catch (\Throwable $e) {
            Log::error('[shop] could not open PayMongo checkout', ['order' => $order->reference, 'error' => $e->getMessage()]);
            $order->delete();

            return redirect('/checkout')->withInput($values)
                ->with('checkout_error', 'We couldn’t open the payment page just now. Nothing was charged. Please try again in a few minutes, or contact us to order.');
        }
        $order->forceFill(['paymongo_checkout_id' => $checkout['id']])->save();

        return redirect()->away($checkout['url']);
    }

    /** @return array{0: array<string, mixed>, 1: list<array<string, mixed>>} the order's fields and its items */
    private function orderData(array $values, array $summary, bool $inPerson): array
    {
        $order = [
            'status' => $inPerson ? 'reserved' : 'pending_payment',
            'payment_mode' => $inPerson ? 'in_person' : 'online',
            'payment_preference' => $inPerson ? $values['payment'] : null,
            'pickup_location' => $values['pickup'],
            'customer_name' => $values['name'],
            'customer_phone' => $values['phone'],
            'customer_email' => $values['email'],
            'customer_notes' => $values['notes'] ?: null,
            'fulfilment' => 'pickup',
            'total_php' => $summary['total'],
        ];
        if (($member = \App\Support\Members::current()) && \Illuminate\Support\Facades\Schema::hasColumn('orders', 'member_id')) {
            $order['member_id'] = $member->id;   // only when signed in, so guest checkout never needs the column
        }
        $items = array_map(fn ($line) => [
            'product_id' => $line['product']->id,
            'name' => $line['product']->name,
            'brand' => $line['product']->brand,
            'model' => $line['product']->model,
            'unit' => Shop::perMetre($line['product']) ? 'm' : 'pc',
            'unit_price_php' => $line['unit_price'],
            'quantity' => $line['qty'],
            'line_total_php' => $line['line_total'],
        ], $summary['lines']);

        return [$order, $items];
    }

    /** Staff preview: the order the last test checkout would have created (never saved). */
    private function previewOrder(): ?Order
    {
        $data = session('shop_preview_order');
        if (! is_array($data) || ! Shop::previewing()) {
            return null;
        }
        $order = (new Order)->forceFill($data['order']);
        $order->setRelation('items', collect($data['items'])->map(fn ($i) => (new OrderItem)->forceFill($i)));

        return $order;
    }

    /** Staff preview of the confirmation page a customer sees after placing an order. */
    public function preview(): View
    {
        $order = $this->previewOrder() ?? abort(404);

        return view('pages.order', ['order' => $order, 'settings' => PublicContent::settings(), 'checks' => 0, 'preview' => true, 'printUrl' => '/checkout/preview/print']);
    }

    /** Printable order summary: the customer's copy (opens in its own window). */
    public function print(Request $request, string $reference): View
    {
        abort_unless(Shop::ordersVisible(), 404);
        $order = $this->find($reference, (string) $request->query('t')) ?? abort(404);

        return view('pages.order-print', ['order' => $order->load('items'), 'settings' => PublicContent::settings(), 'backUrl' => $order->publicPath(), 'preview' => false]);
    }

    public function previewPrint(): View
    {
        $order = $this->previewOrder() ?? abort(404);

        return view('pages.order-print', ['order' => $order, 'settings' => PublicContent::settings(), 'backUrl' => '/checkout', 'preview' => true]);
    }

    /** Where PayMongo sends the customer after paying. The webhook decides whether it is paid. */
    public function done(Request $request): RedirectResponse
    {
        abort_unless(Shop::ordersVisible(), 404);
        $order = $this->find((string) $request->query('order'), (string) $request->query('t'));
        if ($order) {
            Shop::put([]);   // the cart has become an order
        }

        return $order ? redirect($order->publicPath()) : redirect('/cart');
    }

    /** Private order status page (link in the success redirect; unguessable token). */
    public function status(Request $request, string $reference): View
    {
        abort_unless(Shop::ordersVisible(), 404);
        $order = $this->find($reference, (string) $request->query('t')) ?? abort(404);

        // Backup for a missed webhook: while the order still waits for payment, ask PayMongo
        // directly (at most every 20 seconds per order). The same checks apply as for the webhook.
        if ($order->status === 'pending_payment' && $order->paymongo_checkout_id
            && Cache::add('paymongo-check:'.$order->id, 1, 20)
            && ($session = PayMongo::fetchSession($order->paymongo_checkout_id))) {
            OrderPayments::record($order->reference, $session['session_id'], $session['payments']);
            $order->refresh();
        }
        $checks = max(0, min(30, (int) $request->query('r', 0)));

        return view('pages.order', ['order' => $order->load('items'), 'settings' => PublicContent::settings(), 'checks' => $checks, 'preview' => false, 'printUrl' => $order->printPath()]);
    }

    private function find(string $reference, string $token): ?Order
    {
        if (! preg_match('/^JFL-\d{6}-[A-Z0-9]{4}$/', $reference) || strlen($token) !== 40 || ! Shop::hasTables()) {
            return null;
        }
        $order = Order::query()->where('reference', $reference)->first();

        return $order && hash_equals($order->access_token, $token) ? $order : null;
    }

    /** Email the order summary to the staff inbox (new pay-at-pickup order, or a staff preview). A failure never loses the order. */
    private function alert(?Order $order, bool $preview = false): void
    {
        $to = array_values(array_filter(array_map('trim', explode(',', (string) config('shop.notify')))));
        if (! $order || ! $to || config('mail.default') === 'log') {
            return;
        }
        try {
            Mail::to($to)->send(new NewOrder($preview ? $order : $order->load('items'), $preview));
            if (! $preview) {
                $order->forceFill(['emailed_at' => now()])->save();
            }
        } catch (\Throwable $e) {
            Log::error('[shop] order email alert failed', ['order' => $order->reference, 'error' => $e::class]);
        }
    }

    private function uniqueReference(): string
    {
        do {
            $ref = Shop::newReference();
        } while (Order::query()->where('reference', $ref)->exists());

        return $ref;
    }
}
