<?php

return [
    // Online shop (cart + PayMongo checkout). Off until SHOP_ENABLED=true AND the PayMongo
    // keys are set, so deploying this code changes nothing on the live site by itself.
    'enabled' => filter_var(env('SHOP_ENABLED', false), FILTER_VALIDATE_BOOL),

    // While the shop is off, signed-in staff still see the cart and checkout pages (marked
    // "Staff preview"; no order is saved and nothing is charged). Customers see nothing.
    'staff_preview' => filter_var(env('SHOP_STAFF_PREVIEW', true), FILTER_VALIDATE_BOOL),

    // Customers may reserve online and pay in person when they pick up at the office or
    // warehouse. This works without PayMongo; with PayMongo set up, customers choose.
    'pay_in_person' => filter_var(env('SHOP_PAY_IN_PERSON', true), FILTER_VALIDATE_BOOL),

    // Ways to pay at pickup offered at checkout (any of: cash, gcash, bank_transfer, check).
    'in_person_methods' => array_values(array_filter(array_map('trim', explode(',', (string) env('SHOP_IN_PERSON_METHODS', 'cash,gcash,bank_transfer,check'))))),

    // Reservations (pay at pickup) one visitor may place per day — limits no-show spam.
    'reservations_per_day' => (int) env('SHOP_RESERVATIONS_PER_DAY', 3),

    // Largest quantity of one product in one order (pieces, or metres for per-metre items).
    'max_quantity' => (int) env('SHOP_MAX_QUANTITY', 500),

    // Largest total accepted online (₱). Bigger orders are sent to the quotation form,
    // because e-wallets and some cards cap single payments.
    'max_total' => (float) env('SHOP_MAX_TOTAL', 300000),

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
