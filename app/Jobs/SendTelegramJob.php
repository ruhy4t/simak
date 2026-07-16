<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendTelegramJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 5;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

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
            $response = Http::asForm()->timeout(4)->post($url, $postData);
            if (! $response->successful()) {
                Log::channel('metrics')->error('telegram.delivery_failed', [
                    'status' => $response->status(),
                    'attempt' => $this->attempts(),
                ]);

                throw new RuntimeException("Telegram API returned HTTP {$response->status()}.");
            }

            Log::channel('metrics')->info('telegram.delivered', ['attempt' => $this->attempts()]);
        } catch (Throwable $exception) {
            Log::channel('metrics')->error('telegram.delivery_exception', [
                'attempt' => $this->attempts(),
                'exception_class' => $exception::class,
            ]);

            throw new RuntimeException('Telegram delivery failed.');
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::channel('metrics')->critical('telegram.permanently_failed', [
            'exception_class' => $exception::class,
        ]);
    }
}
