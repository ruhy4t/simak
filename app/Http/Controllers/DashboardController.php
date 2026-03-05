<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get filter parameters
        $filterMonth = $request->input('month');
        $filterYear = $request->input('year');

        // Get stats for both Mutasi Keluar and Mutasi Masuk
        $statsKeluar = $this->getStatsMutasiKeluar($user);
        $statsMasuk = $this->getStatsMutasiMasuk($user);

        // Get user stats (for Sekolah and Dinas roles)
        $statsUsers = null;
        if ($user->role === 'Sekolah') {
            $statsUsers = $this->getStatsUsers($user);
        } elseif (in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            $statsUsers = $this->getStatsDinasUsers();
        }

        // Get school mutation statistics with filters
        $schoolStatsKeluar = $this->getSchoolStatsKeluar($user, $filterMonth, $filterYear);
        $schoolStatsMasuk = $this->getSchoolStatsMasuk($user, $filterMonth, $filterYear);

        // Get available years for filter
        $availableYears = $this->getAvailableYears();

        return view('dashboard.index', compact(
            'statsKeluar',
            'statsMasuk',
            'statsUsers',
            'schoolStatsKeluar',
            'schoolStatsMasuk',
            'filterMonth',
            'filterYear',
            'availableYears',
            'user'
        ));
    }

    private function getStatsMutasiKeluar($user): array
    {
        try {
            $query = DB::table('mutasi_keluar')
                ->whereNull('deleted_at') // Filter Deleted
                ->where('status', '!=', 'Dibatalkan'); // Exclude cancelled

            if ($user->role === 'Operator') {
                $query->where('pengusul', $user->username)
                    ->where('npsn_sekolah_asal', $user->npsn);
            } elseif ($user->role === 'Sekolah') {
                $query->where('npsn_sekolah_asal', $user->npsn);
            }

            // Optimization: Single Query Aggregation
            $stats = $query->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Menunggu Verifikasi Dinas' THEN 1 ELSE 0 END) as menunggu,
                SUM(CASE WHEN status = 'Disetujui Dinas' THEN 1 ELSE 0 END) as disetujui,
                SUM(CASE WHEN status = 'Perlu Perbaikan' THEN 1 ELSE 0 END) as perbaikan,
                SUM(CASE WHEN status = 'Proses TTD' THEN 1 ELSE 0 END) as proses_ttd,
                SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) as selesai
            ")->first();

            return [
                'total' => (int) $stats->total,
                'menunggu' => (int) $stats->menunggu,
                'perbaikan' => (int) $stats->perbaikan,
                'proses_ttd' => (int) $stats->proses_ttd,
                'disetujui' => (int) $stats->disetujui,
                'selesai' => (int) $stats->selesai,
            ];
        } catch (\Exception $e) {
            return $this->emptyStats();
        }
    }

    private function getStatsMutasiMasuk($user): array
    {
        try {
            $query = DB::table('mutasi_masuk')
                ->whereNull('deleted_at') // Filter Deleted
                ->where('status', '!=', 'Dibatalkan'); // Exclude cancelled

            if ($user->role === 'Operator') {
                $query->where('pengusul', $user->username);
            } elseif ($user->role === 'Sekolah') {
                $query->where('npsn_sekolah_tujuan', $user->npsn);
            }

            // Optimization: Single Query Aggregation
            $stats = $query->selectRaw("
                COUNT(*) as total,
                SUM(jumlah_siswa) as total_siswa,
                SUM(CASE WHEN status = 'Menunggu Verifikasi Dinas' THEN 1 ELSE 0 END) as menunggu,
                SUM(CASE WHEN status = 'Disetujui Dinas' THEN 1 ELSE 0 END) as disetujui,
                SUM(CASE WHEN status = 'Proses TTD' THEN 1 ELSE 0 END) as proses_ttd,
                SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) as selesai,
                SUM(CASE WHEN status = 'Perlu Perbaikan' THEN 1 ELSE 0 END) as perbaikan
            ")->first();

            return [
                'total' => (int) $stats->total,
                'total_siswa' => (int) ($stats->total_siswa ?? 0),
                'menunggu' => (int) $stats->menunggu,
                'disetujui' => (int) $stats->disetujui,
                'proses_ttd' => (int) $stats->proses_ttd,
                'selesai' => (int) $stats->selesai,
                'perbaikan' => (int) $stats->perbaikan,
            ];
        } catch (\Exception $e) {
            return $this->emptyStatsMasuk();
        }
    }

    private function getStatsUsers($user): array
    {
        try {
            $stats = DB::table('users')
                ->where('npsn', $user->npsn)
                ->where('role', 'Operator')
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Aktif' OR is_active = 1 THEN 1 ELSE 0 END) as active
                ")->first();

            $total = (int) $stats->total;
            $active = (int) $stats->active;

            return [
                'total' => $total,
                'active' => $active,
                'inactive' => $total - $active,
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'active' => 0, 'inactive' => 0];
        }
    }

    /**
     * Get user stats for Dinas role - semua Sekolah dan Operator
     */
    private function getStatsDinasUsers(): array
    {
        try {
            $stats = DB::table('users')
                ->whereIn('role', ['Sekolah', 'Operator'])
                ->selectRaw("
                    SUM(CASE WHEN role = 'Sekolah' THEN 1 ELSE 0 END) as sekolah_total,
                    SUM(CASE WHEN role = 'Sekolah' AND (status = 'Aktif' OR is_active = 1) THEN 1 ELSE 0 END) as sekolah_active,
                    SUM(CASE WHEN role = 'Operator' THEN 1 ELSE 0 END) as operator_total,
                    SUM(CASE WHEN role = 'Operator' AND (status = 'Aktif' OR is_active = 1) THEN 1 ELSE 0 END) as operator_active
                ")->first();

            $sekolahTotal = (int) $stats->sekolah_total;
            $sekolahActive = (int) $stats->sekolah_active;
            $operatorTotal = (int) $stats->operator_total;
            $operatorActive = (int) $stats->operator_active;

            return [
                'sekolah_total' => $sekolahTotal,
                'sekolah_active' => $sekolahActive,
                'sekolah_inactive' => $sekolahTotal - $sekolahActive,
                'operator_total' => $operatorTotal,
                'operator_active' => $operatorActive,
                'operator_inactive' => $operatorTotal - $operatorActive,
                'total' => $sekolahTotal + $operatorTotal,
            ];
        } catch (\Exception $e) {
            return [
                'sekolah_total' => 0,
                'sekolah_active' => 0,
                'sekolah_inactive' => 0,
                'operator_total' => 0,
                'operator_active' => 0,
                'operator_inactive' => 0,
                'total' => 0,
            ];
        }
    }

    private function emptyStats(): array
    {
        return [
            'total' => 0,
            'menunggu' => 0,
            'disetujui' => 0,
            'perbaikan' => 0,
            'proses_ttd' => 0,
            'selesai' => 0,
        ];
    }

    private function emptyStatsMasuk(): array
    {
        return [
            'total' => 0,
            'total_siswa' => 0,
            'menunggu' => 0,
            'disetujui' => 0,
            'proses_ttd' => 0,
            'selesai' => 0,
            'perbaikan' => 0,
        ];
    }

    /**
     * Get school statistics for Mutasi Keluar (group by sekolah asal)
     */
    private function getSchoolStatsKeluar($user, $month = null, $year = null): array
    {
        try {
            $query = DB::table('mutasi_keluar')
                ->select('nama_sekolah_asal as nama_sekolah', DB::raw('COUNT(*) as jumlah'))
                ->whereNull('deleted_at') // Exclude Soft Deleted
                ->where('status', '!=', 'Dibatalkan') // Exclude Cancelled
                ->groupBy('nama_sekolah_asal')
                ->orderByDesc('jumlah')
                ->limit(10);

            // Filter by role
            if ($user->role === 'Operator') {
                $query->where('pengusul', $user->username)
                    ->where('npsn_sekolah_asal', $user->npsn);
            } elseif ($user->role === 'Sekolah') {
                $query->where('npsn_sekolah_asal', $user->npsn);
            }

            // Filter by month/year
            if ($month) {
                $query->whereMonth('tanggal_pengajuan', $month);
            }
            if ($year) {
                $query->whereYear('tanggal_pengajuan', $year);
            }

            return $query->get()->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get school statistics for Mutasi Masuk (group by sekolah tujuan)
     */
    private function getSchoolStatsMasuk($user, $month = null, $year = null): array
    {
        try {
            $query = DB::table('mutasi_masuk')
                ->select('nama_sekolah_tujuan as nama_sekolah', DB::raw('SUM(jumlah_siswa) as jumlah'))
                ->whereNull('deleted_at') // Exclude Soft Deleted
                ->where('status', '!=', 'Dibatalkan') // Exclude Cancelled
                ->groupBy('nama_sekolah_tujuan')
                ->orderByDesc('jumlah')
                ->limit(10);

            // Filter by role
            if ($user->role === 'Operator') {
                $query->where('pengusul', $user->username);
            } elseif ($user->role === 'Sekolah') {
                $query->where('npsn_sekolah_tujuan', $user->npsn);
            }

            // Filter by month/year
            if ($month) {
                $query->whereMonth('tanggal_pengajuan', $month);
            }
            if ($year) {
                $query->whereYear('tanggal_pengajuan', $year);
            }

            return $query->get()->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get available years from both mutasi tables (cached for 1 hour)
     */
    private function getAvailableYears(): array
    {
        try {
            $years = [];

            // From mutasi_keluar (no schema check - tables always exist in production)
            $keluarYears = DB::table('mutasi_keluar')
                ->selectRaw('DISTINCT YEAR(tanggal_pengajuan) as year')
                ->whereNotNull('tanggal_pengajuan')
                ->pluck('year')
                ->toArray();
            $years = array_merge($years, $keluarYears);

            // From mutasi_masuk
            $masukYears = DB::table('mutasi_masuk')
                ->selectRaw('DISTINCT YEAR(tanggal_pengajuan) as year')
                ->whereNotNull('tanggal_pengajuan')
                ->pluck('year')
                ->toArray();
            $years = array_merge($years, $masukYears);

            // Unique and sort descending
            $years = array_unique(array_filter($years));
            rsort($years);

            // Add current year if not exists
            if (!in_array(date('Y'), $years)) {
                array_unshift($years, (int) date('Y'));
            }

            return $years;
        } catch (\Exception $e) {
            return [(int) date('Y')];
        }
    }
}
