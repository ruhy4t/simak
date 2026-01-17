<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\AuditLog;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('profile.index', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'nama_pengguna' => 'required|string|max:100',
            'no_kontak' => 'nullable|string|max:20',
            'telegram_chat_id' => 'nullable|string|max:20|regex:/^[0-9-]+$/',
        ], [
            'telegram_chat_id.regex' => 'Chat ID hanya boleh berisi angka.',
        ]);

        $user->update([
            'nama_pengguna' => $request->nama_pengguna,
            'no_kontak' => $request->no_kontak,
            'telegram_chat_id' => $request->telegram_chat_id,
        ]);

        AuditLog::log('PROFILE_UPDATED', $user->username);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function changePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required',
            'password' => [
                'required',
                'confirmed',
                'min:8',
                // Allow any characters, must have: lowercase, uppercase, digit, and at least one special char
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/'
            ],
        ], [
            'password.regex' => 'Password harus mengandung huruf besar, huruf kecil, angka, dan minimal 1 karakter khusus.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password lama tidak sesuai.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        AuditLog::log('PASSWORD_CHANGED', $user->username);

        return back()->with('success', 'Password berhasil diubah.');
    }
}
