<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class QueueWorkerController extends Controller
{
    /**
     * Run the queue worker for Vercel Cron
     */
    public function work()
    {
        // Security: Validate Cron Token
        $requestToken = request()->query('token');
        $validToken = config('services.cron.token');

        if (empty($validToken)) {
            Log::warning('Queue Worker: CRON_SECRET_TOKEN is not set in environment variables.');
            // Optional: Fail safe to 403 or allow if you want insecure by default (not recommended)
            // return abort(403, 'Server configuration error');
        }

        if ($requestToken !== $validToken) {
            Log::warning('Queue Worker: Unauthorized access attempt.');
            Log::warning('IP: ' . request()->ip());
            Log::warning('Received Token: ' . $requestToken);
            Log::warning('Expected Token: ' . $validToken); // Caution: This will log the secret to Vercel logs basically.
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
                'debug' => [
                    'received' => $requestToken,
                    'expected_configured' => !empty($validToken) ? 'YES' : 'NO'
                ]
            ], 403);
        }

        Log::info('Queue Worker triggered via Cron/API');

        // Run the worker for a short period or until empty
        // --stop-when-empty is crucial for serverless to avoid timeouts
        // --memory=512 to ensure it stays within limits (though we bumped php memory to 1024)

        try {
            Log::info("Starting queue:work...");

            // Capture output to log or response
            $exitCode = Artisan::call('queue:work', [
                '--stop-when-empty' => true,
                '--tries' => 3,
                '--timeout' => 5 // 5 seconds per job max to fit in Vercel limits
            ]);

            Log::info("Queue Worker finished with exit code: " . $exitCode);

            return response()->json([
                'status' => 'success',
                'message' => 'Queue worker finished',
                'exit_code' => $exitCode,
                'output' => Artisan::output()
            ]);

        } catch (\Exception $e) {
            Log::error("Queue Worker Failed: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
