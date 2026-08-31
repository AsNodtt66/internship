<?php

return [
    'required_php_major_minor' => env('RELEASE_REQUIRED_PHP', '8.4'),
    'required_laravel_major' => (int) env('RELEASE_REQUIRED_LARAVEL_MAJOR', 13),
    'required_filament_major' => (int) env('RELEASE_REQUIRED_FILAMENT_MAJOR', 5),
    'required_vite_major' => (int) env('RELEASE_REQUIRED_VITE_MAJOR', 8),
    'require_https' => env('RELEASE_REQUIRE_HTTPS', true),
    'require_secure_cookie' => env('RELEASE_REQUIRE_SECURE_COOKIE', true),
    'require_queue_async' => env('RELEASE_REQUIRE_ASYNC_QUEUE', true),
    'require_csp' => env('RELEASE_REQUIRE_CSP', false),
    'max_pending_migrations' => (int) env('RELEASE_MAX_PENDING_MIGRATIONS', 0),
    'performance' => [
        'http_error_rate' => (float) env('RELEASE_HTTP_ERROR_RATE', 0.01),
        'checks_rate' => (float) env('RELEASE_CHECKS_RATE', 0.99),
        'p95_ms' => (int) env('RELEASE_P95_MS', 1000),
        'p99_ms' => (int) env('RELEASE_P99_MS', 2000),
    ],
];
