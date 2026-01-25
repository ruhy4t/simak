<?php

/**
 * Laravel - SIMAK Entry Point (Vercel Serverless)
 * 
 * This file replaces the default include to ensure correct execution context
 * in Vercel's serverless environment.
 */

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

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
