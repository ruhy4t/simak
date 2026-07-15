<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $dateFilter = $this->resolveDateFilter($request);
        $filterMonth = $dateFilter['month'];
        $filterYear = $dateFilter['year'];

        // Get stats for both Mutasi Keluar and Mutasi Masuk
        $statsKeluar = $this->getStatsMutasiKeluar($user, $dateFilter);
        $statsMasuk = $this->getStatsMutasiMasuk($user, $dateFilter);

        // Get user stats (for Sekolah and Dinas roles)
        $statsUsers = null;
        if ($user->role === 'Sekolah') {
            $statsUsers = $this->getStatsUsers($user);
        } elseif (in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            $statsUsers = $this->getStatsDinasUsers();
        }

        // Get school mutation statistics with filters
        $schoolStatsKeluar = $this->getSchoolStatsKeluar($user, $dateFilter);
        $schoolStatsMasuk = $this->getSchoolStatsMasuk($user, $dateFilter);

        // Get available years for filter
        $availableYears = $this->getAvailableYears();
        if ($filterYear && ! in_array($filterYear, $availableYears, true)) {
            $availableYears[] = $filterYear;
            rsort($availableYears);
        }

        return view('dashboard.index', compact(
            'statsKeluar',
            'statsMasuk',
            'statsUsers',
            'schoolStatsKeluar',
            'schoolStatsMasuk',
            'filterMonth',
            'filterYear',
            'dateFilter',
            'availableYears',
            'user'
        ));
    }

    private function getStatsMutasiKeluar($user, array $dateFilter): array
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

            $this->applyDateFilter($query, $dateFilter);

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

    private function getStatsMutasiMasuk($user, array $dateFilter): array
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

            $this->applyDateFilter($query, $dateFilter);

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
    private function getSchoolStatsKeluar($user, array $dateFilter): array
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

            $this->applyDateFilter($query, $dateFilter);

            return $query->get()->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get school statistics for Mutasi Masuk (group by sekolah tujuan)
     */
    private function getSchoolStatsMasuk($user, array $dateFilter): array
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

            $this->applyDateFilter($query, $dateFilter);

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
            $keluarYears = DB::table('mutasi_keluar')
                ->selectRaw('YEAR(tanggal_pengajuan) as year')
                ->whereNotNull('tanggal_pengajuan');

            $masukYears = DB::table('mutasi_masuk')
                ->selectRaw('YEAR(tanggal_pengajuan) as year')
                ->whereNotNull('tanggal_pengajuan');

            // One database round-trip instead of querying each table separately.
            $years = DB::query()
                ->fromSub($keluarYears->union($masukYears), 'mutation_years')
                ->whereNotNull('year')
                ->distinct()
                ->orderByDesc('year')
                ->pluck('year')
                ->map(fn ($year) => (int) $year)
                ->all();

            // Add current year if not exists
            if (!in_array(date('Y'), $years)) {
                array_unshift($years, (int) date('Y'));
            }

            return $years;
        } catch (\Exception $e) {
            return [(int) date('Y')];
        }
    }

    private function resolveDateFilter(Request $request): array
    {
        $validated = $request->validate([
            'period' => ['nullable', 'in:all,today,this_month,this_year,month,custom'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:1900,2200'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        $period = $validated['period'] ?? null;
        if ($period === null) {
            $period = isset($validated['start_date'], $validated['end_date'])
                ? 'custom'
                : (isset($validated['month']) || isset($validated['year']) ? 'month' : 'all');
        }

        $today = CarbonImmutable::today();
        $month = isset($validated['month']) ? (int) $validated['month'] : null;
        $year = isset($validated['year']) ? (int) $validated['year'] : null;
        $start = null;
        $end = null;

        if ($period === 'today') {
            $start = $today;
            $end = $today;
        } elseif ($period === 'this_month') {
            $start = $today->startOfMonth();
            $end = $today->endOfMonth();
            $month = $today->month;
            $year = $today->year;
        } elseif ($period === 'this_year') {
            $start = $today->startOfYear();
            $end = $today->endOfYear();
            $year = $today->year;
        } elseif ($period === 'month') {
            $month ??= $today->month;
            $year ??= $today->year;
            $start = CarbonImmutable::create($year, $month, 1)->startOfMonth();
            $end = $start->endOfMonth();
        } elseif ($period === 'custom' && isset($validated['start_date'], $validated['end_date'])) {
            $start = CarbonImmutable::createFromFormat('Y-m-d', $validated['start_date'])->startOfDay();
            $end = CarbonImmutable::createFromFormat('Y-m-d', $validated['end_date'])->startOfDay();
        } else {
            $period = 'all';
        }

        $label = match ($period) {
            'today' => 'Hari ini, '.$today->translatedFormat('j F Y'),
            'this_month', 'month' => $start->translatedFormat('F Y'),
            'this_year' => 'Tahun '.$today->year,
            'custom' => $start->equalTo($end)
                ? $start->translatedFormat('j F Y')
                : $start->translatedFormat('j M Y').' – '.$end->translatedFormat('j M Y'),
            default => 'Semua data',
        };

        return [
            'period' => $period,
            'start' => $start?->toDateString(),
            'end' => $end?->toDateString(),
            'end_exclusive' => $end?->addDay()->toDateString(),
            'month' => $month,
            'year' => $year,
            'label' => $label,
        ];
    }

    /**
     * Apply an index-friendly half-open date range.
     */
    private function applyDateFilter($query, array $dateFilter): void
    {
        if ($dateFilter['start'] === null || $dateFilter['end_exclusive'] === null) {
            return;
        }

        $query->where('tanggal_pengajuan', '>=', $dateFilter['start'])
            ->where('tanggal_pengajuan', '<', $dateFilter['end_exclusive']);
    }
}
