<?php

return [
    'headers' => [
        'enabled' => env('SECURITY_HEADERS_ENABLED', true),
        'frame_options' => env('SECURITY_FRAME_OPTIONS', 'SAMEORIGIN'),
        'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'permissions_policy' => env('SECURITY_PERMISSIONS_POLICY', 'camera=(), microphone=(), geolocation=()'),
        'hsts' => [
            'enabled' => env('SECURITY_HSTS_ENABLED', false),
            'max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
            'include_subdomains' => env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', true),
            'preload' => env('SECURITY_HSTS_PRELOAD', false),
        ],
        // CSP is report-only by default because Filament/Livewire may require
        // policy tuning for inline/runtime assets. Promote to enforcement only
        // after observing reports in the target browser/deployment.
        'csp_report_only' => env('SECURITY_CSP_REPORT_ONLY'),
        'csp' => env('SECURITY_CSP'),
    ],

    'rate_limits' => [
        'documents_per_minute' => (int) env('SECURITY_DOCUMENT_DOWNLOADS_PER_MINUTE', 60),
        'reports_per_minute' => (int) env('SECURITY_REPORTS_PER_MINUTE', 30),
        'health_per_minute' => (int) env('SECURITY_HEALTH_PER_MINUTE', 120),
    ],
];
