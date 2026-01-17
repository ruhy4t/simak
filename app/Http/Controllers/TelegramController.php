<<<<<<< HEAD
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\NotificationHelper;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    /**
     * Handle Telegram webhook for /start command
     * This endpoint receives updates from Telegram when user sends /start to the bot
     * 
     * POST /api/telegram/webhook
     */
    public function webhook(Request $request)
    {
        try {
            $update = $request->all();

            // Log incoming webhook for debugging
            Log::info('Telegram webhook received', $update);

            // Process the webhook
            $result = NotificationHelper::processWebhook($update);

            if ($result) {
                Log::info("User started bot - Chat ID: {$result['chat_id']}, Name: {$result['first_name']}");
            }

            // Always return 200 OK to Telegram
            return response()->json(['ok' => true]);

        } catch (\Exception $e) {
            Log::error('Telegram webhook error: ' . $e->getMessage());
            // Still return 200 to prevent Telegram from retrying
            return response()->json(['ok' => true]);
        }
    }

    /**
     * Set webhook URL for Telegram bot
     * Call this once to configure your bot to send updates to your webhook
     * 
     * GET /api/telegram/set-webhook?url=https://yourdomain.com/api/telegram/webhook
     */
    public function setWebhook(Request $request)
    {
        $webhookUrl = $request->input('url');

        if (empty($webhookUrl)) {
            return response()->json([
                'success' => false,
                'message' => 'URL parameter is required'
            ], 400);
        }

        $botToken = config('services.telegram.bot_token');

        if (empty($botToken)) {
            return response()->json([
                'success' => false,
                'message' => 'TELEGRAM_BOT_TOKEN not configured'
            ], 500);
        }

        try {
            // Direct IP workaround for ByetHost DNS blocking
            $url = "https://149.154.167.220/bot{$botToken}/setWebhook";

            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders(['Host' => 'api.telegram.org'])
                ->post($url, [
                    'url' => $webhookUrl,
                ]);

            $result = $response->json();

            if ($result['ok'] ?? false) {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook set successfully',
                    'webhook_url' => $webhookUrl
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['description'] ?? 'Failed to set webhook'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current webhook info
     * GET /api/telegram/webhook-info
     */
    public function webhookInfo()
    {
        $botToken = config('services.telegram.bot_token');

        if (empty($botToken)) {
            return response()->json([
                'success' => false,
                'message' => 'TELEGRAM_BOT_TOKEN not configured'
            ], 500);
        }

        try {
            $url = "https://149.154.167.220/bot{$botToken}/getWebhookInfo";
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders(['Host' => 'api.telegram.org'])
                ->get($url);

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
=======
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\NotificationHelper;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    /**
     * Handle Telegram webhook for /start command
     * This endpoint receives updates from Telegram when user sends /start to the bot
     * 
     * POST /api/telegram/webhook
     */
    public function webhook(Request $request)
    {
        try {
            $update = $request->all();

            // Log incoming webhook for debugging
            Log::info('Telegram webhook received', $update);

            // Process the webhook
            $result = NotificationHelper::processWebhook($update);

            if ($result) {
                Log::info("User started bot - Chat ID: {$result['chat_id']}, Name: {$result['first_name']}");
            }

            // Always return 200 OK to Telegram
            return response()->json(['ok' => true]);

        } catch (\Exception $e) {
            Log::error('Telegram webhook error: ' . $e->getMessage());
            // Still return 200 to prevent Telegram from retrying
            return response()->json(['ok' => true]);
        }
    }

    /**
     * Set webhook URL for Telegram bot
     * Call this once to configure your bot to send updates to your webhook
     * 
     * GET /api/telegram/set-webhook?url=https://yourdomain.com/api/telegram/webhook
     */
    public function setWebhook(Request $request)
    {
        $webhookUrl = $request->input('url');

        if (empty($webhookUrl)) {
            return response()->json([
                'success' => false,
                'message' => 'URL parameter is required'
            ], 400);
        }

        $botToken = config('services.telegram.bot_token');

        if (empty($botToken)) {
            return response()->json([
                'success' => false,
                'message' => 'TELEGRAM_BOT_TOKEN not configured'
            ], 500);
        }

        try {
            // Direct IP workaround for ByetHost DNS blocking
            $url = "https://149.154.167.220/bot{$botToken}/setWebhook";

            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders(['Host' => 'api.telegram.org'])
                ->post($url, [
                    'url' => $webhookUrl,
                ]);

            $result = $response->json();

            if ($result['ok'] ?? false) {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook set successfully',
                    'webhook_url' => $webhookUrl
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['description'] ?? 'Failed to set webhook'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current webhook info
     * GET /api/telegram/webhook-info
     */
    public function webhookInfo()
    {
        $botToken = config('services.telegram.bot_token');

        if (empty($botToken)) {
            return response()->json([
                'success' => false,
                'message' => 'TELEGRAM_BOT_TOKEN not configured'
            ], 500);
        }

        try {
            $url = "https://149.154.167.220/bot{$botToken}/getWebhookInfo";
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders(['Host' => 'api.telegram.org'])
                ->get($url);

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
>>>>>>> 9466a07 (Upload project pertama kali)
