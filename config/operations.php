<?php

return [
    'queue_monitor' => [
        'target' => env('QUEUE_MONITOR_TARGET', 'database:default'),
        'max' => (int) env('QUEUE_MONITOR_MAX', 100),
    ],

    'failed_jobs_retention_hours' => (int) env('QUEUE_FAILED_RETENTION_HOURS', 168),
];
