<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
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

        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        try {
            $response = Http::asForm()->timeout(10)->post($url, $postData);

            if (! $response->successful()) {
                Log::error("Job Telegram failed (HTTP {$response->status()}): {$response->body()}");
            }
        } catch (\Exception $e) {
            Log::error('Job Telegram exception: '.$e->getMessage());
        }
    }
}
