<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Performance observability
    |--------------------------------------------------------------------------
    |
    | These thresholds only emit operational warnings. They do not abort a
    | request. Keep SQL bindings out of logs so participant data is not copied
    | into the observability stream.
    |
    */
    'database_warn_ms' => (int) env('PERFORMANCE_DB_WARN_MS', 500),
    'request_warn_ms' => (int) env('PERFORMANCE_REQUEST_WARN_MS', 1500),
    'server_timing' => (bool) env('PERFORMANCE_SERVER_TIMING', false),

    /*
    | Detect accidental lazy loading during development when explicitly
    | enabled. Leave disabled by default for a stable migration path.
    */
    'prevent_lazy_loading' => (bool) env('PERFORMANCE_PREVENT_LAZY_LOADING', false),
];
