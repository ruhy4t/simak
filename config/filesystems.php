<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        'private_docs' => [
            'driver' => 'local',
            'root' => storage_path('app/dokumen_rahasia'), // Store outside public
            'visibility' => 'private',
            'throw' => false,
        ],

        // Backblaze B2 for Vercel serverless (cloud storage)
        'b2' => [
            'driver' => 's3',
            'key' => env('B2_ACCESS_KEY_ID'),
            'secret' => env('B2_SECRET_ACCESS_KEY'),
            'region' => env('B2_REGION', 'us-west-004'),
            'bucket' => env('B2_BUCKET'),
            'endpoint' => (function () {
                $endpoint = env('B2_ENDPOINT');
                if ($endpoint && !str_starts_with($endpoint, 'http')) {
                    return 'https://' . $endpoint;
                }
                return $endpoint;
            })(),
            'use_path_style_endpoint' => true,
            'throw' => true,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];