<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PenandatanganController extends Controller
{
    private function sanitize($input): ?string
    {
        if ($input === null || $input === '')
            return null;
        $input = str_replace("\0", '', $input);
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    public function index()
    {
        $user = Auth::user();
        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            abort(403);
        }

        // Only active records (not soft deleted)
        $penandatangan = DB::table('penandatangan')
            ->whereNull('deleted_at')
            ->orderBy('nama')
            ->get();

        return view('penandatangan.index', compact('penandatangan'));
    }

    public function trash()
    {
        $user = Auth::user();
        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            abort(403);
        }

        // Only soft deleted records
        $penandatangan = DB::table('penandatangan')
            ->whereNotNull('deleted_at')
            ->orderBy('deleted_at', 'desc')
            ->get();

        return view('penandatangan.index', compact('penandatangan'))->with('trash', true);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            abort(403);
        }

        $request->validate([
            'nama' => 'required|string|max:100',
            'nip' => 'nullable|string|max:30',
            'jabatan' => 'required|string|max:100',
            'pangkat_golongan' => 'nullable|string|max:100',
        ]);

        DB::table('penandatangan')->insert([
            'nama' => $this->sanitize($request->nama),
            'nip' => $this->sanitize($request->nip),
            'jabatan' => $this->sanitize($request->jabatan),
            'pangkat_golongan' => $this->sanitize($request->pangkat_golongan),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info("Penandatangan {$request->nama} ditambahkan oleh {$user->username}");
        return back()->with('success', "✅ Penandatangan berhasil ditambahkan!");
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            abort(403);
        }

        $request->validate([
            'nama' => 'required|string|max:100',
            'nip' => 'nullable|string|max:30',
            'jabatan' => 'required|string|max:100',
            'pangkat_golongan' => 'nullable|string|max:100',
        ]);

        DB::table('penandatangan')->where('id', intval($id))->update([
            'nama' => $this->sanitize($request->nama),
            'nip' => $this->sanitize($request->nip),
            'jabatan' => $this->sanitize($request->jabatan),
            'pangkat_golongan' => $this->sanitize($request->pangkat_golongan),
            'updated_at' => now(),
        ]);

        return back()->with('success', "✅ Data penandatangan berhasil diperbarui.");
    }

    public function toggleStatus($id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            abort(403);
        }

        $penandatangan = DB::table('penandatangan')->where('id', intval($id))->first();
        if (!$penandatangan) {
            return back()->with('error', 'Data tidak ditemukan.');
        }

        // Only allow toggle if NOT deleted
        if ($penandatangan->deleted_at) {
            return back()->with('error', 'Data yang dihapus tidak dapat diubah statusnya.');
        }

        $newStatus = !$penandatangan->is_active;
        DB::table('penandatangan')->where('id', $id)->update([
            'is_active' => $newStatus,
            'updated_at' => now(),
        ]);

        $statusText = $newStatus ? 'DIAKTIFKAN' : 'DINONAKTIFKAN';
        return back()->with('success', "{$penandatangan->nama} berhasil {$statusText}.");
    }

    public function destroy(Request $request, $id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            abort(403);
        }

        $request->validate([
            'alasan_penghapusan' => 'required|string|max:255',
        ]);

        $penandatangan = DB::table('penandatangan')->where('id', intval($id))->first();
        if (!$penandatangan) {
            return back()->with('error', 'Data tidak ditemukan.');
        }

        DB::table('penandatangan')->where('id', $id)->update([
            'deleted_at' => now(),
            'alasan_penghapusan' => $this->sanitize($request->alasan_penghapusan),
            'is_active' => false,
            'updated_at' => now(),
        ]);

        return redirect()->route('penandatangan.index')->with('success', "Data berhasil dihapus (soft delete).");
    }

    public function restore($id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            abort(403);
        }

        $penandatangan = DB::table('penandatangan')->where('id', intval($id))->first();
        if (!$penandatangan) {
            return back()->with('error', 'Data tidak ditemukan.');
        }

        DB::table('penandatangan')->where('id', $id)->update([
            'deleted_at' => null,
            'alasan_penghapusan' => null,
            'is_active' => true, // Auto active upon restore
            'updated_at' => now(),
        ]);

        return redirect()->route('penandatangan.trash')->with('success', "Data berhasil dikembalikan (restore).");
    }
}