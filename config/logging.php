<?php

return [
    'default' => env('LOG_CHANNEL', 'stderr'),
    'deprecations' => ['channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'), 'trace' => false],

    'channels' => [
        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', env('APP_ENV') === 'production' ? 'warning' : 'debug'),
            'handler' => Monolog\Handler\StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => ['stream' => 'php://stderr'],
            'processors' => [Monolog\Processor\PsrLogMessageProcessor::class],
        ],

        'metrics' => [
            'driver' => 'monolog',
            'level' => 'info',
            'handler' => Monolog\Handler\StreamHandler::class,
            'with' => ['stream' => 'php://stderr'],
            'processors' => [Monolog\Processor\PsrLogMessageProcessor::class],
        ],

        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily'],  // Changed from 'single' to 'daily' for log rotation
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', env('APP_ENV') === 'production' ? 'warning' : 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', env('APP_ENV') === 'production' ? 'warning' : 'debug'),
            'days' => 14,
            'replace_placeholders' => true,
        ],
    ],
];
