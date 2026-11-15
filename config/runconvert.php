<?php

return [
    // API key from Dashboard → API Keys on runconvert.com.
    'api_key' => env('RUNCONVERT_API_KEY'),

    'base_url' => env('RUNCONVERT_BASE_URL', 'https://api.runconvert.com/v3'),

    // Seconds per API request (uploads and downloads have no limit).
    'timeout' => (int) env('RUNCONVERT_TIMEOUT', 60),

    'webhook' => [
        // Receive webhooks at this path; set to null to turn the route off.
        'path' => env('RUNCONVERT_WEBHOOK_PATH', 'runconvert/webhook'),

        // The endpoint's secret (shown once when you create it in Dashboard → Webhooks).
        'secret' => env('RUNCONVERT_WEBHOOK_SECRET'),

        // Middleware for the webhook route (no CSRF: RunConvert signs every delivery instead).
        'middleware' => [],
    ],
];
