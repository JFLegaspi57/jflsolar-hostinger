<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\PayMongo;
use App\Support\PublicContent;
use App\Support\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        abort_unless(Shop::enabled(), 404);
        $summary = Shop::summary();
        if (! $summary['lines']) {
            return redirect('/cart');
        }

        return view('pages.checkout', ['summary' => $summary, 'settings' => PublicContent::settings()]);
    }

    public function submit(Request $request): RedirectResponse
    {
        abort_unless(Shop::enabled(), 404);

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
        if ($errors) {
            return redirect('/checkout')->withInput($values)->withErrors($errors);
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
        $order = $this->find((string) $request->query('order'), (string) $request->query('t'));
        if ($order) {
            Shop::put([]);   // the cart has become an order
        }

        return $order ? redirect($order->publicPath()) : redirect('/cart');
    }

    /** Private order status page (link in the success redirect; unguessable token). */
    public function status(Request $request, string $reference): View
    {
        $order = $this->find($reference, (string) $request->query('t')) ?? abort(404);

        return view('pages.order', ['order' => $order->load('items'), 'settings' => PublicContent::settings()]);
    }

    private function find(string $reference, string $token): ?Order
    {
        if (! preg_match('/^JFL-\d{6}-[A-Z0-9]{4}$/', $reference) || strlen($token) !== 40) {
            return null;
        }
        Shop::ensureTables();
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
