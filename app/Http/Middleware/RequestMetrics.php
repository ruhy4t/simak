<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestMetrics
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);
        $queryCount = 0;
        $queryDurationMs = 0.0;

        DB::listen(function (QueryExecuted $query) use (&$queryCount, &$queryDurationMs): void {
            $queryCount++;
            $queryDurationMs += $query->time;
        });

        $response = $next($request);
        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
        $slow = $durationMs >= (int) config('observability.slow_request_ms');
        $sampleRate = max(0.0, min(1.0, (float) config('observability.request_sample_rate')));
        $sampled = $sampleRate >= 1.0 || (random_int(1, 10_000) / 10_000) <= $sampleRate;

        if ($slow || $response->getStatusCode() >= 500 || $sampled) {
            Log::channel('metrics')->log($slow ? 'warning' : 'info', 'http.request', [
                'method' => $request->method(),
                'route' => $request->route()?->getName() ?? $request->path(),
                'status' => $response->getStatusCode(),
                'duration_ms' => round($durationMs, 2),
                'query_count' => $queryCount,
                'query_duration_ms' => round($queryDurationMs, 2),
                'user_id' => $request->user()?->getAuthIdentifier(),
            ]);
        }

        $response->headers->set('Server-Timing', sprintf('app;dur=%.2f, db;dur=%.2f', $durationMs, $queryDurationMs));

        return $response;
    }
}
