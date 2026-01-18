<?php

namespace App\Http\Controllers;

use App\Models\MutasiKeluar;
use App\Models\MutasiMasuk;
use App\Models\Penandatangan;
use App\Models\SatuanPendidikan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

        $filename = 'Rekapitulasi_Mutasi_' . $start_date . '_' . $end_date . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data, $user) {
            $file = fopen('php://output', 'w');

            // Add BOM for Excel UTF-8 compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            if ($user->role === 'Sekolah') {
                fputcsv($file, ['No', 'Periode', 'Masuk VII', 'Masuk VIII', 'Masuk IX', 'Total Masuk', 'Keluar VII', 'Keluar VIII', 'Keluar IX', 'Total Keluar']);
            } else {
                fputcsv($file, ['No', 'Nama Sekolah', 'Masuk VII', 'Masuk VIII', 'Masuk IX', 'Total Masuk', 'Keluar VII', 'Keluar VIII', 'Keluar IX', 'Total Keluar', 'Jumlah']);
            }

            // Data rows
            foreach ($data as $index => $item) {
                if ($user->role === 'Sekolah') {
                    fputcsv($file, [
                        $index + 1,
                        $item['periode'],
                        $item['masuk_vii'],
                        $item['masuk_viii'],
                        $item['masuk_ix'],
                        $item['masuk'],
                        $item['keluar_vii'],
                        $item['keluar_viii'],
                        $item['keluar_ix'],
                        $item['keluar'],
                    ]);
                } else {
                    fputcsv($file, [
                        $index + 1,
                        $item['nama_sekolah'],
                        $item['masuk_vii'],
                        $item['masuk_viii'],
                        $item['masuk_ix'],
                        $item['masuk'],
                        $item['keluar_vii'],
                        $item['keluar_viii'],
                        $item['keluar_ix'],
                        $item['keluar'],
                        $item['netto'],
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Helper to fetch Rekap data with Kelas breakdown.
     * OPTIMIZED: Uses aggregate queries instead of N+1 pattern
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
                    SUM(CASE WHEN mutasi_masuk_siswa.kelas IN ("7", "VII") THEN 1 ELSE 0 END) as masuk_vii,
                    SUM(CASE WHEN mutasi_masuk_siswa.kelas IN ("8", "VIII") THEN 1 ELSE 0 END) as masuk_viii,
                    SUM(CASE WHEN mutasi_masuk_siswa.kelas IN ("9", "IX") THEN 1 ELSE 0 END) as masuk_ix
                ')
                ->groupBy('mutasi_masuk.npsn_sekolah_tujuan')
                ->get()
                ->keyBy('npsn');

            // OPTIMIZED: Single aggregate query for Mutasi Keluar per school
            $keluarStats = MutasiKeluar::whereBetween('tanggal_pengajuan', [$start_date, $end_date])
                ->where('status', 'Selesai')
                ->selectRaw('
                    npsn_sekolah_asal as npsn,
                    SUM(CASE WHEN kelas IN ("7", "VII") THEN 1 ELSE 0 END) as keluar_vii,
                    SUM(CASE WHEN kelas IN ("8", "VIII") THEN 1 ELSE 0 END) as keluar_viii,
                    SUM(CASE WHEN kelas IN ("9", "IX") THEN 1 ELSE 0 END) as keluar_ix
                ')
                ->groupBy('npsn_sekolah_asal')
                ->get()
                ->keyBy('npsn');

            // Get schools and merge pre-aggregated data
            $schools = SatuanPendidikan::local()->orderBy('nama')->get();

            foreach ($schools as $school) {
                $masuk = $masukStats->get($school->npsn);
                $keluar = $keluarStats->get($school->npsn);

                $masukVII = $masuk ? (int) $masuk->masuk_vii : 0;
                $masukVIII = $masuk ? (int) $masuk->masuk_viii : 0;
                $masukIX = $masuk ? (int) $masuk->masuk_ix : 0;
                $masukTotal = $masukVII + $masukVIII + $masukIX;

                $keluarVII = $keluar ? (int) $keluar->keluar_vii : 0;
                $keluarVIII = $keluar ? (int) $keluar->keluar_viii : 0;
                $keluarIX = $keluar ? (int) $keluar->keluar_ix : 0;
                $keluarTotal = $keluarVII + $keluarVIII + $keluarIX;

                // Data Awal (From DB Profile)
                $awalVII = (int) $school->jumlah_siswa_awal_7;
                $awalVIII = (int) $school->jumlah_siswa_awal_8;
                $awalIX = (int) $school->jumlah_siswa_awal_9;
                $awalTotal = $awalVII + $awalVIII + $awalIX;

                // Data Akhir (Awal + Masuk - Keluar)
                $akhirVII = $awalVII + $masukVII - $keluarVII;
                $akhirVIII = $awalVIII + $masukVIII - $keluarVIII;
                $akhirIX = $awalIX + $masukIX - $keluarIX;
                $akhirTotal = $awalTotal + $masukTotal - $keluarTotal;

                if ($masukTotal > 0 || $keluarTotal > 0 || $awalTotal > 0) {
                    $data[] = [
                        'nama_sekolah' => $school->nama,
                        // Awal
                        'awal_vii' => $awalVII,
                        'awal_viii' => $awalVIII,
                        'awal_ix' => $awalIX,
                        'awal' => $awalTotal,
                        // Masuk
                        'masuk' => $masukTotal,
                        'masuk_vii' => $masukVII,
                        'masuk_viii' => $masukVIII,
                        'masuk_ix' => $masukIX,
                        // Keluar
                        'keluar' => $keluarTotal,
                        'keluar_vii' => $keluarVII,
                        'keluar_viii' => $keluarVIII,
                        'keluar_ix' => $keluarIX,
                        // Akhir
                        'akhir_vii' => $akhirVII,
                        'akhir_viii' => $akhirVIII,
                        'akhir_ix' => $akhirIX,
                        'akhir' => $akhirTotal,
                        // Netto
                        'netto' => $masukTotal - $keluarTotal
                    ];
                }
            }
        } elseif (in_array($role, ['Sekolah', 'Operator'])) {
            // For Sekolah: Group by Month with Kelas breakdown
            $npsn = $user->sekolah->npsn ?? $user->npsn;
            $school = $user->sekolah; // Get school profile data
            $period = \Carbon\CarbonPeriod::create($start_date, '1 month', $end_date);

            foreach ($period as $dt) {
                $monthStart = $dt->copy()->startOfMonth()->format('Y-m-d');
                $monthEnd = $dt->copy()->endOfMonth()->format('Y-m-d');

                if ($monthStart < $start_date)
                    $monthStart = $start_date;
                if ($monthEnd > $end_date)
                    $monthEnd = $end_date;
                if ($monthStart > $monthEnd)
                    continue;

                // Mutasi Masuk per kelas
                $masukQuery = DB::table('mutasi_masuk')
                    ->join('mutasi_masuk_siswa', 'mutasi_masuk.id_mutasi', '=', 'mutasi_masuk_siswa.id_mutasi')
                    ->where('mutasi_masuk.npsn_sekolah_tujuan', $npsn)
                    ->whereBetween('mutasi_masuk.tanggal_pengajuan', [$monthStart, $monthEnd])
                    ->where('mutasi_masuk.status', 'Selesai');

                $masukVII = (clone $masukQuery)->whereIn('mutasi_masuk_siswa.kelas', ['7', 'VII'])->count();
                $masukVIII = (clone $masukQuery)->whereIn('mutasi_masuk_siswa.kelas', ['8', 'VIII'])->count();
                $masukIX = (clone $masukQuery)->whereIn('mutasi_masuk_siswa.kelas', ['9', 'IX'])->count();
                $masukTotal = $masukVII + $masukVIII + $masukIX;

                // Mutasi Keluar per kelas
                $keluarQuery = MutasiKeluar::where('npsn_sekolah_asal', $npsn)
                    ->whereBetween('tanggal_pengajuan', [$monthStart, $monthEnd])
                    ->where('status', 'Selesai');

                $keluarVII = (clone $keluarQuery)->whereIn('kelas', ['7', 'VII'])->count();
                $keluarVIII = (clone $keluarQuery)->whereIn('kelas', ['8', 'VIII'])->count();
                $keluarIX = (clone $keluarQuery)->whereIn('kelas', ['9', 'IX'])->count();
                $keluarTotal = $keluarVII + $keluarVIII + $keluarIX;

                // Data Awal (From DB Profile - Static)
                $awalVII = (int) ($school->jumlah_siswa_awal_7 ?? 0);
                $awalVIII = (int) ($school->jumlah_siswa_awal_8 ?? 0);
                $awalIX = (int) ($school->jumlah_siswa_awal_9 ?? 0);
                $awalTotal = $awalVII + $awalVIII + $awalIX;

                // Data Akhir (Awal + Masuk - Keluar) - simplified per period row
                $akhirVII = $awalVII + $masukVII - $keluarVII;
                $akhirVIII = $awalVIII + $masukVIII - $keluarVIII;
                $akhirIX = $awalIX + $masukIX - $keluarIX;
                $akhirTotal = $awalTotal + $masukTotal - $keluarTotal;

                $data[] = [
                    'periode' => $dt->translatedFormat('F Y'),
                    // Awal
                    'awal_vii' => $awalVII,
                    'awal_viii' => $awalVIII,
                    'awal_ix' => $awalIX,
                    'awal' => $awalTotal,
                    // Masuk
                    'masuk' => $masukTotal,
                    'masuk_vii' => $masukVII,
                    'masuk_viii' => $masukVIII,
                    'masuk_ix' => $masukIX,
                    // Keluar
                    'keluar' => $keluarTotal,
                    'keluar_vii' => $keluarVII,
                    'keluar_viii' => $keluarVIII,
                    'keluar_ix' => $keluarIX,
                    // Akhir
                    'akhir_vii' => $akhirVII,
                    'akhir_viii' => $akhirVIII,
                    'akhir_ix' => $akhirIX,
                    'akhir' => $akhirTotal,
                    // Netto
                    'netto' => $masukTotal - $keluarTotal
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
     * Helper to fetch Trend data.
     */
    private function getTrendData($year, $month, $user)
    {
        $role = $user->role;

        // Determine Mode: Daily (if month selected) or Monthly (if year only)
        if ($month) {
            $daysInMonth = \Carbon\Carbon::create($year, $month)->daysInMonth;
            $range = range(1, $daysInMonth);
            $groupBy = 'day';
            $selectUnit = "DAY(mutasi_masuk.tanggal_pengajuan) as unit";
            $selectUnitKeluar = "DAY(tanggal_pengajuan) as unit";
        } else {
            $range = range(1, 12);
            $groupBy = 'month';
            $selectUnit = "MONTH(mutasi_masuk.tanggal_pengajuan) as unit";
            $selectUnitKeluar = "MONTH(tanggal_pengajuan) as unit";
        }

        // --- MUTASI MASUK ---
        $masukQuery = DB::table('mutasi_masuk')
            ->join('mutasi_masuk_siswa', 'mutasi_masuk.id_mutasi', '=', 'mutasi_masuk_siswa.id_mutasi')
            ->selectRaw("$selectUnit,
                SUM(CASE WHEN mutasi_masuk_siswa.asal_luar_negeri = 1 THEN 1 ELSE 0 END) as luar_negeri,
                SUM(CASE WHEN mutasi_masuk_siswa.asal_luar_negeri = 0 AND mutasi_masuk_siswa.kabupaten_kota_asal LIKE '%KOTA BOGOR%' THEN 1 ELSE 0 END) as dalam_kota,
                SUM(CASE WHEN mutasi_masuk_siswa.asal_luar_negeri = 0 AND (mutasi_masuk_siswa.kabupaten_kota_asal NOT LIKE '%KOTA BOGOR%' OR mutasi_masuk_siswa.kabupaten_kota_asal IS NULL) THEN 1 ELSE 0 END) as luar_kota")
            ->whereYear('mutasi_masuk.tanggal_pengajuan', $year)
            ->where('mutasi_masuk.status', 'Selesai');

        if ($month) {
            $masukQuery->whereMonth('mutasi_masuk.tanggal_pengajuan', $month);
        }

        if (in_array($role, ['Sekolah', 'Operator'])) {
            $npsn = $user->sekolah->npsn ?? $user->npsn;
            $masukQuery->where('mutasi_masuk.npsn_sekolah_tujuan', $npsn);
        }

        $masukResults = $masukQuery->groupBy('unit')->get()->keyBy('unit');

        // --- MUTASI KELUAR ---
        $keluarQuery = MutasiKeluar::selectRaw("$selectUnitKeluar,
                SUM(CASE WHEN provinsi_tujuan = 'LUAR NEGERI' THEN 1 ELSE 0 END) as luar_negeri,
                SUM(CASE WHEN provinsi_tujuan != 'LUAR NEGERI' AND kabupaten_kota_tujuan LIKE '%KOTA BOGOR%' THEN 1 ELSE 0 END) as dalam_kota,
                SUM(CASE WHEN provinsi_tujuan != 'LUAR NEGERI' AND (kabupaten_kota_tujuan NOT LIKE '%KOTA BOGOR%' OR kabupaten_kota_tujuan IS NULL) THEN 1 ELSE 0 END) as luar_kota")
            ->whereYear('tanggal_pengajuan', $year)
            ->where('status', 'Selesai');

        if ($month) {
            $keluarQuery->whereMonth('tanggal_pengajuan', $month);
        }

        if (in_array($role, ['Sekolah', 'Operator'])) {
            $npsn = $user->sekolah->npsn ?? $user->npsn;
            $keluarQuery->where('npsn_sekolah_asal', $npsn);
        }

        $keluarResults = $keluarQuery->groupBy('unit')->get()->keyBy('unit');

        // Format data for Chart
        $data = [
            'labels' => [],
            'masuk' => ['dalam' => [], 'luar_kota' => [], 'luar_negeri' => []],
            'keluar' => ['dalam' => [], 'luar_kota' => [], 'luar_negeri' => []]
        ];

        foreach ($range as $unit) {
            // Label Generation
            if ($month) {
                // Daily: "1", "2"... or "1 Jan"
                $data['labels'][] = $unit;
            } else {
                // Monthly: "Januari"
                $data['labels'][] = \Carbon\Carbon::create($year, $unit, 1)->translatedFormat('F');
            }

            // Masuk
            $mItem = $masukResults->get($unit);
            $data['masuk']['dalam'][] = $mItem ? (int) $mItem->dalam_kota : 0;
            $data['masuk']['luar_kota'][] = $mItem ? (int) $mItem->luar_kota : 0;
            $data['masuk']['luar_negeri'][] = $mItem ? (int) $mItem->luar_negeri : 0;

            // Keluar
            $kItem = $keluarResults->get($unit);
            $data['keluar']['dalam'][] = $kItem ? (int) $kItem->dalam_kota : 0;
            $data['keluar']['luar_kota'][] = $kItem ? (int) $kItem->luar_kota : 0;
            $data['keluar']['luar_negeri'][] = $kItem ? (int) $kItem->luar_negeri : 0;
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
        $filename = 'Daftar_Nominatif_' . $type . '_' . $start_date . '_' . $end_date . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM

            // Header
            fputcsv($file, ['No', 'Tanggal', 'Jenis', 'Nama Siswa', 'NISN', 'Kelas', 'Asal Sekolah', 'Tujuan Sekolah', 'Status']);

            foreach ($data as $index => $item) {
                fputcsv($file, [
                    $index + 1,
                    $item->tanggal,
                    strtoupper($item->jenis),
                    $item->nama_siswa,
                    $item->nisn,
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
                    'mutasi_masuk_siswa.kelas',
                    'mutasi_masuk_siswa.nama_sekolah_asal as asal',
                    'mutasi_masuk.nama_sekolah_tujuan as tujuan',
                    'mutasi_masuk.status',
                    'mutasi_masuk.npsn_sekolah_tujuan'
                )
                ->whereBetween('mutasi_masuk.tanggal_pengajuan', [$start, $end]);

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
                'kelas',
                'nama_sekolah_asal as asal',
                'nama_sekolah_tujuan as tujuan',
                'status',
                'npsn_sekolah_asal'
            )
                ->whereBetween('tanggal_pengajuan', [$start, $end]);

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
                $start_date = \Carbon\Carbon::create($year, $month, 1)->startOfMonth()->format('Y-m-d');
                $end_date = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');
            } else {
                $start_date = \Carbon\Carbon::create($year, 1, 1)->startOfYear()->format('Y-m-d');
                $end_date = \Carbon\Carbon::create($year, 12, 31)->endOfYear()->format('Y-m-d');
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
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    private function getDistribusiData($start, $end, $user)
    {
        $role = $user->role;
        $limit = 10;

        // Top 10 Asal (Mutasi Masuk)
        $topAsalQuery = DB::table('mutasi_masuk_siswa')
            ->join('mutasi_masuk', 'mutasi_masuk_siswa.id_mutasi', '=', 'mutasi_masuk.id_mutasi')
            ->select('mutasi_masuk_siswa.nama_sekolah_asal', DB::raw('count(*) as total'))
            ->whereBetween('mutasi_masuk.tanggal_pengajuan', [$start, $end])
            ->where('mutasi_masuk.status', 'Selesai')
            ->groupBy('mutasi_masuk_siswa.nama_sekolah_asal')
            ->orderByDesc('total')
            ->limit($limit);

        if (in_array($role, ['Sekolah', 'Operator'])) {
            $npsn = $user->sekolah->npsn ?? $user->npsn;
            $topAsalQuery->where('mutasi_masuk.npsn_sekolah_tujuan', $npsn);
        }
        $topAsal = $topAsalQuery->get();

        // Top 10 Tujuan (Mutasi Keluar)
        $topTujuanQuery = MutasiKeluar::select('nama_sekolah_tujuan', DB::raw('count(*) as total'))
            ->whereBetween('tanggal_pengajuan', [$start, $end])
            ->where('status', 'Selesai')
            ->groupBy('nama_sekolah_tujuan')
            ->orderByDesc('total')
            ->limit($limit);

        if (in_array($role, ['Sekolah', 'Operator'])) {
            $npsn = $user->sekolah->npsn ?? $user->npsn;
            $topTujuanQuery->where('npsn_sekolah_asal', $npsn);
        }
        $topTujuan = $topTujuanQuery->get();

        return [
            'top_asal' => $topAsal,
            'top_tujuan' => $topTujuan
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
            ->where('mutasi_masuk.status', 'Selesai');

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
            ->where('status', 'Selesai');

        if (in_array($role, ['Sekolah', 'Operator'])) {
            $npsn = $user->sekolah->npsn ?? $user->npsn;
            $keluarQuery->where('npsn_sekolah_asal', $npsn);
        }
        $keluarStats = $keluarQuery->first();

        return [
            'masuk' => $masukStats,
            'keluar' => $keluarStats
        ];
    }
}