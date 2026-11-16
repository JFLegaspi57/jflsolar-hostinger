<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Support\OrderPayments;
use App\Support\PayMongo;
use App\Support\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * PayMongo → this site, signed with the webhook secret. The payment is recorded only when
 * the signature is valid and the session matches the order (OrderPayments checks the
 * amount). Safe to receive twice — PayMongo retries.
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
        if ($event['type'] === 'checkout_session.payment.paid') {
            if (str_starts_with($event['reference'], \App\Support\Course::REF_PREFIX)) {
                \App\Support\Course::recordPayment($event['reference'], $event['session_id'], $event['payments']);   // paid membership (online course)
            } elseif (Shop::ordersVisible()) {
                OrderPayments::record($event['reference'], $event['session_id'], $event['payments']);
            }
        }

        return response('ok', 200);
    }
}
