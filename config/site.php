<?php

return [
    // "production" only on the live jflsolar.ph site. Anything else = preview:
    // search engines blocked, sample content shown with labels, preview ribbon.
    'env' => env('SITE_ENV', 'preview') === 'production' ? 'production' : 'preview',

    // Live mode only: set SEARCH_INDEXING=false to keep search engines out (for a
    // live-mode site on a temporary address that duplicates the real domain).
    'indexing' => filter_var(env('SEARCH_INDEXING', true), FILTER_VALIDATE_BOOL),

    // Canonical site address, no trailing slash (e.g. https://jflsolar.ph).
    'url' => rtrim((string) env('APP_URL', 'http://localhost:8000'), '/'),

    'inquiry' => [
        // Where new-inquiry alerts are emailed (comma-separate several). Leave
        // empty to keep inquiries in the admin only.
        'notify' => env('INQUIRY_NOTIFY_EMAIL', ''),
        // Accepted inquiries per rolling 24 hours across the whole site.
        'daily_cap' => (int) env('INQUIRY_DAILY_CAP', 80),
    ],
];
