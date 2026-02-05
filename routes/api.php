<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Cron trigger for Vercel
Route::get('/queue/work', [\App\Http\Controllers\QueueWorkerController::class, 'work']);

// Telegram Bot Webhook (no CSRF, no auth - Telegram needs direct access)
Route::prefix('telegram')->group(function () {
    Route::post('/webhook', [\App\Http\Controllers\TelegramController::class, 'webhook']);
    Route::get('/set-webhook', [\App\Http\Controllers\TelegramController::class, 'setWebhook']);
    Route::get('/webhook-info', [\App\Http\Controllers\TelegramController::class, 'webhookInfo']);
});