<<<<<<< HEAD
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
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
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
            RateLimiter::hit($key, 900); // 15 minutes
            AuditLog::log('LOGIN_FAILED', $username, 'User not found');

            $retries = RateLimiter::attempts($key);
            $remaining = max(0, 5 - $retries);

            return back()->withErrors(['username' => "Username atau password salah. Sisa kesempatan: {$remaining} dari 5."])->withInput();
        }

        // Check if user is locked
        if ($user->isLocked()) {
            AuditLog::log('LOGIN_BLOCKED', $username, 'Account locked');
            return back()->withErrors([
                'username' => 'Akun terkunci. Silakan coba lagi nanti.',
            ])->withInput();
        }

        // Check status using accessor for consistency
        if (!$user->is_active) {
            AuditLog::log('LOGIN_INACTIVE', $username, 'Account inactive');
            return back()->withErrors(['username' => 'Akun tidak aktif.'])->withInput();
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            RateLimiter::hit($key, 900);

            // Increment logic is handled intelligently in User model (auto-reset after 15 mins)
            $user->incrementFailedLogin();
            AuditLog::log('LOGIN_FAILED', $username, 'Wrong password');

            // Get fresh count from DB to ensure accuracy
            $currentFails = $user->fresh()->failed_login_attempts;
            $remaining = max(0, 5 - $currentFails);

            return back()->withErrors(['username' => "Username atau password salah. Sisa kesempatan: {$remaining} dari 5."])->withInput();
        }

        // Success
        RateLimiter::clear($key);
        $user->resetFailedLogin();

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // Store login time for daily tasks middleware
        $request->session()->put('login_time', now('Asia/Jakarta'));

        AuditLog::log('LOGIN_SUCCESS', $username);

        return redirect()->intended(route('dashboard'));
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
=======
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
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
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
            RateLimiter::hit($key, 900); // 15 minutes
            AuditLog::log('LOGIN_FAILED', $username, 'User not found');

            $retries = RateLimiter::attempts($key);
            $remaining = max(0, 5 - $retries);

            return back()->withErrors(['username' => "Username atau password salah. Sisa kesempatan: {$remaining} dari 5."])->withInput();
        }

        // Check if user is locked
        if ($user->isLocked()) {
            AuditLog::log('LOGIN_BLOCKED', $username, 'Account locked');
            return back()->withErrors([
                'username' => 'Akun terkunci. Silakan coba lagi nanti.',
            ])->withInput();
        }

        // Check status using accessor for consistency
        if (!$user->is_active) {
            AuditLog::log('LOGIN_INACTIVE', $username, 'Account inactive');
            return back()->withErrors(['username' => 'Akun tidak aktif.'])->withInput();
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            RateLimiter::hit($key, 900);

            // Increment logic is handled intelligently in User model (auto-reset after 15 mins)
            $user->incrementFailedLogin();
            AuditLog::log('LOGIN_FAILED', $username, 'Wrong password');

            // Get fresh count from DB to ensure accuracy
            $currentFails = $user->fresh()->failed_login_attempts;
            $remaining = max(0, 5 - $currentFails);

            return back()->withErrors(['username' => "Username atau password salah. Sisa kesempatan: {$remaining} dari 5."])->withInput();
        }

        // Success
        RateLimiter::clear($key);
        $user->resetFailedLogin();

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // Store login time for daily tasks middleware
        $request->session()->put('login_time', now('Asia/Jakarta'));

        AuditLog::log('LOGIN_SUCCESS', $username);

        return redirect()->intended(route('dashboard'));
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
>>>>>>> 9466a07 (Upload project pertama kali)
