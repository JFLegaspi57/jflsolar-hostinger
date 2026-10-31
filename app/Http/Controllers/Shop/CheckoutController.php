<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\OrderPayments;
use App\Support\PayMongo;
use App\Support\PublicContent;
use App\Support\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        return view('pages.checkout', ['summary' => $summary, 'settings' => PublicContent::settings(), 'fingerprint' => Shop::fingerprint($summary)]);
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
        ];
        $errors = [];
        if (mb_strlen($values['name']) < 2 || mb_strlen($values['name']) > 100) {
            $errors['name'] = 'Please enter your full name.';
        }
        $digits = preg_replace('/\D/', '', $values['phone']);
        if (! preg_match('/^[0-9+()\s-]{7,30}$/', $values['phone']) || strlen($digits) < 7 || strlen($digits) > 15) {
            $errors['phone'] = 'Please enter a mobile or phone number we can call, e.g. 0917 123 4567.';
        }
        if (! filter_var($values['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($values['email']) > 254) {
            $errors['email'] = 'Please enter a valid email address — your receipt is sent there.';
        }
        if (mb_strlen($values['notes']) > 1000) {
            $errors['notes'] = 'Notes must be 1,000 characters or fewer.';
        }
        if ($request->input('agree') !== '1') {
            $errors['agree'] = 'Please confirm you will pick up the items, and agree to the terms.';
        }
        if ($summary['total'] < self::MIN_TOTAL) {
            $errors['total'] = 'Online orders must be at least ₱'.self::MIN_TOTAL.'.';
        }
        if ($summary['total'] > (float) config('shop.max_total')) {
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

        if (Shop::previewing()) {
            return redirect('/checkout')->withInput($values)
                ->with('checkout_preview', 'Staff preview: the form is filled in correctly. Customers would now go to PayMongo’s payment page. No order was saved and nothing was charged — online payment is not switched on yet.');
        }

        Shop::ensureTables();
        $order = DB::transaction(function () use ($values, $summary) {
            $order = Order::query()->create([
                'reference' => $this->uniqueReference(),
                'access_token' => Shop::newToken(),
                'status' => 'pending_payment',
                'customer_name' => $values['name'],
                'customer_phone' => $values['phone'],
                'customer_email' => $values['email'],
                'customer_notes' => $values['notes'] ?: null,
                'fulfilment' => 'pickup',
                'total_php' => $summary['total'],
            ]);
            foreach ($summary['lines'] as $line) {
                $p = $line['product'];
                $order->items()->create([
                    'product_id' => $p->id,
                    'name' => $p->name,
                    'brand' => $p->brand,
                    'model' => $p->model,
                    'unit' => Shop::perMetre($p) ? 'm' : 'pc',
                    'unit_price_php' => $line['unit_price'],
                    'quantity' => $line['qty'],
                    'line_total_php' => $line['line_total'],
                ]);
            }

            return $order;
        });

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

        return view('pages.order', ['order' => $order->load('items'), 'settings' => PublicContent::settings(), 'checks' => $checks]);
    }

    private function find(string $reference, string $token): ?Order
    {
        if (! preg_match('/^JFL-\d{6}-[A-Z0-9]{4}$/', $reference) || strlen($token) !== 40 || ! Shop::hasTables()) {
            return null;
        }
        $order = Order::query()->where('reference', $reference)->first();

        return $order && hash_equals($order->access_token, $token) ? $order : null;
    }

    private function uniqueReference(): string
    {
        do {
            $ref = Shop::newReference();
        } while (Order::query()->where('reference', $ref)->exists());

        return $ref;
    }
}
