<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\MutasiKeluar;
use App\Models\Penandatangan;
use App\Models\SatuanPendidikan;
use App\Services\CohortReportService;
use App\Services\LongitudinalCohortService;
use App\Services\MutationStatisticsCache;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LaporanController extends Controller
{
    /**
     * Display the main reporting dashboard/menu.
     */
    public function index()
    {
        return view('laporan.index');
    }

    /**
     * Display the Rekapitulasi Report page.
     */
    public function rekap(Request $request)
    {
        $user = Auth::user();
        $start_date = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $end_date = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $data = $this->getRekapData($start_date, $end_date, $user);

        return view('laporan.rekap.index', compact('data', 'start_date', 'end_date'));
    }

    /**
     * Generate PDF for Rekapitulasi Report.
     */
    public function printRekap(Request $request)
    {
        $user = Auth::user();
        $start_date = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $end_date = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $data = $this->getRekapData($start_date, $end_date, $user);

        $pdf = Pdf::loadView('laporan.rekap.pdf', compact('data', 'start_date', 'end_date', 'user'))
            ->setPaper('a4', 'landscape');

        return $pdf->stream('Laporan_Rekapitulasi_Mutasi.pdf');
    }

    /**
     * Export Rekapitulasi Report to Excel (CSV format).
     */
    public function exportRekapExcel(Request $request)
    {
        $user = Auth::user();
        $start_date = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $end_date = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $data = $this->getRekapData($start_date, $end_date, $user);

        $filename = 'Rekapitulasi_Mutasi_'.$start_date.'_'.$end_date.'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data, $user) {
            $file = fopen('php://output', 'w');

            // Add BOM for Excel UTF-8 compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            if ($user->role === 'Sekolah') {
                fputcsv($file, [
                    'No',
                    'Periode',
                    'Masuk VII (L)',
                    'Masuk VII (P)',
                    'Masuk VII (Total)',
                    'Masuk VIII (L)',
                    'Masuk VIII (P)',
                    'Masuk VIII (Total)',
                    'Masuk IX (L)',
                    'Masuk IX (P)',
                    'Masuk IX (Total)',
                    'Total Masuk',
                    'Keluar VII (L)',
                    'Keluar VII (P)',
                    'Keluar VII (Total)',
                    'Keluar VIII (L)',
                    'Keluar VIII (P)',
                    'Keluar VIII (Total)',
                    'Keluar IX (L)',
                    'Keluar IX (P)',
                    'Keluar IX (Total)',
                    'Total Keluar',
                ]);
            } else {
                fputcsv($file, [
                    'No',
                    'Nama Sekolah',
                    'Masuk VII (L)',
                    'Masuk VII (P)',
                    'Masuk VII (Total)',
                    'Masuk VIII (L)',
                    'Masuk VIII (P)',
                    'Masuk VIII (Total)',
                    'Masuk IX (L)',
                    'Masuk IX (P)',
                    'Masuk IX (Total)',
                    'Total Masuk',
                    'Keluar VII (L)',
                    'Keluar VII (P)',
                    'Keluar VII (Total)',
                    'Keluar VIII (L)',
                    'Keluar VIII (P)',
                    'Keluar VIII (Total)',
                    'Keluar IX (L)',
                    'Keluar IX (P)',
                    'Keluar IX (Total)',
                    'Total Keluar',
                    'Jumlah Netto',
                ]);
            }

            // Data rows
            foreach ($data as $index => $item) {
                $row = [
                    $index + 1,
                    $user->role === 'Sekolah' ? $item['periode'] : $item['nama_sekolah'],

                    // Masuk VII
                    $item['masuk_vii']['l'],
                    $item['masuk_vii']['p'],
                    $item['masuk_vii']['t'],
                    // Masuk VIII
                    $item['masuk_viii']['l'],
                    $item['masuk_viii']['p'],
                    $item['masuk_viii']['t'],
                    // Masuk IX
                    $item['masuk_ix']['l'],
                    $item['masuk_ix']['p'],
                    $item['masuk_ix']['t'],
                    // Total Masuk
                    $item['masuk'],

                    // Keluar VII
                    $item['keluar_vii']['l'],
                    $item['keluar_vii']['p'],
                    $item['keluar_vii']['t'],
                    // Keluar VIII
                    $item['keluar_viii']['l'],
                    $item['keluar_viii']['p'],
                    $item['keluar_viii']['t'],
                    // Keluar IX
                    $item['keluar_ix']['l'],
                    $item['keluar_ix']['p'],
                    $item['keluar_ix']['t'],
                    // Total Keluar
                    $item['keluar'],
                ];

                if ($user->role !== 'Sekolah') {
                    $row[] = $item['netto'];
                }

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Helper to fetch Rekap data with Kelas breakdown.
     * OPTIMIZED: Uses aggregate queries instead of N+1 pattern
     * UPDATED: Includes Gender Breakdown (L/P)
     */
    private function getRekapData($start_date, $end_date, $user)
    {
        $role = $user->role;
        $data = [];

        if (in_array($role, ['Dinas', 'SuperAdmin'])) {
            // OPTIMIZED: Single aggregate query for Mutasi Masuk per school
            $masukStats = DB::table('mutasi_masuk')
                ->join('mutasi_masuk_siswa', 'mutasi_masuk.id_mutasi', '=', 'mutasi_masuk_siswa.id_mutasi')
                ->whereBetween('mutasi_masuk.tanggal_pengajuan', [$start_date, $end_date])
                ->where('mutasi_masuk.status', 'Selesai')
                ->selectRaw('
                    mutasi_masuk.npsn_sekolah_tujuan as npsn,
                    SUM(CASE WHEN mutasi_masuk_siswa.kelas IN ("7", "VII") AND mutasi_masuk_siswa.jenis_kelamin = "L" THEN 1 ELSE 0 END) as masuk_vii_l,
                    SUM(CASE WHEN mutasi_masuk_siswa.kelas IN ("7", "VII") AND mutasi_masuk_siswa.jenis_kelamin = "P" THEN 1 ELSE 0 END) as masuk_vii_p,
                    SUM(CASE WHEN mutasi_masuk_siswa.kelas IN ("8", "VIII") AND mutasi_masuk_siswa.jenis_kelamin = "L" THEN 1 ELSE 0 END) as masuk_viii_l,
                    SUM(CASE WHEN mutasi_masuk_siswa.kelas IN ("8", "VIII") AND mutasi_masuk_siswa.jenis_kelamin = "P" THEN 1 ELSE 0 END) as masuk_viii_p,
                    SUM(CASE WHEN mutasi_masuk_siswa.kelas IN ("9", "IX") AND mutasi_masuk_siswa.jenis_kelamin = "L" THEN 1 ELSE 0 END) as masuk_ix_l,
                    SUM(CASE WHEN mutasi_masuk_siswa.kelas IN ("9", "IX") AND mutasi_masuk_siswa.jenis_kelamin = "P" THEN 1 ELSE 0 END) as masuk_ix_p
                ')
                ->groupBy('mutasi_masuk.npsn_sekolah_tujuan')
                ->get()
                ->keyBy('npsn');

            // OPTIMIZED: Single aggregate query for Mutasi Keluar per school
            $keluarStats = MutasiKeluar::whereBetween('tanggal_pengajuan', [$start_date, $end_date])
                ->where('status', 'Selesai')
                ->selectRaw('
                    npsn_sekolah_asal as npsn,
                    SUM(CASE WHEN kelas IN ("7", "VII") AND jenis_kelamin = "L" THEN 1 ELSE 0 END) as keluar_vii_l,
                    SUM(CASE WHEN kelas IN ("7", "VII") AND jenis_kelamin = "P" THEN 1 ELSE 0 END) as keluar_vii_p,
                    SUM(CASE WHEN kelas IN ("8", "VIII") AND jenis_kelamin = "L" THEN 1 ELSE 0 END) as keluar_viii_l,
                    SUM(CASE WHEN kelas IN ("8", "VIII") AND jenis_kelamin = "P" THEN 1 ELSE 0 END) as keluar_viii_p,
                    SUM(CASE WHEN kelas IN ("9", "IX") AND jenis_kelamin = "L" THEN 1 ELSE 0 END) as keluar_ix_l,
                    SUM(CASE WHEN kelas IN ("9", "IX") AND jenis_kelamin = "P" THEN 1 ELSE 0 END) as keluar_ix_p
                ')
                ->groupBy('npsn_sekolah_asal')
                ->get()
                ->keyBy('npsn');

            // Get schools and merge pre-aggregated data
            $schools = SatuanPendidikan::eligibleBogorJuniorHigh()->orderBy('nama')->get();

            foreach ($schools as $school) {
                $masuk = $masukStats->get($school->npsn);
                $keluar = $keluarStats->get($school->npsn);

                // Masuk Breakdown
                $masukVII_L = $masuk ? (int) $masuk->masuk_vii_l : 0;
                $masukVII_P = $masuk ? (int) $masuk->masuk_vii_p : 0;
                $masukVIII_L = $masuk ? (int) $masuk->masuk_viii_l : 0;
                $masukVIII_P = $masuk ? (int) $masuk->masuk_viii_p : 0;
                $masukIX_L = $masuk ? (int) $masuk->masuk_ix_l : 0;
                $masukIX_P = $masuk ? (int) $masuk->masuk_ix_p : 0;

                $masukVII = $masukVII_L + $masukVII_P;
                $masukVIII = $masukVIII_L + $masukVIII_P;
                $masukIX = $masukIX_L + $masukIX_P;
                $masukTotal = $masukVII + $masukVIII + $masukIX;

                // Keluar Breakdown
                $keluarVII_L = $keluar ? (int) $keluar->keluar_vii_l : 0;
                $keluarVII_P = $keluar ? (int) $keluar->keluar_vii_p : 0;
                $keluarVIII_L = $keluar ? (int) $keluar->keluar_viii_l : 0;
                $keluarVIII_P = $keluar ? (int) $keluar->keluar_viii_p : 0;
                $keluarIX_L = $keluar ? (int) $keluar->keluar_ix_l : 0;
                $keluarIX_P = $keluar ? (int) $keluar->keluar_ix_p : 0;

                $keluarVII = $keluarVII_L + $keluarVII_P;
                $keluarVIII = $keluarVIII_L + $keluarVIII_P;
                $keluarIX = $keluarIX_L + $keluarIX_P;
                $keluarTotal = $keluarVII + $keluarVIII + $keluarIX;

                // Data Awal (From DB Profile)
                // Use new fields if available, otherwise fallback is tricky but we'll assume new fields exist as per migration
                $awalVII_L = (int) $school->jumlah_siswa_7_l;
                $awalVII_P = (int) $school->jumlah_siswa_7_p;
                $awalVIII_L = (int) $school->jumlah_siswa_8_l;
                $awalVIII_P = (int) $school->jumlah_siswa_8_p;
                $awalIX_L = (int) $school->jumlah_siswa_9_l;
                $awalIX_P = (int) $school->jumlah_siswa_9_p;

                $awalVII = $awalVII_L + $awalVII_P;
                $awalVIII = $awalVIII_L + $awalVIII_P;
                $awalIX = $awalIX_L + $awalIX_P;
                $awalTotal = $awalVII + $awalVIII + $awalIX;

                // Data Akhir (Awal + Masuk - Keluar)
                $akhirTotal = $awalTotal + $masukTotal - $keluarTotal;

                if ($masukTotal > 0 || $keluarTotal > 0 || $awalTotal > 0) {
                    $data[] = [
                        'nama_sekolah' => $school->nama,
                        // Awal Structure [Total, L, P]
                        'awal_vii' => ['t' => $awalVII, 'l' => $awalVII_L, 'p' => $awalVII_P],
                        'awal_viii' => ['t' => $awalVIII, 'l' => $awalVIII_L, 'p' => $awalVIII_P],
                        'awal_ix' => ['t' => $awalIX, 'l' => $awalIX_L, 'p' => $awalIX_P],
                        'awal' => $awalTotal,

                        // Masuk Structure [Total, L, P]
                        'masuk_vii' => ['t' => $masukVII, 'l' => $masukVII_L, 'p' => $masukVII_P],
                        'masuk_viii' => ['t' => $masukVIII, 'l' => $masukVIII_L, 'p' => $masukVIII_P],
                        'masuk_ix' => ['t' => $masukIX, 'l' => $masukIX_L, 'p' => $masukIX_P],
                        'masuk' => $masukTotal,

                        // Keluar Structure [Total, L, P]
                        'keluar_vii' => ['t' => $keluarVII, 'l' => $keluarVII_L, 'p' => $keluarVII_P],
                        'keluar_viii' => ['t' => $keluarVIII, 'l' => $keluarVIII_L, 'p' => $keluarVIII_P],
                        'keluar_ix' => ['t' => $keluarIX, 'l' => $keluarIX_L, 'p' => $keluarIX_P],
                        'keluar' => $keluarTotal,

                        // Akhir Structure [Total, L, P]
                        'akhir_vii' => ['t' => ($awalVII_L + $masukVII_L - $keluarVII_L) + ($awalVII_P + $masukVII_P - $keluarVII_P), 'l' => ($awalVII_L + $masukVII_L - $keluarVII_L), 'p' => ($awalVII_P + $masukVII_P - $keluarVII_P)],
                        'akhir_viii' => ['t' => ($awalVIII_L + $masukVIII_L - $keluarVIII_L) + ($awalVIII_P + $masukVIII_P - $keluarVIII_P), 'l' => ($awalVIII_L + $masukVIII_L - $keluarVIII_L), 'p' => ($awalVIII_P + $masukVIII_P - $keluarVIII_P)],
                        'akhir_ix' => ['t' => ($awalIX_L + $masukIX_L - $keluarIX_L) + ($awalIX_P + $masukIX_P - $keluarIX_P), 'l' => ($awalIX_L + $masukIX_L - $keluarIX_L), 'p' => ($awalIX_P + $masukIX_P - $keluarIX_P)],
                        'akhir' => $awalTotal + $masukTotal - $keluarTotal,

                        // Netto
                        'netto' => $masukTotal - $keluarTotal,
                    ];
                }
            }
        } elseif (in_array($role, ['Sekolah', 'Operator'])) {
            // For Sekolah: Group by Month with Kelas breakdown
            $npsn = $user->sekolah->npsn ?? $user->npsn;
            $school = $user->sekolah; // Get school profile data
            $period = CarbonPeriod::create($start_date, '1 month', $end_date);

            foreach ($period as $dt) {
                $monthStart = $dt->copy()->startOfMonth()->format('Y-m-d');
                $monthEnd = $dt->copy()->endOfMonth()->format('Y-m-d');

                if ($monthStart < $start_date) {
                    $monthStart = $start_date;
                }
                if ($monthEnd > $end_date) {
                    $monthEnd = $end_date;
                }
                if ($monthStart > $monthEnd) {
                    continue;
                }

                // Mutasi Masuk per kelas & gender
                $masukStats = DB::table('mutasi_masuk')
                    ->join('mutasi_masuk_siswa', 'mutasi_masuk.id_mutasi', '=', 'mutasi_masuk_siswa.id_mutasi')
                    ->where('mutasi_masuk.npsn_sekolah_tujuan', $npsn)
                    ->whereBetween('mutasi_masuk.tanggal_pengajuan', [$monthStart, $monthEnd])
                    ->where('mutasi_masuk.status', 'Selesai')
                    ->selectRaw('
                        SUM(CASE WHEN mutasi_masuk_siswa.kelas IN ("7", "VII") AND mutasi_masuk_siswa.jenis_kelamin = "L" THEN 1 ELSE 0 END) as vii_l,
                        SUM(CASE WHEN mutasi_masuk_siswa.kelas IN ("7", "VII") AND mutasi_masuk_siswa.jenis_kelamin = "P" THEN 1 ELSE 0 END) as vii_p,
                        SUM(CASE WHEN mutasi_masuk_siswa.kelas IN ("8", "VIII") AND mutasi_masuk_siswa.jenis_kelamin = "L" THEN 1 ELSE 0 END) as viii_l,
                        SUM(CASE WHEN mutasi_masuk_siswa.kelas IN ("8", "VIII") AND mutasi_masuk_siswa.jenis_kelamin = "P" THEN 1 ELSE 0 END) as viii_p,
                        SUM(CASE WHEN mutasi_masuk_siswa.kelas IN ("9", "IX") AND mutasi_masuk_siswa.jenis_kelamin = "L" THEN 1 ELSE 0 END) as ix_l,
                        SUM(CASE WHEN mutasi_masuk_siswa.kelas IN ("9", "IX") AND mutasi_masuk_siswa.jenis_kelamin = "P" THEN 1 ELSE 0 END) as ix_p
                    ')
                    ->first();

                $m_vii_l = (int) ($masukStats->vii_l ?? 0);
                $m_vii_p = (int) ($masukStats->vii_p ?? 0);
                $m_viii_l = (int) ($masukStats->viii_l ?? 0);
                $m_viii_p = (int) ($masukStats->viii_p ?? 0);
                $m_ix_l = (int) ($masukStats->ix_l ?? 0);
                $m_ix_p = (int) ($masukStats->ix_p ?? 0);
                $masukTotal = $m_vii_l + $m_vii_p + $m_viii_l + $m_viii_p + $m_ix_l + $m_ix_p;

                // Mutasi Keluar per kelas & gender
                $keluarStats = MutasiKeluar::where('npsn_sekolah_asal', $npsn)
                    ->whereBetween('tanggal_pengajuan', [$monthStart, $monthEnd])
                    ->where('status', 'Selesai')
                    ->selectRaw('
                        SUM(CASE WHEN kelas IN ("7", "VII") AND jenis_kelamin = "L" THEN 1 ELSE 0 END) as vii_l,
                        SUM(CASE WHEN kelas IN ("7", "VII") AND jenis_kelamin = "P" THEN 1 ELSE 0 END) as vii_p,
                        SUM(CASE WHEN kelas IN ("8", "VIII") AND jenis_kelamin = "L" THEN 1 ELSE 0 END) as viii_l,
                        SUM(CASE WHEN kelas IN ("8", "VIII") AND jenis_kelamin = "P" THEN 1 ELSE 0 END) as viii_p,
                        SUM(CASE WHEN kelas IN ("9", "IX") AND jenis_kelamin = "L" THEN 1 ELSE 0 END) as ix_l,
                        SUM(CASE WHEN kelas IN ("9", "IX") AND jenis_kelamin = "P" THEN 1 ELSE 0 END) as ix_p
                    ')
                    ->first();

                $k_vii_l = (int) ($keluarStats->vii_l ?? 0);
                $k_vii_p = (int) ($keluarStats->vii_p ?? 0);
                $k_viii_l = (int) ($keluarStats->viii_l ?? 0);
                $k_viii_p = (int) ($keluarStats->viii_p ?? 0);
                $k_ix_l = (int) ($keluarStats->ix_l ?? 0);
                $k_ix_p = (int) ($keluarStats->ix_p ?? 0);
                $keluarTotal = $k_vii_l + $k_vii_p + $k_viii_l + $k_viii_p + $k_ix_l + $k_ix_p;

                // Data Awal (From DB Profile - Static)
                $awalVII_L = (int) ($school->jumlah_siswa_7_l ?? 0);
                $awalVII_P = (int) ($school->jumlah_siswa_7_p ?? 0);
                $awalVIII_L = (int) ($school->jumlah_siswa_8_l ?? 0);
                $awalVIII_P = (int) ($school->jumlah_siswa_8_p ?? 0);
                $awalIX_L = (int) ($school->jumlah_siswa_9_l ?? 0);
                $awalIX_P = (int) ($school->jumlah_siswa_9_p ?? 0);

                $awalVII = $awalVII_L + $awalVII_P;
                $awalVIII = $awalVIII_L + $awalVIII_P;
                $awalIX = $awalIX_L + $awalIX_P;
                $awalTotal = $awalVII + $awalVIII + $awalIX;

                $data[] = [
                    'periode' => $dt->translatedFormat('F Y'),
                    // Awal
                    'awal_vii' => ['t' => $awalVII, 'l' => $awalVII_L, 'p' => $awalVII_P],
                    'awal_viii' => ['t' => $awalVIII, 'l' => $awalVIII_L, 'p' => $awalVIII_P],
                    'awal_ix' => ['t' => $awalIX, 'l' => $awalIX_L, 'p' => $awalIX_P],
                    'awal' => $awalTotal,

                    // Masuk
                    'masuk_vii' => ['t' => $m_vii_l + $m_vii_p, 'l' => $m_vii_l, 'p' => $m_vii_p],
                    'masuk_viii' => ['t' => $m_viii_l + $m_viii_p, 'l' => $m_viii_l, 'p' => $m_viii_p],
                    'masuk_ix' => ['t' => $m_ix_l + $m_ix_p, 'l' => $m_ix_l, 'p' => $m_ix_p],
                    'masuk' => $masukTotal,

                    // Keluar
                    'keluar_vii' => ['t' => $k_vii_l + $k_vii_p, 'l' => $k_vii_l, 'p' => $k_vii_p],
                    'keluar_viii' => ['t' => $k_viii_l + $k_viii_p, 'l' => $k_viii_l, 'p' => $k_viii_p],
                    'keluar_ix' => ['t' => $k_ix_l + $k_ix_p, 'l' => $k_ix_l, 'p' => $k_ix_p],
                    'keluar' => $keluarTotal,

                    // Akhir Structure [Total, L, P]
                    'akhir_vii' => ['t' => ($awalVII_L + $m_vii_l - $k_vii_l) + ($awalVII_P + $m_vii_p - $k_vii_p), 'l' => ($awalVII_L + $m_vii_l - $k_vii_l), 'p' => ($awalVII_P + $m_vii_p - $k_vii_p)],
                    'akhir_viii' => ['t' => ($awalVIII_L + $m_viii_l - $k_viii_l) + ($awalVIII_P + $m_viii_p - $k_viii_p), 'l' => ($awalVIII_L + $m_viii_l - $k_viii_l), 'p' => ($awalVIII_P + $m_viii_p - $k_viii_p)],
                    'akhir_ix' => ['t' => ($awalIX_L + $m_ix_l - $k_ix_l) + ($awalIX_P + $m_ix_p - $k_ix_p), 'l' => ($awalIX_L + $m_ix_l - $k_ix_l), 'p' => ($awalIX_P + $m_ix_p - $k_ix_p)],
                    'akhir' => $awalTotal + $masukTotal - $keluarTotal,

                    // Netto
                    'netto' => $masukTotal - $keluarTotal,
                ];
            }
        }

        return $data;
    }

    /**
     * Display the Trend Charts page.
     */
    public function tren(Request $request)
    {
        $user = Auth::user();
        $year = $request->input('year', date('Y'));
        $month = $request->input('month'); // Nullable (All Months)

        $chartData = $this->getTrendData($year, $month, $user);

        return view('laporan.tren.index', compact('chartData', 'year', 'month'));
    }

    /**
     * Display annual reconciliation and longitudinal intake cohorts.
     */
    public function kohort(
        Request $request,
        CohortReportService $cohortService,
        LongitudinalCohortService $longitudinalCohortService,
    ) {
        abort_unless($cohortService->available(), 503, 'Fitur laporan kohort belum siap. Jalankan migrasi database.');

        $user = Auth::user();
        $academicYears = AcademicYear::query()
            ->whereIn('status', [AcademicYear::STATUS_ACTIVE, AcademicYear::STATUS_CLOSED])
            ->orderByDesc('is_active')
            ->orderByDesc('name')
            ->get();
        $academicYear = $academicYears->firstWhere('id', $request->integer('academic_year'))
            ?? $academicYears->first();
        $cohortEntryYears = AcademicYear::query()
            ->whereHas('studentCounts', fn ($query) => $query->where('grade', 7))
            ->orderByDesc('starts_on')
            ->orderByDesc('name')
            ->get();
        $cohortEntryYear = $cohortEntryYears->firstWhere('id', $request->integer('cohort_entry_year'))
            ?? $cohortEntryYears->first();

        abort_unless($academicYear || $cohortEntryYear, 404, 'Data rekonsiliasi dan kohort belum tersedia.');

        $schools = collect();
        $school = null;
        if (in_array($user->role, ['Sekolah', 'Operator'], true)) {
            $school = $user->sekolah;
            abort_unless($school, 404, 'Data sekolah tidak ditemukan.');
        } else {
            $schools = SatuanPendidikan::eligibleBogorJuniorHigh()
                ->whereHas('studentCounts', function ($query) use ($academicYear, $cohortEntryYear): void {
                    $query->whereIn('academic_year_id', array_filter([
                        $academicYear?->id,
                        $cohortEntryYear?->id,
                    ]));
                })
                ->orderBy('nama')
                ->get(['id', 'npsn', 'nama']);
            if ($request->filled('school')) {
                $school = $schools->firstWhere('id', $request->integer('school'));
                abort_unless($school, 404, 'Satuan pendidikan tidak ditemukan.');
            }
        }

        $cohort = $academicYear
            ? $cohortService->report($academicYear, $school?->id)
            : null;
        $longitudinalCohort = $cohortEntryYear && $longitudinalCohortService->available()
            ? $longitudinalCohortService->report($cohortEntryYear, $school?->id)
            : null;

        return view('laporan.kohort.index', compact(
            'academicYears', 'academicYear', 'cohortEntryYears', 'cohortEntryYear',
            'schools', 'school', 'cohort', 'longitudinalCohort'
        ));
    }

    /**
     * Display city-wide mutation trends without requiring authentication.
     */
    public function publicTren(
        Request $request,
        CohortReportService $cohortService,
        LongitudinalCohortService $longitudinalCohortService,
    ) {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2020', 'max:'.date('Y')],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'academic_year' => ['nullable', 'integer', 'min:1'],
            'cohort_entry_year' => ['nullable', 'integer', 'min:1'],
            'school_status' => ['nullable', Rule::in(['Negeri', 'Swasta'])],
            'npsn' => ['nullable', 'string', 'regex:/^[0-9]{8}$/'],
        ], [
            'school_status.in' => 'Status sekolah harus Negeri atau Swasta.',
            'npsn.regex' => 'NPSN harus terdiri dari tepat 8 digit angka.',
        ]);

        $year = (int) ($validated['year'] ?? date('Y'));
        $month = isset($validated['month']) ? (int) $validated['month'] : null;
        $npsn = $validated['npsn'] ?? null;
        $schoolStatus = $npsn ? null : ($validated['school_status'] ?? null);
        $selectedSchool = null;
        $eligibleSchoolQuery = SatuanPendidikan::eligibleBogorJuniorHigh();

        if ($npsn) {
            $selectedSchool = (clone $eligibleSchoolQuery)
                ->where('npsn', $npsn)
                ->first();

            if (! $selectedSchool) {
                throw ValidationException::withMessages([
                    'npsn' => 'NPSN tidak ditemukan atau bukan SMP/SPK SMP Kota Bogor.',
                ]);
            }

            $eligibleSchoolNpsns = [$selectedSchool->npsn];
        } elseif ($schoolStatus) {
            $eligibleSchoolNpsns = $eligibleSchoolQuery
                ->where('status_sp', $schoolStatus)
                ->pluck('npsn')
                ->all();
        } else {
            // Tanpa filter sekolah, gunakan seluruh riwayat mutasi seperti
            // dashboard Dinas. Data lama tidak selalu mempunyai referensi NPSN.
            $eligibleSchoolNpsns = null;
        }

        $statusKey = strtolower($schoolStatus ?? 'all');
        $npsnKey = $selectedSchool?->npsn ?? 'all';
        $statisticsVersion = MutationStatisticsCache::version();
        $cacheKey = 'public.statistics.trend.v8.'.$statisticsVersion
            .'.'.($month ?: 'all').'.'.$year
            .'.status-'.$statusKey.'.npsn-'.$npsnKey;

        $chartData = Cache::remember(
            $cacheKey,
            now()->addMinutes(30),
            fn () => $this->getTrendData($year, $month, null, $eligibleSchoolNpsns)
        );

        $cohortYears = collect();
        $cohortYear = null;
        $cohort = null;
        if ($cohortService->available()) {
            $cohortYears = AcademicYear::query()
                ->whereIn('status', [AcademicYear::STATUS_ACTIVE, AcademicYear::STATUS_CLOSED])
                ->orderByDesc('is_active')
                ->orderByDesc('name')
                ->get();
            $cohortYear = $cohortYears->firstWhere('id', (int) ($validated['academic_year'] ?? 0))
                ?? $cohortYears->first();

            if ($cohortYear) {
                $cohort = Cache::remember(
                    "public.statistics.reconciliation.v2.{$statisticsVersion}.{$cohortYear->id}.status-{$statusKey}.npsn-{$npsnKey}",
                    now()->addMinutes(30),
                    fn () => $cohortService->report($cohortYear, $selectedSchool?->id, $schoolStatus)
                );
            }
        }

        $cohortEntryYears = collect();
        $cohortEntryYear = null;
        $longitudinalCohort = null;
        if ($longitudinalCohortService->available()) {
            $cohortEntryYears = AcademicYear::query()
                ->whereHas('studentCounts', fn ($query) => $query->where('grade', 7))
                ->orderByDesc('starts_on')
                ->orderByDesc('name')
                ->get();
            $cohortEntryYear = $cohortEntryYears->firstWhere('id', (int) ($validated['cohort_entry_year'] ?? 0))
                ?? $cohortEntryYears->first();

            if ($cohortEntryYear) {
                $longitudinalCohort = Cache::remember(
                    "public.statistics.longitudinal-cohort.v1.{$statisticsVersion}.{$cohortEntryYear->id}.status-{$statusKey}.npsn-{$npsnKey}",
                    now()->addMinutes(30),
                    fn () => $longitudinalCohortService->report(
                        $cohortEntryYear,
                        $selectedSchool?->id,
                        $schoolStatus
                    )
                );
            }
        }

        return response()
            ->view('public.statistics', compact(
                'chartData', 'year', 'month', 'cohortYears', 'cohortYear', 'cohort',
                'cohortEntryYears', 'cohortEntryYear', 'longitudinalCohort',
                'schoolStatus', 'npsn', 'selectedSchool'
            ))
            ->header('Cache-Control', 'public, max-age=0, must-revalidate');
    }

    /**
     * Helper to fetch Trend data.
     */
    private function getTrendData(
        $year,
        $month,
        $user = null,
        ?array $publicSchoolNpsns = null,
    ) {
        $role = $user?->role;

        [$selectUnit, $selectUnitKeluar] = match (DB::connection()->getDriverName()) {
            'sqlite' => $month
                ? [
                    "CAST(strftime('%d', mutasi_masuk.tanggal_pengajuan) AS INTEGER) as unit",
                    "CAST(strftime('%d', tanggal_pengajuan) AS INTEGER) as unit",
                ]
                : [
                    "CAST(strftime('%m', mutasi_masuk.tanggal_pengajuan) AS INTEGER) as unit",
                    "CAST(strftime('%m', tanggal_pengajuan) AS INTEGER) as unit",
                ],
            'pgsql' => $month
                ? [
                    'EXTRACT(DAY FROM mutasi_masuk.tanggal_pengajuan) as unit',
                    'EXTRACT(DAY FROM tanggal_pengajuan) as unit',
                ]
                : [
                    'EXTRACT(MONTH FROM mutasi_masuk.tanggal_pengajuan) as unit',
                    'EXTRACT(MONTH FROM tanggal_pengajuan) as unit',
                ],
            default => $month
                ? [
                    'DAY(mutasi_masuk.tanggal_pengajuan) as unit',
                    'DAY(tanggal_pengajuan) as unit',
                ]
                : [
                    'MONTH(mutasi_masuk.tanggal_pengajuan) as unit',
                    'MONTH(tanggal_pengajuan) as unit',
                ],
        };

        // Determine Mode: Daily (if month selected) or Monthly (if year only)
        if ($month) {
            $daysInMonth = Carbon::create($year, $month)->daysInMonth;
            $range = range(1, $daysInMonth);
            $periodStart = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
            $periodEnd = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
        } else {
            $range = range(1, 12);
            $periodStart = Carbon::create($year, 1, 1)->startOfYear()->toDateString();
            $periodEnd = Carbon::create($year, 12, 31)->endOfYear()->toDateString();
        }

        // --- MUTASI MASUK ---
        $masukQuery = DB::table('mutasi_masuk')
            ->join('mutasi_masuk_siswa', 'mutasi_masuk.id_mutasi', '=', 'mutasi_masuk_siswa.id_mutasi')
            ->selectRaw("$selectUnit,
                SUM(CASE WHEN mutasi_masuk_siswa.asal_luar_negeri = 1 THEN 1 ELSE 0 END) as luar_negeri,
                SUM(CASE WHEN mutasi_masuk_siswa.asal_luar_negeri = 0 AND mutasi_masuk_siswa.kabupaten_kota_asal LIKE '%KOTA BOGOR%' THEN 1 ELSE 0 END) as dalam_kota,
                SUM(CASE WHEN mutasi_masuk_siswa.asal_luar_negeri = 0 AND (mutasi_masuk_siswa.kabupaten_kota_asal NOT LIKE '%KOTA BOGOR%' OR mutasi_masuk_siswa.kabupaten_kota_asal IS NULL) THEN 1 ELSE 0 END) as luar_kota,
                SUM(CASE WHEN mutasi_masuk_siswa.kelas IN ('7', 'VII') THEN 1 ELSE 0 END) as kelas_vii,
                SUM(CASE WHEN mutasi_masuk_siswa.kelas IN ('8', 'VIII') THEN 1 ELSE 0 END) as kelas_viii,
                SUM(CASE WHEN mutasi_masuk_siswa.kelas IN ('9', 'IX') THEN 1 ELSE 0 END) as kelas_ix")
            ->whereNull('mutasi_masuk.deleted_at')
            ->whereBetween('mutasi_masuk.tanggal_pengajuan', [$periodStart, $periodEnd])
            ->where('mutasi_masuk.status', 'Selesai');

        $legacyMasukQuery = DB::table('mutasi_masuk')
            ->whereNull('mutasi_masuk.deleted_at')
            ->whereBetween('mutasi_masuk.tanggal_pengajuan', [$periodStart, $periodEnd])
            ->where('mutasi_masuk.status', 'Selesai')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('mutasi_masuk_siswa')
                    ->whereColumn('mutasi_masuk_siswa.id_mutasi', 'mutasi_masuk.id_mutasi');
            });

        if ($user && in_array($role, ['Sekolah', 'Operator'], true)) {
            $npsn = $user->sekolah->npsn ?? $user->npsn;
            $masukQuery->where('mutasi_masuk.npsn_sekolah_tujuan', $npsn);
            $legacyMasukQuery->where('mutasi_masuk.npsn_sekolah_tujuan', $npsn);
        } elseif (! $user && $publicSchoolNpsns !== null) {
            $masukQuery->whereIn('mutasi_masuk.npsn_sekolah_tujuan', $publicSchoolNpsns);
            $legacyMasukQuery->whereIn('mutasi_masuk.npsn_sekolah_tujuan', $publicSchoolNpsns);
        }

        $masukResults = $masukQuery->groupBy('unit')->get()->keyBy('unit');
        $masukTotal = (int) $masukResults->sum(
            fn (object $row): int => (int) $row->dalam_kota
                + (int) $row->luar_kota
                + (int) $row->luar_negeri
        );
        $masukTotal += (int) $legacyMasukQuery->sum('mutasi_masuk.jumlah_siswa');

        // --- MUTASI KELUAR ---
        $keluarQuery = MutasiKeluar::selectRaw("$selectUnitKeluar,
                SUM(CASE WHEN UPPER(TRIM(COALESCE(provinsi_tujuan, ''))) = 'LUAR NEGERI' THEN 1 ELSE 0 END) as luar_negeri,
                SUM(CASE WHEN UPPER(TRIM(COALESCE(provinsi_tujuan, ''))) <> 'LUAR NEGERI' AND UPPER(COALESCE(kabupaten_kota_tujuan, '')) LIKE '%KOTA BOGOR%' THEN 1 ELSE 0 END) as dalam_kota,
                SUM(CASE WHEN UPPER(TRIM(COALESCE(provinsi_tujuan, ''))) <> 'LUAR NEGERI' AND UPPER(COALESCE(kabupaten_kota_tujuan, '')) NOT LIKE '%KOTA BOGOR%' THEN 1 ELSE 0 END) as luar_kota,
                SUM(CASE WHEN kelas IN ('7', 'VII') THEN 1 ELSE 0 END) as kelas_vii,
                SUM(CASE WHEN kelas IN ('8', 'VIII') THEN 1 ELSE 0 END) as kelas_viii,
                SUM(CASE WHEN kelas IN ('9', 'IX') THEN 1 ELSE 0 END) as kelas_ix")
            ->whereNull('deleted_at')
            ->whereBetween('tanggal_pengajuan', [$periodStart, $periodEnd])
            ->where('status', 'Selesai');
        if ($user && in_array($role, ['Sekolah', 'Operator'], true)) {
            $npsn = $user->sekolah->npsn ?? $user->npsn;
            $keluarQuery->where('npsn_sekolah_asal', $npsn);
        } elseif (! $user && $publicSchoolNpsns !== null) {
            $keluarQuery->whereIn('mutasi_keluar.npsn_sekolah_asal', $publicSchoolNpsns);
        }

        $keluarResults = $keluarQuery->groupBy('unit')->get()->keyBy('unit');
        $keluarTotal = (int) $keluarResults->sum(
            fn (object $row): int => (int) $row->dalam_kota
                + (int) $row->luar_kota
                + (int) $row->luar_negeri
        );

        // Format data for Chart
        $data = [
            'labels' => [],
            'masuk' => ['dalam' => [], 'luar_kota' => [], 'luar_negeri' => []],
            'keluar' => ['dalam' => [], 'luar_kota' => [], 'luar_negeri' => []],
            'kelas' => [
                'labels' => ['VII', 'VIII', 'IX'],
                'masuk' => [0, 0, 0],
                'keluar' => [0, 0, 0],
            ],
            'totals' => ['masuk' => $masukTotal, 'keluar' => $keluarTotal],
        ];

        foreach ($range as $unit) {
            // Label Generation
            if ($month) {
                // Daily: "1", "2"... or "1 Jan"
                $data['labels'][] = $unit;
            } else {
                // Monthly: "Januari"
                $data['labels'][] = Carbon::create($year, $unit, 1)->translatedFormat('F');
            }

            // Masuk
            $mItem = $masukResults->get($unit);
            $data['masuk']['dalam'][] = $mItem ? (int) $mItem->dalam_kota : 0;
            $data['masuk']['luar_kota'][] = $mItem ? (int) $mItem->luar_kota : 0;
            $data['masuk']['luar_negeri'][] = $mItem ? (int) $mItem->luar_negeri : 0;
            if ($mItem) {
                $data['kelas']['masuk'][0] += (int) $mItem->kelas_vii;
                $data['kelas']['masuk'][1] += (int) $mItem->kelas_viii;
                $data['kelas']['masuk'][2] += (int) $mItem->kelas_ix;
            }

            // Keluar
            $kItem = $keluarResults->get($unit);
            $data['keluar']['dalam'][] = $kItem ? (int) $kItem->dalam_kota : 0;
            $data['keluar']['luar_kota'][] = $kItem ? (int) $kItem->luar_kota : 0;
            $data['keluar']['luar_negeri'][] = $kItem ? (int) $kItem->luar_negeri : 0;
            if ($kItem) {
                $data['kelas']['keluar'][0] += (int) $kItem->kelas_vii;
                $data['kelas']['keluar'][1] += (int) $kItem->kelas_viii;
                $data['kelas']['keluar'][2] += (int) $kItem->kelas_ix;
            }
        }

        return $data;
    }

    /**
     * Display the Nominatif Report page.
     */
    public function nominatif(Request $request)
    {
        $user = Auth::user();
        $start_date = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $end_date = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $type = $request->input('type', 'masuk'); // masuk, keluar

        $data = $this->getNominatifData($start_date, $end_date, $type, $user);

        return view('laporan.nominatif.index', compact('data', 'start_date', 'end_date', 'type'));
    }

    /**
     * Print PDF for Nominatif Report.
     */
    public function printNominatif(Request $request)
    {
        $user = Auth::user();
        $start_date = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $end_date = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $type = $request->input('type', 'masuk');

        $data = $this->getNominatifData($start_date, $end_date, $type, $user);

        $pdf = Pdf::loadView('laporan.nominatif.pdf', compact('data', 'start_date', 'end_date', 'type', 'user'))
            ->setPaper('a4', 'landscape');

        return $pdf->stream('Daftar_Nominatif_Mutasi.pdf');
    }

    /**
     * Export Nominatif Report to Excel (CSV).
     */
    public function exportNominatifExcel(Request $request)
    {
        $user = Auth::user();
        $start_date = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $end_date = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $type = $request->input('type', 'masuk');

        $data = $this->getNominatifData($start_date, $end_date, $type, $user);
        $filename = 'Daftar_Nominatif_'.$type.'_'.$start_date.'_'.$end_date.'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM

            // Header
            fputcsv($file, ['No', 'Tanggal', 'Jenis', 'Nama Siswa', 'NISN', 'L/P', 'Kelas', 'Asal Sekolah', 'Tujuan Sekolah', 'Status']);

            foreach ($data as $index => $item) {
                fputcsv($file, [
                    $index + 1,
                    $item->tanggal,
                    strtoupper($item->jenis),
                    $item->nama_siswa,
                    $item->nisn,
                    $item->jenis_kelamin,
                    $item->kelas,
                    $item->asal,
                    $item->tujuan,
                    $item->status,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Helper to fetch Nominatif data.
     */
    private function getNominatifData($start, $end, $type, $user)
    {
        $role = $user->role;
        $items = collect();

        // Mutasi Masuk
        if ($type === 'masuk') {
            $query = DB::table('mutasi_masuk')
                ->join('mutasi_masuk_siswa', 'mutasi_masuk.id_mutasi', '=', 'mutasi_masuk_siswa.id_mutasi')
                ->select(
                    'mutasi_masuk.tanggal_pengajuan as tanggal',
                    'mutasi_masuk_siswa.nama_murid as nama_siswa',
                    'mutasi_masuk_siswa.nisn',
                    'mutasi_masuk_siswa.jenis_kelamin', // Added
                    'mutasi_masuk_siswa.kelas',
                    'mutasi_masuk_siswa.nama_sekolah_asal as asal',
                    'mutasi_masuk.nama_sekolah_tujuan as tujuan',
                    'mutasi_masuk.status',
                    'mutasi_masuk.npsn_sekolah_tujuan'
                )
                ->whereBetween('mutasi_masuk.tanggal_pengajuan', [$start, $end])
                ->whereIn('mutasi_masuk.npsn_sekolah_tujuan', $this->eligibleBogorJuniorHighNpsns());

            if (in_array($role, ['Sekolah', 'Operator'])) {
                $query->where('mutasi_masuk.npsn_sekolah_tujuan', $user->sekolah->npsn ?? $user->npsn);
            }

            $masuk = $query->get()->map(function ($item) {
                $item->jenis = 'masuk';

                return $item;
            });
            $items = $items->merge($masuk);
        }

        // Mutasi Keluar
        if ($type === 'keluar') {
            $query = MutasiKeluar::select(
                'tanggal_pengajuan as tanggal',
                'nama_murid as nama_siswa',
                'nisn',
                'jenis_kelamin', // Added
                'kelas',
                'nama_sekolah_asal as asal',
                'nama_sekolah_tujuan as tujuan',
                'status',
                'npsn_sekolah_asal'
            )
                ->whereBetween('tanggal_pengajuan', [$start, $end])
                ->whereIn('npsn_sekolah_asal', $this->eligibleBogorJuniorHighNpsns());

            if (in_array($role, ['Sekolah', 'Operator'])) {
                $query->where('npsn_sekolah_asal', $user->sekolah->npsn ?? $user->npsn);
            }

            $keluar = $query->get()->map(function ($item) {
                $item->jenis = 'keluar';

                return $item;
            });
            $items = $items->merge($keluar);
        }

        return $items->sortByDesc('tanggal')->values();
    }

    /**
     * Print Comprehensive Report (Rekap + Tren + Distribusi + Demografi + Nominatif).
     */
    public function printKomprehensif(Request $request)
    {
        try {
            $user = Auth::user();
            // Default to current year if no input, or use date range if provided
            $year = $request->input('year', date('Y'));
            $month = $request->input('month'); // Optional

            if ($month) {
                $start_date = Carbon::create($year, $month, 1)->startOfMonth()->format('Y-m-d');
                $end_date = Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');
            } else {
                $start_date = Carbon::create($year, 1, 1)->startOfYear()->format('Y-m-d');
                $end_date = Carbon::create($year, 12, 31)->endOfYear()->format('Y-m-d');
            }

            // 1. Rekap Data
            $rekapData = $this->getRekapData($start_date, $end_date, $user);

            // 2. Trend Data (Reuse existing logic)
            $trendData = $this->getTrendData($year, $month, $user);

            // 3. Distribusi Data (New)
            $distribusiData = $this->getDistribusiData($start_date, $end_date, $user);

            // 4. Demografi Data (New)
            $demografiData = $this->getDemografiData($start_date, $end_date, $user);

            // 5. Nominatif Data (Reuse existing logic)
            $nominatifMasuk = $this->getNominatifData($start_date, $end_date, 'masuk', $user);
            $nominatifKeluar = $this->getNominatifData($start_date, $end_date, 'keluar', $user);

            // --- Chart Generation ---
            // Chart generation removed as per request
            $chartImage = null;

            // Penandatangan (Optional, take first available or specific logic)
            $penandatangan = null;

            // Browser Print Version
            return view('laporan.cetak_komprehensif', compact(
                'user',
                'year',
                'month',
                'start_date',
                'end_date',
                'rekapData',
                'trendData',
                'distribusiData',
                'demografiData',
                'nominatifMasuk',
                'nominatifKeluar',
                'penandatangan',
                'chartImage'
            ));

            /*
            // MPDF / DomPDF Version (Disabled per user request)
            $pdf = Pdf::loadView('laporan.cetak_komprehensif', compact(
                'user',
                'year',
                'month',
                'start_date',
                'end_date',
                'rekapData',
                'trendData',
                'distribusiData',
                'demografiData',
                'nominatifMasuk',
                'nominatifKeluar',
                'penandatangan',
                'chartImage'
            ))->setPaper('a4', 'landscape');

            return $pdf->stream('Laporan_Komprehensif_Mutasi.pdf');
            */
        } catch (\Exception $e) {
            \Log::error('Print comprehensive report error: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $payload = [
                'status' => 'error',
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Gagal membuat laporan komprehensif. Silakan coba lagi atau hubungi administrator.',
            ];

            if (config('app.debug')) {
                $payload['file'] = $e->getFile();
                $payload['line'] = $e->getLine();
                $payload['trace'] = $e->getTraceAsString();
            }

            return response()->json($payload, 500);
        }
    }

    private function getDistribusiData($start, $end, $user)
    {
        $role = $user->role;
        $limit = 10;

        // Top 10 Asal (Mutasi Masuk)
        $topAsalQuery = DB::table('mutasi_masuk_siswa')
            ->join('mutasi_masuk', 'mutasi_masuk_siswa.id_mutasi', '=', 'mutasi_masuk.id_mutasi')
            ->select('mutasi_masuk_siswa.nama_sekolah_asal', 'mutasi_masuk_siswa.npsn_sekolah_asal as npsn', DB::raw('count(*) as total'))
            ->whereBetween('mutasi_masuk.tanggal_pengajuan', [$start, $end])
            ->where('mutasi_masuk.status', 'Selesai')
            ->whereIn('mutasi_masuk.npsn_sekolah_tujuan', $this->eligibleBogorJuniorHighNpsns())
            ->groupBy('mutasi_masuk_siswa.nama_sekolah_asal', 'mutasi_masuk_siswa.npsn_sekolah_asal')
            ->orderByDesc('total')
            ->limit($limit);

        if (in_array($role, ['Sekolah', 'Operator'])) {
            $npsn = $user->sekolah->npsn ?? $user->npsn;
            $topAsalQuery->where('mutasi_masuk.npsn_sekolah_tujuan', $npsn);
        }
        $topAsal = $topAsalQuery->get();

        // Top 10 Tujuan (Mutasi Keluar)
        $topTujuanQuery = MutasiKeluar::select('nama_sekolah_tujuan', 'npsn_sekolah_tujuan as npsn', DB::raw('count(*) as total'))
            ->whereBetween('tanggal_pengajuan', [$start, $end])
            ->where('status', 'Selesai')
            ->whereIn('npsn_sekolah_asal', $this->eligibleBogorJuniorHighNpsns())
            ->groupBy('nama_sekolah_tujuan', 'npsn_sekolah_tujuan')
            ->orderByDesc('total')
            ->limit($limit);

        if (in_array($role, ['Sekolah', 'Operator'])) {
            $npsn = $user->sekolah->npsn ?? $user->npsn;
            $topTujuanQuery->where('npsn_sekolah_asal', $npsn);
        }
        $topTujuan = $topTujuanQuery->get();

        return [
            'top_asal' => $topAsal,
            'top_tujuan' => $topTujuan,
        ];
    }

    private function getDemografiData($start, $end, $user)
    {
        $role = $user->role;

        // --- Mutasi Masuk Stats ---
        $masukQuery = DB::table('mutasi_masuk_siswa')
            ->join('mutasi_masuk', 'mutasi_masuk_siswa.id_mutasi', '=', 'mutasi_masuk.id_mutasi')
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN mutasi_masuk_siswa.jenis_kelamin = "L" THEN 1 ELSE 0 END) as laki,
                SUM(CASE WHEN mutasi_masuk_siswa.jenis_kelamin = "P" THEN 1 ELSE 0 END) as perempuan,
                SUM(CASE WHEN mutasi_masuk_siswa.kelas IN ("7", "VII") THEN 1 ELSE 0 END) as k7,
                SUM(CASE WHEN mutasi_masuk_siswa.kelas IN ("8", "VIII") THEN 1 ELSE 0 END) as k8,
                SUM(CASE WHEN mutasi_masuk_siswa.kelas IN ("9", "IX") THEN 1 ELSE 0 END) as k9
            ')
            ->whereBetween('mutasi_masuk.tanggal_pengajuan', [$start, $end])
            ->where('mutasi_masuk.status', 'Selesai')
            ->whereIn('mutasi_masuk.npsn_sekolah_tujuan', $this->eligibleBogorJuniorHighNpsns());

        if (in_array($role, ['Sekolah', 'Operator'])) {
            $npsn = $user->sekolah->npsn ?? $user->npsn;
            $masukQuery->where('mutasi_masuk.npsn_sekolah_tujuan', $npsn);
        }
        $masukStats = $masukQuery->first();

        // --- Mutasi Keluar Stats ---
        $keluarQuery = MutasiKeluar::selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN jenis_kelamin = "L" THEN 1 ELSE 0 END) as laki,
                SUM(CASE WHEN jenis_kelamin = "P" THEN 1 ELSE 0 END) as perempuan,
                SUM(CASE WHEN kelas IN ("7", "VII") THEN 1 ELSE 0 END) as k7,
                SUM(CASE WHEN kelas IN ("8", "VIII") THEN 1 ELSE 0 END) as k8,
                SUM(CASE WHEN kelas IN ("9", "IX") THEN 1 ELSE 0 END) as k9
            ')
            ->whereBetween('tanggal_pengajuan', [$start, $end])
            ->where('status', 'Selesai')
            ->whereIn('npsn_sekolah_asal', $this->eligibleBogorJuniorHighNpsns());

        if (in_array($role, ['Sekolah', 'Operator'])) {
            $npsn = $user->sekolah->npsn ?? $user->npsn;
            $keluarQuery->where('npsn_sekolah_asal', $npsn);
        }
        $keluarStats = $keluarQuery->first();

        return [
            'masuk' => $masukStats,
            'keluar' => $keluarStats,
        ];
    }

    private function eligibleBogorJuniorHighNpsns()
    {
        return SatuanPendidikan::eligibleBogorJuniorHigh()->select('npsn');
    }
}
