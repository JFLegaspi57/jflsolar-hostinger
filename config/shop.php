<?php

return [
    // Online shop (cart + PayMongo checkout). Off until SHOP_ENABLED=true AND the PayMongo
    // keys are set, so deploying this code changes nothing on the live site by itself.
    'enabled' => filter_var(env('SHOP_ENABLED', false), FILTER_VALIDATE_BOOL),

    // Largest quantity of one product in one order (pieces, or metres for per-metre items).
    'max_quantity' => (int) env('SHOP_MAX_QUANTITY', 500),

    // Where new-order alerts are emailed (comma-separate several). Empty = same as inquiries.
    'notify' => env('ORDER_NOTIFY_EMAIL', env('INQUIRY_NOTIFY_EMAIL', '')),

    'paymongo' => [
        // Secret key from PayMongo Dashboard → Developers → API keys (sk_test_… or sk_live_…).
        'secret_key' => env('PAYMONGO_SECRET_KEY', ''),
        // Signing secret of the webhook pointing at https://<site>/webhooks/paymongo (whsk_…).
        'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET', ''),
        // Payment methods offered on PayMongo's checkout page; each must be enabled on the account.
        'methods' => array_values(array_filter(array_map('trim', explode(',', (string) env('PAYMONGO_METHODS', 'qrph,gcash,paymaya,card'))))),
        'checkout_endpoint' => env('PAYMONGO_CHECKOUT_ENDPOINT', 'https://api.paymongo.com/v2/checkout_sessions'),
    ],
];
