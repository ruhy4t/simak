<?php

return [
    'temporary_ttl_hours' => (int) env('TEMP_UPLOAD_TTL_HOURS', 168),
    'temporary_max_bytes' => 2 * 1024 * 1024,
    'allowed_mime_by_extension' => [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
    ],
];
