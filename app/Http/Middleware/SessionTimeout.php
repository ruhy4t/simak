<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeout
{
    /**
     * Session idle timeout in minutes.
     * After this period of inactivity, user will be logged out.
     */
    protected int $timeout = 60;

    /**
     * Handle an incoming request.
     * Tracks user activity and logs out if idle for too long.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $lastActivity = $request->session()->get('last_activity');
        $currentTime = time();

        // Check if session has timed out due to inactivity
        if ($lastActivity && ($currentTime - $lastActivity) > ($this->timeout * 60)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Sesi Anda telah berakhir karena tidak ada aktivitas selama 60 menit. Silakan login kembali.');
        }

        // Update last activity timestamp
        $request->session()->put('last_activity', $currentTime);

        return $next($request);
    }
}
