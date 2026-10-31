<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

/**
 * PayMongo hosted checkout (GCash, Maya, cards, QR Ph). The customer pays on PayMongo's
 * page; we never see card or wallet details. The signed webhook is the only thing that
 * marks an order paid — the "success" redirect alone proves nothing.
 */
final class PayMongo
{
    /** Allowed age of a webhook timestamp, so a captured request cannot be replayed later. */
    public const WEBHOOK_TOLERANCE_SECONDS = 600;

    /**
     * Opens a checkout session for the order.
     *
     * @return array{id: string, url: string}
     */
    public static function createCheckout(Order $order): array
    {
        $order->loadMissing('items');
        $lineItems = $order->items->map(fn ($i) => [
            'name' => mb_substr($i->name.($i->unit === 'm' ? ' (per metre)' : ''), 0, 255),
            'amount' => (int) round($i->unit_price_php * 100),   // centavos
            'currency' => 'PHP',
            'quantity' => $i->quantity,
        ])->all();

        $response = Http::withBasicAuth((string) config('shop.paymongo.secret_key'), '')
            ->acceptJson()
            ->timeout(20)
            ->post((string) config('shop.paymongo.checkout_endpoint'), [
                'data' => ['attributes' => [
                    'line_items' => $lineItems,
                    'payment_method_types' => config('shop.paymongo.methods'),
                    'success_url' => Site::url('/checkout/done?order='.$order->reference.'&t='.$order->access_token),
                    'cancel_url' => Site::url('/cart?cancelled='.$order->reference),
                    'reference_number' => $order->reference,
                    'description' => 'JFL Solar order '.$order->reference,
                    'send_email_receipt' => true,
                    'show_line_items' => true,
                    'billing' => ['name' => $order->customer_name, 'email' => $order->customer_email, 'phone' => $order->customer_phone],
                ]],
            ]);

        $id = (string) $response->json('data.id', '');
        $url = (string) $response->json('data.attributes.checkout_url', '');
        if (! $response->successful() || $id === '' || ! str_starts_with($url, 'https://')) {
            throw new \RuntimeException('PayMongo checkout failed: HTTP '.$response->status().' '.mb_substr((string) $response->json('errors.0.detail', ''), 0, 200));
        }

        return ['id' => $id, 'url' => $url];
    }

    /**
     * Checks the Paymongo-Signature header ("t=…,te=…,li=…"): HMAC-SHA256 of "t.rawBody"
     * with the webhook's signing secret, compared with te (test mode) or li (live mode).
     */
    public static function signatureValid(string $rawBody, string $header, ?int $now = null): bool
    {
        $secret = (string) config('shop.paymongo.webhook_secret');
        if ($secret === '' || $header === '') {
            return false;
        }
        $parts = [];
        foreach (explode(',', $header) as $piece) {
            [$k, $v] = array_pad(explode('=', trim($piece), 2), 2, '');
            $parts[$k] = $v;
        }
        $t = $parts['t'] ?? '';
        if (! ctype_digit($t) || abs(($now ?? time()) - (int) $t) > self::WEBHOOK_TOLERANCE_SECONDS) {
            return false;
        }
        $live = str_starts_with((string) config('shop.paymongo.secret_key'), 'sk_live_');
        $given = $parts[$live ? 'li' : 'te'] ?? '';
        $expected = hash_hmac('sha256', $t.'.'.$rawBody, $secret);

        return $given !== '' && hash_equals($expected, $given);
    }

    /**
     * Reads a webhook event. Accepts both payload shapes PayMongo documents
     * (data.attributes.type / data.attributes.data, and data.type / data.data).
     *
     * @return array{type: string, livemode: bool, session_id: string, reference: string, payments: list<array{id: string, status: string, amount: float, method: string}>}
     */
    public static function parseEvent(array $payload): array
    {
        $event = $payload['data'] ?? [];
        $attr = $event['attributes'] ?? $event;
        $session = $attr['data'] ?? [];
        $sAttr = $session['attributes'] ?? [];
        $payments = [];
        foreach (($sAttr['payments'] ?? []) as $p) {
            $pa = $p['attributes'] ?? [];
            $payments[] = [
                'id' => (string) ($p['id'] ?? ''),
                'status' => (string) ($pa['status'] ?? ''),
                'amount' => ((int) ($pa['amount'] ?? 0)) / 100,
                'method' => (string) ($pa['source']['type'] ?? ''),
            ];
        }

        return [
            'type' => (string) ($attr['type'] ?? ''),
            'livemode' => (bool) ($attr['livemode'] ?? false),
            'session_id' => (string) ($session['id'] ?? ''),
            'reference' => (string) ($sAttr['reference_number'] ?? ''),
            'payments' => $payments,
        ];
    }
}
