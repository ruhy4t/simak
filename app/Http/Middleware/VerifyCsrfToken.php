<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'telegram/*', // Exclude Telegram webhook
        'api/*',      // Exclude all API routes from CSRF (needed if moved to web.php)
        'cron/*',     // Exclude cron routes
    ];
}