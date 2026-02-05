<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTelegramJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $chatId;
    protected $message;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($chatId, $message)
    {
        $this->chatId = $chatId;
        $this->message = $message;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Use the existing logic from NotificationHelper
        // But we must refer to it statically or duplicate logic.
        // Since NotificationHelper::sendTelegram is static, we can call it directly.
        // ideally, to avoid circular logic or infinite loops if helper calls job, 
        // we should call the *actual* sending logic.

        // HOWEVER, NotificationHelper::sendTelegram currently assumes immediate execution. 
        // We will modify NotificationHelper later to dispatch this job. 
        // So this Job should contain the *actual* curl logic OR call a "raw" method in Helper.

        // Let's implement the RAW sending logic here or call a new raw method in Helper 
        // to keep things clean. For now, let's call a new dynamic method 'sendTelegramRaw' 
        // that we will add to NotificationHelper, OR just copy the curl logic here to be safe/independent.

        // Copying curl logic is safer to avoid method signature confusion during refactor.

        $botToken = config('services.telegram.bot_token');
        $chatId = $this->chatId;
        $message = $this->message;

        if (empty($botToken) || empty($chatId)) {
            Log::warning('Job Telegram skipped: missing token/chatId');
            return;
        }

        $postData = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ];

        // Strategy 1: Standard Domain
        $urlDomain = "https://api.telegram.org/bot{$botToken}/sendMessage";

        // Strategy 2: Direct IP
        $urlIp = "https://149.154.167.220/bot{$botToken}/sendMessage";

        if ($this->executeCurl($urlDomain, $postData)) {
            return;
        }

        Log::warning("Job Telegram: Domain failed, using IP fallback...");
        if ($this->executeCurl($urlIp, $postData, ['Host: api.telegram.org'])) {
            return;
        }

        Log::error("Job Telegram: All strategies failed for {$chatId}");

        // We could release back to queue if needed:
        // $this->release(10);
    }

    private function executeCurl(string $url, array $postData, array $headers = []): bool
    {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($postData),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);

            if (!empty($headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                Log::warning("Job Telegram cURL error: {$curlError}");
                return false;
            }

            if ($httpCode === 200) {
                return true;
            } else {
                Log::error("Job Telegram failed (HTTP {$httpCode}): {$response}");
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Job Telegram exception: " . $e->getMessage());
            return false;
        }
    }
}
