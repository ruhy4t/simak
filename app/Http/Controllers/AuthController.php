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
            'login_role' => 'required|in:admin,sekolah,operator',
            'username' => 'required|string|max:50',
            'npsn' => 'nullable|required_if:login_role,sekolah,operator|digits:8',
            'password' => 'required|string|min:6',
        ], [
            'login_role.required' => 'Pilih jenis akun yang akan digunakan.',
            'login_role.in' => 'Jenis akun tidak valid.',
            'npsn.required_if' => 'NPSN wajib diisi untuk akun Sekolah dan Operator.',
            'npsn.digits' => 'NPSN harus terdiri dari 8 digit angka.',
        ]);

        $loginRole = (string) $request->login_role;
        $username = strtolower(trim($request->username));
        $npsn = $request->filled('npsn') ? trim((string) $request->npsn) : null;
        $loginIdentity = $npsn
            ? "{$loginRole}:{$username}@{$npsn}"
            : "{$loginRole}:{$username}";

        // Limit each username+IP pair without allowing an attacker to lock the
        // account globally. The IP limiter also slows credential stuffing.
        $credentialKey = 'login_credential:' . hash('sha256', $loginIdentity . '|' . $request->ip());
        $ipKey = 'login_ip:' . hash('sha256', (string) $request->ip());
        if (RateLimiter::tooManyAttempts($credentialKey, 5)
            || RateLimiter::tooManyAttempts($ipKey, 20)) {
            $seconds = max(
                RateLimiter::availableIn($credentialKey),
                RateLimiter::availableIn($ipKey),
            );
            AuditLog::log('LOGIN_RATE_LIMITED', $loginIdentity, "Locked for {$seconds}s");

            return back()->withErrors([
                'username' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
            ])->withInput();
        }

        $userQuery = User::where('username', $username);

        $user = match ($loginRole) {
            'admin' => $userQuery
                ->whereIn('role', ['Dinas', 'SuperAdmin'])
                ->whereNull('npsn')
                ->first(),
            'sekolah' => $userQuery
                ->where('role', 'Sekolah')
                ->where('npsn', $npsn)
                ->first(),
            'operator' => $userQuery
                ->where('role', 'Operator')
                ->where('npsn', $npsn)
                ->first(),
        };

        if (!$user) {
            RateLimiter::hit($credentialKey, 300);
            RateLimiter::hit($ipKey, 300);
            AuditLog::log('LOGIN_FAILED', $loginIdentity, 'User not found');

            $retries = RateLimiter::attempts($credentialKey);
            $remaining = max(0, 5 - $retries);

            return back()->withErrors(['username' => "Username atau password salah. Sisa kesempatan: {$remaining} dari 5."])->withInput();
        }

        // Check status using accessor for consistency
        if (!$user->is_active) {
            AuditLog::log('LOGIN_INACTIVE', $loginIdentity, 'Account inactive');
            return back()->withErrors(['username' => 'Akun tidak aktif.'])->withInput();
        }

        // Reject corrupted or unsupported password hashes without exposing a
        // server error. Plain-text passwords must never be accepted here.
        $passwordInfo = Hash::info((string) $user->password);
        $usesBcrypt = ($passwordInfo['algoName'] ?? null) === 'bcrypt';

        if (!$usesBcrypt || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($credentialKey, 300);
            RateLimiter::hit($ipKey, 300);
            AuditLog::log(
                $usesBcrypt ? 'LOGIN_FAILED' : 'LOGIN_INVALID_PASSWORD_HASH',
                $loginIdentity,
                $usesBcrypt ? 'Wrong password' : 'Unsupported password hash',
            );

            $currentFails = RateLimiter::attempts($credentialKey);
            $remaining = max(0, 5 - $currentFails);

            return back()->withErrors(['username' => "Username atau password salah. Sisa kesempatan: {$remaining} dari 5."])->withInput();
        }

        // Success
        RateLimiter::clear($credentialKey);
        $user->resetFailedLogin();

        Auth::login($user);
        $request->session()->regenerate();

        AuditLog::log('LOGIN_SUCCESS', $loginIdentity);

        $response = redirect()->intended(route('dashboard'));

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
