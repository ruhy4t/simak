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

        // Rate limiting
        $key = 'login_attempt_' . $username;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            AuditLog::log('LOGIN_RATE_LIMITED', $username, "Locked for {$seconds}s");

            return back()->withErrors([
                'username' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
            ])->withInput();
        }

        // Find user
        $user = User::where('username', $username)->first();

        if (!$user) {
            RateLimiter::hit($key, 300); // 5 minutes
            AuditLog::log('LOGIN_FAILED', $username, 'User not found');

            $retries = RateLimiter::attempts($key);
            $remaining = max(0, 5 - $retries);

            return back()->withErrors(['username' => "Username atau password salah. Sisa kesempatan: {$remaining} dari 5."])->withInput();
        }

        // Check if user is locked
        if ($user->isLocked()) {
            $remainingSeconds = now()->diffInSeconds($user->locked_until);
            $remainingMinutes = ceil($remainingSeconds / 60);

            AuditLog::log('LOGIN_BLOCKED', $username, "Account still locked. Remaining: {$remainingMinutes} menit");
            return back()->withErrors([
                'username' => "Akun terkunci karena percobaan login gagal. Silakan coba lagi dalam {$remainingMinutes} menit.",
            ])->withInput();
        }

        // Check if account was previously locked but now auto-unlocked
        if ($user->locked_until && $user->locked_until->isPast()) {
            AuditLog::log('ACCOUNT_AUTO_UNLOCKED', $username, 'Akun dibuka otomatis setelah masa penguncian 5 menit berakhir');
            // Reset the lock fields (will be fully reset on successful login)
            $user->update(['locked_until' => null, 'failed_login_attempts' => 0]);
        }

        // Check status using accessor for consistency
        if (!$user->is_active) {
            AuditLog::log('LOGIN_INACTIVE', $username, 'Account inactive');
            return back()->withErrors(['username' => 'Akun tidak aktif.'])->withInput();
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            RateLimiter::hit($key, 300); // 5 minutes
            $currentFails = RateLimiter::attempts($key);

            // Keep the DB counter in sync with the active 5-minute limiter window.
            $user->recordFailedLogin($currentFails);
            AuditLog::log('LOGIN_FAILED', $username, 'Wrong password');

            // Get fresh count from DB to ensure accuracy
            $freshUser = $user->fresh();
            $currentFails = $freshUser->failed_login_attempts;
            $remaining = max(0, 5 - $currentFails);

            // If account just got locked, show lock message
            if ($freshUser->isLocked()) {
                return back()->withErrors(['username' => "Akun dikunci selama 5 menit karena 5x percobaan login gagal."])->withInput();
            }

            return back()->withErrors(['username' => "Username atau password salah. Sisa kesempatan: {$remaining} dari 5."])->withInput();
        }

        // Success
        RateLimiter::clear($key);
        $user->resetFailedLogin();

        // "Ingat saya" only remembers the username for the browser's password
        // manager. It must never create Laravel's persistent login cookie.
        Auth::login($user);
        $request->session()->regenerate();

        // Store login time for daily tasks middleware
        $request->session()->put('login_time', now('Asia/Jakarta'));

        AuditLog::log('LOGIN_SUCCESS', $username);

        $response = redirect()->intended(route('dashboard'));

        if ($request->boolean('remember_credentials')) {
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
