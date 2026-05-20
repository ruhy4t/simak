<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\NotificationHelper;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    private function ensureAdminAccess(Request $request): void
    {
        if ($request->user() && in_array($request->user()->role, ['Dinas', 'SuperAdmin'], true)) {
            return;
        }

        $expectedToken = config('services.cron.token');
        $requestToken = $request->query('token') ?: $request->bearerToken();

        if (empty($expectedToken) || !hash_equals($expectedToken, (string) $requestToken)) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Handle Telegram webhook for /start command
     * This endpoint receives updates from Telegram when user sends /start to the bot
     * 
     * POST /api/telegram/webhook
     */
    public function webhook(Request $request)
    {
        $webhookSecret = config('services.telegram.webhook_secret');
        if (!empty($webhookSecret)) {
            $receivedSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token');
            if (!hash_equals($webhookSecret, $receivedSecret)) {
                Log::warning('Telegram webhook rejected: invalid secret token');
                abort(403, 'Unauthorized');
            }
        }

        try {
            $update = $request->all();

            Log::info('Telegram webhook received', [
                'update_id' => $update['update_id'] ?? null,
                'message_id' => $update['message']['message_id'] ?? null,
            ]);

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
        $this->ensureAdminAccess($request);

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

            $payload = [
                'url' => $webhookUrl,
            ];

            $webhookSecret = config('services.telegram.webhook_secret');
            if (!empty($webhookSecret)) {
                $payload['secret_token'] = $webhookSecret;
            }

            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders(['Host' => 'api.telegram.org'])
                ->post($url, $payload);

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
        $this->ensureAdminAccess(request());

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
