<?php

/**
 * Laravel - SIMAK Entry Point (Vercel Serverless)
 * 
 * This file replaces the default include to ensure correct execution context
 * in Vercel's serverless environment.
 */

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Vercel preview deployments use unique hostnames. Keep generated URLs on the
// active request host instead of a hardcoded production deployment domain.
$vercelHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? $_SERVER['VERCEL_URL'] ?? null;
if ($vercelHost) {
    $vercelAppUrl = 'https://' . $vercelHost;
    $_ENV['APP_URL'] = $vercelAppUrl;
    $_SERVER['APP_URL'] = $vercelAppUrl;
    $_ENV['ASSET_URL'] = $vercelAppUrl;
    $_SERVER['ASSET_URL'] = $vercelAppUrl;
    putenv('APP_URL=' . $vercelAppUrl);
    putenv('ASSET_URL=' . $vercelAppUrl);
}

// Determine if maintenance mode
if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel and handle the request
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
