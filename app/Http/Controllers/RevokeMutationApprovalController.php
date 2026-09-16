<?php

namespace App\Http\Controllers;

use App\Services\MutationStatisticsCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RevokeMutationApprovalController extends Controller
{
    public function __invoke(Request $request, string $id, string $type)
    {
        abort_unless(in_array($request->user()->role, ['Dinas', 'SuperAdmin']), 403);
        abort_unless(in_array($type, ['masuk', 'keluar']), 404);

        $query = DB::table('mutasi_'.$type)->where('id_mutasi', $id);
        abort_unless((clone $query)->exists(), 404);

        // Compare and update atomically so a stale page cannot undo a later status.
        $updated = $query->where('status', 'Disetujui Dinas')->update([
            'status' => 'Menunggu Verifikasi Dinas',
            'updated_at' => now(),
        ]);

        if (!$updated) {
            return back()->with('error', 'Persetujuan hanya dapat dibatalkan saat status masih Disetujui Dinas. Silakan muat ulang halaman.');
        }

        MutationStatisticsCache::invalidate();
        Log::info('Persetujuan mutasi dibatalkan', [
            'id_mutasi' => $id,
            'jenis' => $type,
            'oleh' => $request->user()->username,
            'status_sebelumnya' => 'Disetujui Dinas',
            'status' => 'Menunggu Verifikasi Dinas',
        ]);

        return redirect()->route('mutasi.'.$type.'.show', $id)
            ->with('success', 'Persetujuan dibatalkan. Usulan kembali ke Menunggu Verifikasi Dinas dan dapat diverifikasi ulang.');
    }
}
