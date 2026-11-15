<?php

namespace App\Http\Controllers;

use App\Support\InboundMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** Hostinger Agentic Mail → this site: each email to info@jflsolar.ph becomes an inquiry (see InboundMail). */
class HostingerMailWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! InboundMail::authorized($request->header('Authorization'))) {
            Log::warning('[mail] Hostinger mail webhook refused: missing or wrong secret');

            return response()->json(['ok' => false, 'error' => 'unauthorized'], 401);
        }
        $payload = (array) json_decode($request->getContent(), true);
        // Keys only (no content), to check the payload shape in the log if something is not picked up.
        Log::info('[mail] Hostinger mail webhook received', ['event' => $payload['event'] ?? $payload['type'] ?? null, 'keys' => array_keys($payload), 'message_keys' => is_array($payload['message'] ?? null) ? array_keys($payload['message']) : null]);
        $inquiry = InboundMail::store($payload);

        return response()->json(['ok' => true, 'inquiry' => $inquiry?->id]);
    }
}
