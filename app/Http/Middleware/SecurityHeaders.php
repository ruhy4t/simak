<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     * Add security headers to responses via Laravel middleware.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 1. HSTS (Strict-Transport-Security)
        // Force HTTPS for 1 year, include subdomains,preload
        if (config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // 2. CSP (Content Security Policy) - "Super High Security"
        // Whitelist trusted sources only. No unsafe-inline for scripts (except what we extracted).
        $csp = "default-src 'self'; " .
            "script-src 'self' https://cdn.jsdelivr.net 'unsafe-eval' 'unsafe-inline'; " . // unsafe-inline REQUIRED for Alpine.js attributes
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://cdn.jsdelivr.net; " . // unsafe-inline needed for dynamic styles
            "font-src 'self' https://fonts.bunny.net https://cdn.jsdelivr.net; " .
            "img-src 'self' data: https://api.qrserver.com https://images.unsplash.com https://www.transparenttextures.com; " . // Allow QR, Unsplash, Textures
            "connect-src 'self' https://emsifa.github.io; " . // Allow API calls to regions
            "frame-ancestors 'self'; " . // Prevent clickjacking
            "form-action 'self'; " .
            "base-uri 'self'; " .
            "object-src 'none';"; // Block Flash/Java

        $response->headers->set('Content-Security-Policy', $csp);

        // 3. Permissions Policy (Feature Policy)
        // Disable sensitive features entirely (Removed 'vr' as it caused issues)
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');

        // 4. Standard Security Headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN'); // Fallback for older browsers
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->remove('X-Powered-By');

        // 5. Cache Control for sensitive pages
        if ($request->is('login', 'dashboard', 'mutasi/*', 'dinas/*', 'profile')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}