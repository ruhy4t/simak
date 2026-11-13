<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolTelegramLinked
{
    private const ALLOWED_ROUTES = [
        'profile.index',
        'profile.update',
        'profile.password',
        'profile.telegram.test',
        'password.change',
        'password.update',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = $request->route()?->getName();

        if (! $user
            || ! in_array($user->role, ['Sekolah', 'Operator'], true)
            || filled($user->telegram_chat_id)
            || in_array($routeName, self::ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        $message = 'Akun sekolah Anda belum menautkan Telegram. Buka Profil, tautkan Telegram, lalu simpan Chat ID untuk melanjutkan menggunakan SIMAK.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'redirect' => route('profile.index'),
            ], 403);
        }

        return redirect()->route('profile.index')->with('error', $message);
    }
}
