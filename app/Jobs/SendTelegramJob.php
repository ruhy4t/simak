<?php

namespace App\Jobs;

use App\Services\TelegramDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
     */
    public function handle(TelegramDeliveryService $telegram): void
    {
        try {
            $telegram->deliver((string) $this->chatId, (string) $this->message);
        } catch (Throwable $exception) {
            Log::channel('metrics')->error('telegram.delivery_exception', [
                'attempt' => $this->attempts(),
                'exception_class' => $exception::class,
            ]);

            throw new RuntimeException('Telegram delivery failed.', previous: $exception);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::channel('metrics')->critical('telegram.permanently_failed', [
            'exception_class' => $exception::class,
        ]);
    }
}
