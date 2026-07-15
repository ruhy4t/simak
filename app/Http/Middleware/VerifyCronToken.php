<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyCronToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = (string) config('services.cron.token', '');
        $requestToken = (string) $request->bearerToken();

        if ($expectedToken === '' || $requestToken === '' || ! hash_equals($expectedToken, $requestToken)) {
            Log::warning('Cron request rejected.', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
