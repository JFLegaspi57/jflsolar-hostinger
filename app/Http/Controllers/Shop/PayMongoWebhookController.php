<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Mail\NewOrder;
use App\Models\Order;
use App\Support\PayMongo;
use App\Support\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * PayMongo → this site, signed with the webhook secret. Marks an order paid only when the
 * signature is valid, the session matches the order, and the full amount was paid.
 * Safe to receive twice (PayMongo retries): a paid order is left as it is.
 */
class PayMongoWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $raw = $request->getContent();
        if (! PayMongo::signatureValid($raw, (string) $request->header('Paymongo-Signature', ''))) {
            Log::warning('[shop] PayMongo webhook with an invalid signature was ignored');

            return response('invalid signature', 400);
        }
        $event = PayMongo::parseEvent((array) json_decode($raw, true));
        if ($event['type'] !== 'checkout_session.payment.paid') {
            return response('ignored', 200);
        }

        Shop::ensureTables();
        $paidNow = DB::transaction(function () use ($event) {
            $order = Order::query()->where('reference', $event['reference'])->lockForUpdate()->first();
            if (! $order || $order->paymongo_checkout_id !== $event['session_id']) {
                Log::warning('[shop] PayMongo payment for an unknown order', ['reference' => $event['reference'], 'session' => $event['session_id']]);

                return null;
            }
            if ($order->status !== 'pending_payment') {
                return null;   // already handled (retry) or changed by staff
            }
            $paid = collect($event['payments'])->where('status', 'paid');
            $amount = round($paid->sum('amount'), 2);
            $first = $paid->first();
            $data = [
                'amount_paid_php' => $amount,
                'paymongo_payment_id' => $first['id'] ?? null,
                'payment_method' => $first['method'] ?? null,
            ];
            if ($amount + 0.005 < $order->total_php) {
                $data['staff_notes'] = trim(($order->staff_notes ?? '')."\nPayMongo reported ₱".number_format($amount, 2).' paid, less than the order total. Check the PayMongo dashboard before releasing items.');
                $order->forceFill($data)->save();
                Log::warning('[shop] PayMongo amount below order total', ['reference' => $order->reference]);

                return null;
            }
            $order->forceFill($data + ['status' => 'paid', 'paid_at' => now()])->save();

            return $order;
        });

        if ($paidNow) {
            $this->alert($paidNow);
        }

        return response('ok', 200);
    }

    /** Email alert to the business inbox (optional). A failure never affects the order. */
    private function alert(Order $order): void
    {
        $to = array_values(array_filter(array_map('trim', explode(',', (string) config('shop.notify')))));
        if (! $to || config('mail.default') === 'log') {
            return;
        }
        try {
            Mail::to($to)->send(new NewOrder($order->load('items')));
            $order->forceFill(['emailed_at' => now()])->save();
        } catch (\Throwable $e) {
            Log::error('[shop] order email alert failed', ['order' => $order->reference, 'error' => $e::class]);
        }
    }
}
