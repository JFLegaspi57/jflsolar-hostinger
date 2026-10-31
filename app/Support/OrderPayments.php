<?php

namespace App\Support;

use App\Mail\NewOrder;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Records a PayMongo payment against an order. Used by the signed webhook and by the
 * order page's own check with PayMongo (in case a webhook delivery was missed). Safe to
 * run any number of times for the same payment.
 */
final class OrderPayments
{
    /**
     * @param  list<array{id: string, status: string, amount: float, method: string}>  $payments
     */
    public static function record(string $reference, string $sessionId, array $payments): void
    {
        Shop::ensureTables();
        $alert = null;
        DB::transaction(function () use ($reference, $sessionId, $payments, &$alert) {
            $order = Order::query()->where('reference', $reference)->lockForUpdate()->first();
            if (! $order || $sessionId === '' || $order->paymongo_checkout_id !== $sessionId) {
                Log::warning('[shop] PayMongo payment for an unknown order', ['reference' => $reference, 'session' => $sessionId]);

                return;
            }
            if ($order->paid_at !== null) {
                return;   // already recorded (retry, or the order page checked first)
            }
            $paid = collect($payments)->where('status', 'paid');
            if ($paid->isEmpty()) {
                return;
            }
            $amount = round($paid->sum('amount'), 2);
            $first = $paid->first();
            $data = [
                'amount_paid_php' => $amount,
                'paymongo_payment_id' => $first['id'] ?: null,
                'payment_method' => $first['method'] ?: null,
                'lock_version' => $order->lock_version + 1,
            ];
            $note = null;
            if ($amount + 0.005 < $order->total_php) {
                $note = 'PayMongo reported ₱'.number_format($amount, 2).' paid, less than the order total. Check the PayMongo dashboard before releasing items.';
            } elseif ($order->status === 'cancelled') {
                // Paid on a checkout page that was still open after staff cancelled the order.
                $data['paid_at'] = now();
                $note = 'Paid ₱'.number_format($amount, 2).' AFTER this order was cancelled. Refund it in the PayMongo dashboard, or contact the customer to reinstate it.';
                $alert = $order;
            } else {
                $data['paid_at'] = now();
                $data['status'] = 'paid';
                $alert = $order;
            }
            if ($note !== null && ! str_contains((string) $order->staff_notes, $note)) {
                $data['staff_notes'] = trim(($order->staff_notes ?? '')."\n".$note);
            }
            $order->forceFill($data)->save();
            if ($note !== null) {
                Log::warning('[shop] '.$note, ['reference' => $order->reference]);
            }
        });

        if ($alert) {
            self::alert($alert->refresh()->load('items'));
        }
    }

    /** Email alert to the business inbox (optional). A failure never affects the order. */
    private static function alert(Order $order): void
    {
        $to = array_values(array_filter(array_map('trim', explode(',', (string) config('shop.notify')))));
        if (! $to || config('mail.default') === 'log') {
            return;
        }
        try {
            Mail::to($to)->send(new NewOrder($order));
            $order->forceFill(['emailed_at' => now()])->save();
        } catch (\Throwable $e) {
            Log::error('[shop] order email alert failed', ['order' => $order->reference, 'error' => $e::class]);
        }
    }
}
