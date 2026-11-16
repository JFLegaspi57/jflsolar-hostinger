<?php

namespace App\Http\Controllers;

use App\Support\InventorySync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** Google Sheet (Apps Script) → this site: stock on hand per item, see InventorySync. */
class InventoryWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! InventorySync::authorized($request->header('Authorization'))) {
            Log::warning('[inventory] sync refused: missing or wrong secret');

            return response()->json(['ok' => false, 'error' => 'unauthorized'], 401);
        }
        $payload = (array) json_decode($request->getContent(), true);
        if (! is_array($payload['items'] ?? null)) {
            return response()->json(['ok' => false, 'error' => 'no items'], 422);
        }
        $report = InventorySync::apply($payload['items'], is_string($payload['sheet'] ?? null) ? $payload['sheet'] : '');

        return response()->json(['ok' => true, 'rows' => $report['rows'], 'linked' => count($report['linked']), 'changed' => count($report['changed']), 'unmatched' => count($report['unmatched'])]);
    }
}
