<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheControlMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $mode = 'default'): Response
    {
        $response = $next($request);

        if ($mode === 'static') {
            // Aggressive caching for static assets/public data (1 week)
            $response->headers->set('Cache-Control', 'public, max-age=604800');
        } elseif ($mode === 'api') {
            // Short caching for API (5 minutes)
            $response->headers->set('Cache-Control', 'public, max-age=300');
        } else {
            // Default no-cache for dynamic content (Safety First)
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        return $response;
    }
}
