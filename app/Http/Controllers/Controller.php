<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function safeErrorMessage(string $userMessage, \Throwable $exception): string
    {
        if (config('app.debug')) {
            return $userMessage . ': ' . $exception->getMessage();
        }

        return $userMessage . '. Silakan coba lagi atau hubungi administrator.';
    }

    protected function safeJsonError(string $userMessage, \Throwable $exception, int $status = 500)
    {
        $payload = [
            'success' => false,
            'message' => $this->safeErrorMessage($userMessage, $exception),
        ];

        if (config('app.debug')) {
            $payload['file'] = $exception->getFile();
            $payload['line'] = $exception->getLine();
        }

        return response()->json($payload, $status);
    }
}
