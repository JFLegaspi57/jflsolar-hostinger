<?php

return [
    // Website chat assistant (answers from the live site content with Claude).
    // Shown only when switched on AND an Anthropic API key is set.
    'enabled' => env('CHATBOT_ENABLED', true),
    'api_key' => env('ANTHROPIC_API_KEY', ''),
    'model' => env('CHATBOT_MODEL', 'claude-haiku-5-5'),
    'max_tokens' => (int) env('CHATBOT_MAX_TOKENS', 700),
    // Cost guards: messages per visitor per hour, and per day for the whole site.
    'per_visitor_hourly' => (int) env('CHATBOT_PER_VISITOR_HOURLY', 40),
    'daily_cap' => (int) env('CHATBOT_DAILY_CAP', 1500),
];
