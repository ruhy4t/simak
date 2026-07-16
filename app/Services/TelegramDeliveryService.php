<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TelegramDeliveryService
{
    public function deliver(string $chatId, string $message): void
    {
        $botToken = (string) config('services.telegram.bot_token', '');

        if ($botToken === '' || $chatId === '') {
            throw new RuntimeException('Telegram bot token or chat ID is not configured.');
        }

        $response = Http::asForm()
            ->timeout(4)
            ->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

        if (! $response->successful()) {
            Log::channel('metrics')->error('telegram.delivery_failed', [
                'status' => $response->status(),
            ]);

            throw new RuntimeException("Telegram API returned HTTP {$response->status()}.");
        }

        Log::channel('metrics')->info('telegram.delivered');
    }
}
