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
        $nonce = base64_encode(random_bytes(18));
        $request->attributes->set('csp_nonce', $nonce);

        $response = $next($request);

        // 1. HSTS (Strict-Transport-Security)
        // Force HTTPS for 1 year, include subdomains,preload
        if (config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // 2. CSP (Content Security Policy). Inline script blocks receive a
        // request-specific nonce. Legacy HTML event handlers are restricted to
        // their exact contents through CSP hashes until they are moved into the
        // compiled JavaScript bundle.
        $handlerHashes = $this->inlineEventHandlerHashes($response);
        $hashSources = $handlerHashes === []
            ? ''
            : " 'unsafe-hashes' ".implode(' ', $handlerHashes);

        $csp = "default-src 'self'; ".
            "script-src 'self' 'nonce-{$nonce}'{$hashSources} https://vercel.live; ".
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://cdn.jsdelivr.net; ".
            "font-src 'self' https://fonts.bunny.net https://cdn.jsdelivr.net; ".
            "img-src 'self' data: https://images.unsplash.com https://www.transparenttextures.com https://*.backblazeb2.com; ".
            "connect-src 'self' https://emsifa.github.io https://vercel.live https://*.backblazeb2.com https://*.s3.us-east-005.backblazeb2.com; ". // Allow B2 Uploads
            "frame-ancestors 'self'; ". // Prevent clickjacking
            "form-action 'self'; ".
            "base-uri 'self'; ".
            "object-src 'none';"; // Block Flash/Java

        $response->headers->set('Content-Security-Policy', $csp);

        // 3. Permissions Policy (Feature Policy)
        // Disable sensitive features entirely (Removed 'vr' as it caused issues)
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');

        // 4. Standard Security Headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN'); // Fallback for older browsers
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $response->headers->remove('X-Powered-By');

        // 5. Never let browsers or shared proxies retain authenticated HTML.
        if ($request->user() !== null || $request->is('login')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }

    /**
     * Add a nonce to script elements and return hashes for inline event handlers.
     *
     * @return list<string>
     */
    private function inlineEventHandlerHashes(Response $response): array
    {
        $contentType = (string) $response->headers->get('Content-Type', '');

        if (! str_contains(strtolower($contentType), 'text/html') || ! method_exists($response, 'getContent')) {
            return [];
        }

        $content = $response->getContent();

        if (! is_string($content) || $content === '') {
            return [];
        }

        preg_match_all(
            '/\son[a-z]+\s*=\s*(["\'])(.*?)\1/is',
            $content,
            $matches
        );

        $hashes = [];
        foreach ($matches[2] ?? [] as $handler) {
            $decoded = html_entity_decode($handler, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $hashes[] = "'sha256-".base64_encode(hash('sha256', $decoded, true))."'";
        }

        return array_values(array_unique($hashes));
    }
}
