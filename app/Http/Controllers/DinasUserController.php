<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DinasUserController extends Controller
{
    private function sanitize($input): ?string
    {
        if ($input === null || $input === '')
            return null;
        $input = str_replace("\0", '', $input);
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            abort(403);
        }

        // SuperAdmin bisa lihat semua role termasuk Dinas, Dinas hanya Sekolah/Operator
        $managedRoles = $user->role === 'SuperAdmin'
            ? ['Dinas', 'Sekolah', 'Operator']
            : ['Sekolah', 'Operator'];

        // Query dengan User Model agar Accessor jalan
        $query = \App\Models\User::whereIn('role', $managedRoles);

        // Filter by Role
        if ($request->filled('role') && in_array($request->role, $managedRoles)) {
            $query->where('role', $request->role);
        }

        // Search
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', $search)
                    ->orWhere('nama_pengguna', 'like', $search)
                    ->orWhere('npsn', 'like', $search)
                    ->orWhere('nama_sekolah', 'like', $search);
            });
        }

        // Stats (tanpa filter untuk total) - Use Model logic
        // Note: We need to replicate logic manually or fetch all collection (performance impact)
        // Better to use DB for counts but careful with is_active
        // For stats, we should check 'status' column directly

        $allStats = DB::table('users')
            ->whereIn('role', $managedRoles)
            ->select('role', 'status', DB::raw('count(*) as total'))
            ->groupBy('role', 'status')
            ->get();

        $byRole = $allStats->groupBy('role')->map(fn($items) => $items->sum('total'));
        $totalUsers = $byRole->sum();

        $activeByRole = $allStats->where('status', 'Aktif')->groupBy('role')->map(fn($items) => $items->sum('total'));
        // Inactive includes 'Non Aktif' or anything else
        $inactiveByRole = $allStats->where('status', '!=', 'Aktif')->groupBy('role')->map(fn($items) => $items->sum('total'));

        // Pass managed roles to view for filter dropdown
        $availableRoles = $managedRoles;

        // Pagination
        $users = $query->orderBy('role')->orderBy('nama_pengguna')->paginate(10)->withQueryString();

        return view('dinas.users', compact('users', 'totalUsers', 'byRole', 'activeByRole', 'inactiveByRole', 'availableRoles'));
    }

    /**
     * API: Lookup NPSN untuk auto-fill nama sekolah
     * Hanya menampilkan sekolah lokal (Kota Bogor)
     */
    public function lookupNpsn(string $npsn)
    {
        // Only lookup local schools (Kota Bogor) - exclude external reference schools
        $satpen = DB::table('satuan_pendidikan')
            ->where('npsn', $npsn)
            ->where('is_local', true)
            ->first();

        if ($satpen) {
            return response()->json([
                'found' => true,
                'nama_sekolah' => $satpen->nama,
                'alamat' => $satpen->alamat_lengkap ?? '',
            ]);
        }

        return response()->json([
            'found' => false,
            'message' => 'NPSN tidak ditemukan atau bukan sekolah Kota Bogor'
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            abort(403);
        }

        // SuperAdmin bisa tambah semua role, Dinas hanya Sekolah/Operator
        $allowedRoles = $user->role === 'SuperAdmin' ? ['Sekolah', 'Operator', 'Dinas'] : ['Sekolah', 'Operator'];

        $rules = [
            'username' => 'required|string|max:50|unique:users,username|regex:/^[a-zA-Z0-9_]+$/',
            'nama_pengguna' => 'required|string|max:100',
            'role' => 'required|in:' . implode(',', $allowedRoles),
        ];

        // NPSN dan nama_sekolah wajib untuk Sekolah/Operator
        if (in_array($request->role, ['Sekolah', 'Operator'])) {
            $rules['npsn'] = 'required|string|max:8';
            $rules['nama_sekolah'] = 'required|string|max:150';
        }

        $request->validate($rules, [
            'username.unique' => 'Username sudah digunakan.',
            'username.regex' => 'Username hanya boleh huruf, angka, dan underscore.',
        ]);

        // Additional validation: NPSN must be a local school (Kota Bogor)
        if (in_array($request->role, ['Sekolah', 'Operator'])) {
            $npsn = preg_replace('/\D/', '', $request->npsn);
            $isLocalSchool = DB::table('satuan_pendidikan')
                ->where('npsn', $npsn)
                ->where('is_local', true)
                ->exists();

            if (!$isLocalSchool) {
                return back()->withErrors([
                    'npsn' => 'NPSN harus dari sekolah Kota Bogor. Sekolah referensi dari luar kota tidak dapat dijadikan pengguna.'
                ])->withInput();
            }
        }

        $username = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $request->username));
        $password = Str::random(8) . rand(10, 99) . '@';

        $data = [
            'username' => $username,
            'password' => Hash::make($password),
            'nama_pengguna' => $this->sanitize($request->nama_pengguna),
            'role' => $request->role,
            'status' => 'Aktif',
            'is_active' => true,
            'must_change_password' => true, // Force change on first login
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (in_array($request->role, ['Sekolah', 'Operator'])) {
            $data['npsn'] = preg_replace('/\D/', '', $request->npsn);
            $data['nama_sekolah'] = $this->sanitize($request->nama_sekolah);
        }

        DB::table('users')->insert($data);

        Log::info("User {$username} ({$request->role}) ditambahkan oleh {$user->username}");

        $roleLabel = $request->role;
        return back()->with('success', "✅ Akun {$roleLabel} berhasil dibuat!\n\n👤 Nama: {$request->nama_pengguna}\n📧 Username: {$username}\n🔑 Password: {$password}\n\n⚠️ Catat password ini!");
    }

    public function toggleStatus($id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            abort(403);
        }

        // SuperAdmin bisa toggle Dinas/Sekolah/Operator, Dinas hanya Sekolah/Operator
        $managedRoles = $user->role === 'SuperAdmin'
            ? ['Dinas', 'Sekolah', 'Operator']
            : ['Sekolah', 'Operator'];

        // Use User Model
        $targetUser = \App\Models\User::where('id', intval($id))
            ->whereIn('role', $managedRoles)
            ->first();

        if (!$targetUser) {
            return back()->with('error', 'User tidak ditemukan.');
        }

        // Prevent disabling yourself
        if ($targetUser->id === $user->id) {
            return back()->with('error', 'Tidak bisa menonaktifkan akun sendiri.');
        }

        try {
            // Accessor returns bool (true if status === 'Aktif')
            $currentIsActive = $targetUser->is_active;
            $newIsActive = !$currentIsActive;

            // Direct assignment to BOTH status column and is_active column (if exists)
            $newStatusString = $newIsActive ? 'Aktif' : 'Non Aktif';
            $targetUser->status = $newStatusString;
            $targetUser->is_active = $newIsActive ? 1 : 0;

            // UNBLOCK FEATURE: If Activating, also clear any login locks
            if ($newIsActive) {
                $targetUser->locked_until = null;
                $targetUser->failed_login_attempts = 0;
            }

            $targetUser->updated_at = now();
            $targetUser->save();

            $statusEmoji = $newIsActive ? '✅' : '🚫';
            $statusText = $newIsActive ? 'DIAKTIFKAN' : 'DINONAKTIFKAN';

            // Logs commented out to prevent hosting permission errors
            // Log::info ...

            return back()->with('success', "{$statusEmoji} Akun berhasil {$statusText}!\n\n👤 Nama: {$targetUser->nama_pengguna}\n📧 Username: @{$targetUser->username}\n🏷️ Role: {$targetUser->role}");
        } catch (\Throwable $e) {
            die("STOP! TERJADI ERROR DINAS:<br><br>" . $e->getMessage() . "<br><br>File: " . $e->getFile() . " Baris: " . $e->getLine());
        }
    }

    public function resetPassword($id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            abort(403);
        }

        // SuperAdmin bisa reset Dinas/Sekolah/Operator, Dinas hanya Sekolah/Operator
        $managedRoles = $user->role === 'SuperAdmin'
            ? ['Dinas', 'Sekolah', 'Operator']
            : ['Sekolah', 'Operator'];

        $targetUser = DB::table('users')
            ->where('id', intval($id))
            ->whereIn('role', $managedRoles)
            ->first();

        if (!$targetUser) {
            return back()->with('error', 'User tidak ditemukan.');
        }

        $newPassword = Str::random(8) . rand(10, 99) . '@';

        DB::table('users')->where('id', $id)->update([
            'password' => Hash::make($newPassword),
            'must_change_password' => true,
            'updated_at' => now(),
        ]);

        Log::info("Password reset for {$targetUser->username} by {$user->username}");

        return back()->with('success', "🔑 Password berhasil direset!\n\n👤 Nama: {$targetUser->nama_pengguna}\n📧 Username: @{$targetUser->username}\n🏷️ Role: {$targetUser->role}\n🔐 Password Baru: {$newPassword}");
    }

    /**
     * Show edit form for a user
     */
    public function edit($id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            abort(403);
        }

        // SuperAdmin bisa edit Dinas/Sekolah/Operator, Dinas hanya Sekolah/Operator
        $managedRoles = $user->role === 'SuperAdmin'
            ? ['Dinas', 'Sekolah', 'Operator']
            : ['Sekolah', 'Operator'];

        $targetUser = \App\Models\User::where('id', intval($id))
            ->whereIn('role', $managedRoles)
            ->first();

        if (!$targetUser) {
            return redirect()->route('dinas.users')->with('error', 'User tidak ditemukan.');
        }

        return view('dinas.users-edit', [
            'targetUser' => $targetUser,
            'availableRoles' => $managedRoles,
        ]);
    }

    /**
     * Update user data
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            abort(403);
        }

        // SuperAdmin bisa update Dinas/Sekolah/Operator, Dinas hanya Sekolah/Operator
        $managedRoles = $user->role === 'SuperAdmin'
            ? ['Dinas', 'Sekolah', 'Operator']
            : ['Sekolah', 'Operator'];

        $targetUser = \App\Models\User::where('id', intval($id))
            ->whereIn('role', $managedRoles)
            ->first();

        if (!$targetUser) {
            return redirect()->route('dinas.users')->with('error', 'User tidak ditemukan.');
        }

        $rules = [
            'nama_pengguna' => 'required|string|max:100',
            'role' => 'required|in:' . implode(',', $managedRoles),
        ];

        // NPSN dan nama_sekolah wajib untuk Sekolah/Operator
        if (in_array($request->role, ['Sekolah', 'Operator'])) {
            $rules['npsn'] = 'required|string|max:8';
            $rules['nama_sekolah'] = 'required|string|max:150';
        }

        $request->validate($rules);

        // Additional validation: NPSN must be a local school (Kota Bogor)
        if (in_array($request->role, ['Sekolah', 'Operator'])) {
            $npsn = preg_replace('/\D/', '', $request->npsn);
            $isLocalSchool = DB::table('satuan_pendidikan')
                ->where('npsn', $npsn)
                ->where('is_local', true)
                ->exists();

            if (!$isLocalSchool) {
                return back()->withErrors([
                    'npsn' => 'NPSN harus dari sekolah Kota Bogor. Sekolah referensi dari luar kota tidak dapat dijadikan pengguna.'
                ])->withInput();
            }
        }

        // Update user data
        $targetUser->nama_pengguna = $this->sanitize($request->nama_pengguna);
        $targetUser->role = $request->role;

        if (in_array($request->role, ['Sekolah', 'Operator'])) {
            $targetUser->npsn = preg_replace('/\D/', '', $request->npsn);
            $targetUser->nama_sekolah = $this->sanitize($request->nama_sekolah);
        } else {
            // Clear school data for Dinas role
            $targetUser->npsn = null;
            $targetUser->nama_sekolah = null;
        }

        // Optional: Update Telegram Chat ID if provided
        if ($request->filled('telegram_chat_id')) {
            $targetUser->telegram_chat_id = $this->sanitize($request->telegram_chat_id);
        }

        $targetUser->updated_at = now();
        $targetUser->save();

        Log::info("User {$targetUser->username} updated by {$user->username}");

        return redirect()->route('dinas.users')->with('success', "✅ Data pengguna berhasil diperbarui!\n\n👤 Nama: {$targetUser->nama_pengguna}\n📧 Username: @{$targetUser->username}\n🏷️ Role: {$targetUser->role}");
    }
}