<?php

return [
    'paths' => [
        resource_path('views'),
    ],
    'compiled' => env('VERCEL') ? '/tmp' : (is_writable(storage_path('framework/views')) ? realpath(storage_path('framework/views')) : '/tmp'),
];