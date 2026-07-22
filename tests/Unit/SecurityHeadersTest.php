<?php

namespace Tests\Unit;

use App\Http\Middleware\SecurityHeaders;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_it_secures_inline_javascript_without_unsafe_script_sources(): void
    {
        $request = Request::create('/example');
        $middleware = new SecurityHeaders;
        $handler = "return confirm('Lanjutkan?')";

        $response = $middleware->handle($request, function () use ($request, $handler): Response {
            $nonce = $request->attributes->get('csp_nonce');

            return new Response(
                '<html><script nonce="'.$nonce.'">window.example = true;</script><button onclick="'.$handler.'">OK</button></html>',
                200,
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        });

        $policy = (string) $response->headers->get('Content-Security-Policy');
        $nonce = (string) $request->attributes->get('csp_nonce');
        $expectedHash = "'sha256-".base64_encode(hash('sha256', $handler, true))."'";

        $this->assertNotSame('', $nonce);
        $this->assertStringContainsString('<script nonce="'.$nonce.'">', (string) $response->getContent());
        $this->assertStringContainsString("script-src 'self' 'nonce-{$nonce}'", $policy);
        $this->assertStringContainsString("'unsafe-hashes' {$expectedHash}", $policy);

        $scriptPolicy = explode(';', $policy)[1];
        $this->assertStringNotContainsString("'unsafe-inline'", $scriptPolicy);
        $this->assertStringNotContainsString("'unsafe-eval'", $scriptPolicy);
        $this->assertStringNotContainsString('cdn.jsdelivr.net', $scriptPolicy);
        $this->assertSame('same-origin', $response->headers->get('Cross-Origin-Opener-Policy'));
        $this->assertSame('same-origin', $response->headers->get('Cross-Origin-Resource-Policy'));
        $this->assertSame('0', $response->headers->get('X-XSS-Protection'));
    }

    public function test_it_does_not_rewrite_non_html_responses(): void
    {
        $request = Request::create('/api/example');
        $middleware = new SecurityHeaders;

        $response = $middleware->handle($request, fn () => new Response(
            '{"script":"<script>alert(1)</script>"}',
            200,
            ['Content-Type' => 'application/json']
        ));

        $this->assertStringNotContainsString('nonce=', (string) $response->getContent());
    }

    public function test_authenticated_responses_are_never_cached(): void
    {
        $request = Request::create('/laporan');
        $request->setUserResolver(fn () => new User(['role' => 'Dinas']));

        $response = (new SecurityHeaders)->handle(
            $request,
            fn () => new Response('<html>private</html>', 200, ['Content-Type' => 'text/html']),
        );

        $cacheControl = (string) $response->headers->get('Cache-Control');
        foreach (['private', 'no-store', 'no-cache', 'must-revalidate', 'max-age=0'] as $directive) {
            $this->assertStringContainsString($directive, $cacheControl);
        }
        $this->assertSame('no-cache', $response->headers->get('Pragma'));
    }

    public function test_blade_scripts_receive_the_matching_request_nonce(): void
    {
        $response = $this->get('/login')->assertOk();
        $policy = (string) $response->headers->get('Content-Security-Policy');

        preg_match("/'nonce-([^']+)'/", $policy, $matches);

        $this->assertNotEmpty($matches[1] ?? null);
        $response->assertSee('script nonce="'.$matches[1].'"', false);
    }
}
