<?php

namespace Tests\Unit;

use App\Http\Controllers\Controller;
use Tests\TestCase;

class ControllerErrorMessageTest extends TestCase
{
    public function test_debug_json_error_keeps_user_message_actionable_without_raw_exception_text(): void
    {
        config(['app.debug' => true]);

        $controller = new class extends Controller
        {
            public function jsonError(\Throwable $exception)
            {
                return $this->safeJsonError('Gagal memproses data', $exception);
            }
        };

        $response = $controller->jsonError(new \RuntimeException('SQLSTATE simulated database failure'));
        $message = $response->getData(true)['message'];

        $this->assertSame('Gagal memproses data. Silakan coba lagi atau hubungi administrator.', $message);
        $this->assertStringNotContainsString('SQLSTATE', $message);
    }
}
