<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user) {
            // Check for default password pattern for 'Sekolah' role
            // Pattern: NPSN + 'Fa*' (e.g., 20220389Fa*)
            if ($user->role === 'Sekolah' && !$user->must_change_password && $user->npsn) {
                $defaultPassword = $user->npsn . 'Fa*';
                if (Hash::check($defaultPassword, $user->password)) {
                    // Mark user as must change password
                    $user->must_change_password = true;
                    $user->save();

                    return redirect()->route('password.change')
                        ->with('warning', '⚠️ Keamanan: Anda terdeteksi menggunakan password default. Demi keamanan data sekolah, Anda wajib mengganti password sekarang!');
                }
            }

            if ($user->must_change_password) {
                // Allow access to logout and the change password route itself
                if (!$request->is('password/change') && !$request->is('password/update') && !$request->is('logout')) {
                    return redirect()->route('password.change')
                        ->with('warning', '⚠️ Keamanan: Anda menggunakan password default/reset. Wajib ganti password sebelum melanjutkan!');
                }
            }
        }

        return $next($request);
    }
}
