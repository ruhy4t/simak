<?php

return [
    'paths' => [
        resource_path('views'),
    ],
    'compiled' => env('VIEW_COMPILED_PATH', '/tmp'), // Use /tmp for Vercel read-only filesystem
];