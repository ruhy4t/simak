<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCronToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    public function test_cron_endpoints_require_a_valid_bearer_token(): void
    {
        config()->set('services.cron.token', 'test-cron-secret');

        $this->getJson('/cron/work')->assertUnauthorized();
        $this->withToken('wrong-token')->getJson('/cron/work')->assertUnauthorized();
        $this->getJson('/api/queue/work')->assertNotFound();
        $this->getJson('/cron/reminder/test-cron-secret')->assertNotFound();
    }

    public function test_cron_middleware_accepts_the_configured_bearer_token(): void
    {
        config()->set('services.cron.token', 'test-cron-secret');
        $request = Request::create('/cron/reminder');
        $request->headers->set('Authorization', 'Bearer test-cron-secret');

        $response = app(VerifyCronToken::class)->handle(
            $request,
            fn () => response()->json(['ok' => true]),
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_telegram_webhook_rejects_an_invalid_secret(): void
    {
        config()->set('services.telegram.webhook_secret', 'test-webhook-secret');

        $this->postJson('/telegram/webhook', [])->assertForbidden();

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-webhook-secret')
            ->postJson('/telegram/webhook', [])
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_telegram_has_one_official_webhook_route(): void
    {
        $webhookRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => $route->uri() === 'telegram/webhook');

        $this->assertCount(1, $webhookRoutes);
        $this->assertSame(['POST'], $webhookRoutes->first()->methods());
        $this->postJson('/api/telegram/webhook')->assertNotFound();
    }

    public function test_telegram_clients_never_disable_tls_verification(): void
    {
        $files = [
            app_path('Http/Controllers/TelegramController.php'),
            app_path('Jobs/SendTelegramJob.php'),
            app_path('Helpers/NotificationHelper.php'),
        ];

        foreach ($files as $file) {
            $contents = (string) file_get_contents($file);

            $this->assertStringNotContainsString('withoutVerifying', $contents, $file);
            $this->assertStringNotContainsString('CURLOPT_SSL_VERIFYPEER => false', $contents, $file);
            $this->assertStringNotContainsString('CURLOPT_SSL_VERIFYHOST => false', $contents, $file);
            $this->assertStringNotContainsString('149.154.167.220', $contents, $file);
        }
    }

    public function test_csrf_exclusion_is_limited_to_the_telegram_webhook(): void
    {
        $contents = (string) file_get_contents(app_path('Http/Middleware/VerifyCsrfToken.php'));

        $this->assertStringContainsString("'telegram/webhook'", $contents);
        $this->assertStringNotContainsString("'api/*'", $contents);
        $this->assertStringNotContainsString("'cron/*'", $contents);
        $this->assertStringNotContainsString("'telegram/*'", $contents);
    }
}
