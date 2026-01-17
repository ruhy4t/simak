<<<<<<< HEAD
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    // API routes here if needed
});

// Telegram Bot Webhook (no CSRF, no auth - Telegram needs direct access)
Route::prefix('telegram')->group(function () {
    Route::post('/webhook', [\App\Http\Controllers\TelegramController::class, 'webhook']);
    Route::get('/set-webhook', [\App\Http\Controllers\TelegramController::class, 'setWebhook']);
    Route::get('/webhook-info', [\App\Http\Controllers\TelegramController::class, 'webhookInfo']);
});
=======
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    // API routes here if needed
});

// Telegram Bot Webhook (no CSRF, no auth - Telegram needs direct access)
Route::prefix('telegram')->group(function () {
    Route::post('/webhook', [\App\Http\Controllers\TelegramController::class, 'webhook']);
    Route::get('/set-webhook', [\App\Http\Controllers\TelegramController::class, 'setWebhook']);
    Route::get('/webhook-info', [\App\Http\Controllers\TelegramController::class, 'webhookInfo']);
});
>>>>>>> 9466a07 (Upload project pertama kali)
