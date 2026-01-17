<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    /**
     * Sanitize string input
     */
    private function sanitize($input): ?string
    {
        if ($input === null || $input === '')
            return null;
        $input = str_replace("\0", '', $input);
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Display list of operators under this school
     */
    public function index()
    {
        $user = Auth::user();

        // Only Sekolah role can access
        if ($user->role !== 'Sekolah') {
            abort(403, 'Akses hanya untuk role Sekolah.');
        }

        // Use User model so casts are applied
        $operators = \App\Models\User::where('npsn', $user->npsn)
            ->where('role', 'Operator')
            ->orderBy('nama_pengguna')
            ->get();

        // Stats - is_active is properly cast as boolean
        $totalOperators = $operators->count();
        $activeOperators = $operators->where('is_active', true)->count();
        $inactiveOperators = $totalOperators - $activeOperators;

        return view('users.manage', compact('operators', 'totalOperators', 'activeOperators', 'inactiveOperators'));
    }

    /**
     * Store new operator
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'Sekolah') {
            abort(403);
        }

        $request->validate([
            'username' => 'required|string|max:50|unique:users,username|regex:/^[a-zA-Z0-9_]+$/',
            'nama_pengguna' => 'required|string|max:100',
            'no_kontak' => 'nullable|string|max:20',
        ], [
            'username.unique' => 'Username sudah digunakan.',
            'username.regex' => 'Username hanya boleh huruf, angka, dan underscore.',
        ]);

        // Sanitize inputs
        $username = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $request->username));
        $namaPengguna = $this->sanitize($request->nama_pengguna);
        $noKontak = $this->sanitize($request->no_kontak);

        // Generate random password
        $password = Str::random(8) . rand(10, 99) . '@';

        DB::table('users')->insert([
            'username' => $username,
            'password' => Hash::make($password),
            'nama_pengguna' => $namaPengguna,
            'role' => 'Operator',
            'npsn' => $user->npsn,
            'nama_sekolah' => $user->nama_sekolah,
            'no_kontak' => $noKontak,
            'status' => 'Aktif',
            'must_change_password' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info("Operator {$username} created by {$user->username}");

        return back()->with('success', "✅ Akun Operator baru berhasil dibuat!\n\n👤 Nama: {$namaPengguna}\n📧 Username: @{$username}\n🔑 Password: {$password}\n\n⚠️ Catat password ini, tidak bisa dilihat lagi!");
    }

    /**
     * Toggle operator active status
     */
    public function toggleStatus(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->role !== 'Sekolah') {
            abort(403);
        }

        // Sanitize ID
        $id = intval($id);

        // Use User model for proper casting
        $operator = \App\Models\User::where('id', $id)
            ->where('npsn', $user->npsn)
            ->where('role', 'Operator')
            ->first();

        if (!$operator) {
            return back()->with('error', '❌ Operator tidak ditemukan atau bukan bagian dari sekolah Anda.');
        }

        try {
            // Explicitly cast current status to boolean, then toggle
            // Accessor getIsActiveAttribute will return true if status === 'Aktif'
            $currentStatus = $operator->is_active;
            $newStatus = !$currentStatus;

            // Update BOTH columns to handle potential schema drift (Production might have is_active column)
            $newStatusString = $newStatus ? 'Aktif' : 'Non Aktif';
            $operator->status = $newStatusString;
            $operator->is_active = $newStatus ? 1 : 0; // Force update if column exists (via magic set if not fillable)

            $operator->updated_at = now();
            $operator->save();

            $statusEmoji = $newStatus ? '✅' : '🚫';
            $statusText = $newStatus ? 'DIAKTIFKAN' : 'DINONAKTIFKAN';
            $statusColor = $newStatus ? 'aktif dan dapat login' : 'non-aktif dan tidak dapat login';

            // Log disabled temporarily to prevent permission errors on hosting
            // Log::info("Operator {$operator->username} {$statusText} by {$user->username}");

            return back()->with('success', "{$statusEmoji} Akun berhasil {$statusText}!\n\n👤 Nama: {$operator->nama_pengguna}\n📧 Username: @{$operator->username}\n📌 Status: Akun sekarang {$statusColor}.");
        } catch (\Throwable $e) {
            // Emergency Error Display
            die("STOP! TERJADI ERROR PROGRAM:<br><br>" . $e->getMessage() . "<br><br>File: " . $e->getFile() . " Baris: " . $e->getLine());
        }
    }

    /**
     * Reset operator password
     */
    public function resetPassword(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->role !== 'Sekolah') {
            abort(403);
        }

        // Sanitize ID
        $id = intval($id);

        $operator = DB::table('users')
            ->where('id', $id)
            ->where('npsn', $user->npsn)
            ->where('role', 'Operator')
            ->first();

        if (!$operator) {
            return back()->with('error', '❌ Operator tidak ditemukan atau bukan bagian dari sekolah Anda.');
        }

        // Generate new random password
        $newPassword = Str::random(8) . rand(10, 99) . '@';

        DB::table('users')->where('id', $id)->update([
            'password' => Hash::make($newPassword),
            'must_change_password' => true,
            'updated_at' => now(),
        ]);

        Log::info("Password reset for {$operator->username} by {$user->username}");

        return back()->with('success', "🔑 Password berhasil direset!\n\n👤 Nama: {$operator->nama_pengguna}\n📧 Username: @{$operator->username}\n🔐 Password Baru: {$newPassword}\n\n⚠️ Catat password ini dan sampaikan kepada operator. Password tidak bisa dilihat lagi!");
    }
}