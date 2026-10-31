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

    // Google Search Console "HTML tag" verification code (only the content="..." value).
    'google_verification' => trim((string) env('GOOGLE_SITE_VERIFICATION', '')),

    // Visitor measurement, live site only, after the visitor allows it in the cookie notice.
    // Google Analytics 4 measurement ID (G-XXXXXXX) and Meta (Facebook) Pixel ID (digits).
    // Leave empty to keep them off.
    'ga4_id' => preg_match('/^G-[A-Z0-9]{4,20}$/', $ga = strtoupper(trim((string) env('GA4_MEASUREMENT_ID', '')))) ? $ga : '',
    'meta_pixel_id' => preg_match('/^[0-9]{6,20}$/', $px = trim((string) env('META_PIXEL_ID', ''))) ? $px : '',

    'inquiry' => [
        // Where new-inquiry alerts are emailed (comma-separate several). Leave
        // empty to keep inquiries in the admin only.
        'notify' => env('INQUIRY_NOTIFY_EMAIL', ''),
        // Accepted inquiries per rolling 24 hours across the whole site.
        'daily_cap' => (int) env('INQUIRY_DAILY_CAP', 80),
    ],
];
