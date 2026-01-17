<<<<<<< HEAD
<?php

/**
 * Laravel - SIMAK Entry Point
 * 
 * This file is the entry point for the application.
 * For shared hosting (Byethost), this file should be in public_html.
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
=======
<?php

/**
 * Laravel - SIMAK Entry Point
 * 
 * This file is the entry point for the application.
 * For shared hosting (Byethost), this file should be in public_html.
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
>>>>>>> 9466a07 (Upload project pertama kali)
