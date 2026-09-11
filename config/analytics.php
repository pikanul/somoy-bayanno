<?php

return [
    'google_analytics_measurement_id' => env('GOOGLE_ANALYTICS_MEASUREMENT_ID'),
    'google_search_console_verification' => env('GOOGLE_SEARCH_CONSOLE_VERIFICATION'),
    'cloudflare_analytics_token' => env('CLOUDFLARE_ANALYTICS_TOKEN'),

    'article_metrics' => [
        'enabled' => env('ARTICLE_METRICS_ENABLED', true),
        'flush_threshold' => max(1, (int) env('ARTICLE_METRICS_FLUSH_THRESHOLD', 25)),
        'most_read_cache_seconds' => max(30, (int) env('ARTICLE_METRICS_MOST_READ_CACHE_SECONDS', 300)),
    ],
];
