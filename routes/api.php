<?php

use Illuminate\Support\Facades\Route;

// Cron trigger for Vercel
Route::get('/queue/work', [\App\Http\Controllers\QueueWorkerController::class, 'work'])
    ->middleware('cron.auth');
