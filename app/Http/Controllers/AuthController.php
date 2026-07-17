<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login', [
            'rememberedUsername' => $request->cookie('remembered_username'),
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:50',
            'password' => 'required|string|min:6',
        ]);

        $username = strtolower(trim($request->username));

        // Limit each username+IP pair without allowing an attacker to lock the
        // account globally. The IP limiter also slows credential stuffing.
        $credentialKey = 'login_credential:' . hash('sha256', $username . '|' . $request->ip());
        $ipKey = 'login_ip:' . hash('sha256', (string) $request->ip());
        if (RateLimiter::tooManyAttempts($credentialKey, 5)
            || RateLimiter::tooManyAttempts($ipKey, 20)) {
            $seconds = max(
                RateLimiter::availableIn($credentialKey),
                RateLimiter::availableIn($ipKey),
            );
            AuditLog::log('LOGIN_RATE_LIMITED', $username, "Locked for {$seconds}s");

            return back()->withErrors([
                'username' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
            ])->withInput();
        }

        // Find user
        $user = User::where('username', $username)->first();

        if (!$user) {
            RateLimiter::hit($credentialKey, 300);
            RateLimiter::hit($ipKey, 300);
            AuditLog::log('LOGIN_FAILED', $username, 'User not found');

            $retries = RateLimiter::attempts($credentialKey);
            $remaining = max(0, 5 - $retries);

            return back()->withErrors(['username' => "Username atau password salah. Sisa kesempatan: {$remaining} dari 5."])->withInput();
        }

        // Check status using accessor for consistency
        if (!$user->is_active) {
            AuditLog::log('LOGIN_INACTIVE', $username, 'Account inactive');
            return back()->withErrors(['username' => 'Akun tidak aktif.'])->withInput();
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            RateLimiter::hit($credentialKey, 300);
            RateLimiter::hit($ipKey, 300);
            AuditLog::log('LOGIN_FAILED', $username, 'Wrong password');

            $currentFails = RateLimiter::attempts($credentialKey);
            $remaining = max(0, 5 - $currentFails);

            return back()->withErrors(['username' => "Username atau password salah. Sisa kesempatan: {$remaining} dari 5."])->withInput();
        }

        // Success
        RateLimiter::clear($credentialKey);
        $user->resetFailedLogin();

        $remember = $request->boolean('remember_credentials');
        if ($remember) {
            Auth::guard()->setRememberDuration(60 * 24 * 30);
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();

        AuditLog::log('LOGIN_SUCCESS', $username);

        $response = redirect()->intended(route('dashboard'));

        if ($remember) {
            return $response->withCookie(cookie(
                'remembered_username',
                $username,
                60 * 24 * 30,
                '/',
                null,
                $request->isSecure(),
                true,
                false,
                'lax'
            ));
        }

        return $response->withoutCookie('remembered_username');
    }

    public function logout(Request $request)
    {
        $username = Auth::user()?->username;

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        AuditLog::log('LOGOUT', $username);

        return redirect()->route('login')->with('success', 'Berhasil logout.');
    }
}
