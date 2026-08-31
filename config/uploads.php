<?php

return [
    // Baseline for PIC-configurable dynamic upload fields. Keep the allowlist
    // intentionally small; expand only for a documented business need.
    'dynamic' => [
        'max_kb' => (int) env('DYNAMIC_UPLOAD_MAX_KB', 10240),
        'accepted_mime_types' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
        ],
    ],
];
