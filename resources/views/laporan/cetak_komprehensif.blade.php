<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Komprehensif Mutasi Siswa</title>
    <style>
        @media print {
            @page { size: A4 landscape; margin: 10mm; }
            body { margin: 0; -webkit-print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
        body { font-family: Arial, sans-serif; font-size: 10pt; background: #fff; margin: 0 auto; max-width: 297mm; padding: 10mm; }
        
        /* Header Kop Surat (Exactly as requested) */
        .kop-surat {
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
            text-align: center;
            position: relative;
            min-height: 85px; /* Ensure height covers logo */
        }
        .logo-kop {
            width: 70px;
            position: absolute;
            left: 10px;
            top: 0;
        }
        .kop-text h1 { font-size: 14pt; margin: 0; font-weight: bold; text-transform: uppercase; }
        .kop-text h2 { font-size: 16pt; margin: 2px 0; font-weight: bold; text-transform: uppercase; }
        .kop-text p { font-size: 9pt; margin: 0; }

        /* Titles */
        .report-title { text-align: center; font-weight: bold; font-size: 12pt; margin-bottom: 5px; text-transform: uppercase; }
        .report-subtitle { text-align: center; font-size: 10pt; margin-bottom: 20px; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; } /* Keep rows intact, but allow table split */
        th, td { border: 1px solid #000; padding: 6px; font-size: 9pt; }
        th { background-color: #f0f0f0; font-weight: bold; text-align: center; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        
        /* Section Spacing */
        .section { margin-bottom: 20px; page-break-inside: auto; }
        h3 { page-break-after: avoid; }
        .page-break { page-break-after: always; }
        
        /* Two Column Layout for Distribution */
        .row { width: 100%; }
        .col-50 { width: 49%; display: inline-block; vertical-align: top; }
        .col-60 { width: 59%; display: inline-block; vertical-align: top; }
        .col-40 { width: 39%; display: inline-block; vertical-align: top; }
        .col-100 { width: 100%; display: block; }
        .gap { width: 1%; display: inline-block; }

        /* TTD */
        .ttd-container { width: 100%; text-align: right; margin-top: 30px; }
        .ttd-box { display: inline-block; text-align: center; width: 250px; }
    </style>
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 800);
        }
    </script>
</head>
<body>
    
    <div class="no-print" style="margin-bottom: 20px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #0284c7; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
            Cetak Laporan
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #64748b; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
            Tutup
        </button>
    </div>

    {{-- HEADER (Page 1 Only) --}}
    <div class="kop-surat">
        @if(in_array($user->role, ['Dinas', 'SuperAdmin']))
            <img src="{{ asset('images/logo_Kota_Bogor.png') }}" class="logo-kop">
            <div class="kop-text">
                <h1>PEMERINTAH KOTA BOGOR</h1>
                <h2>DINAS PENDIDIKAN</h2>
                <p>Jl. Padjajaran No. 125, Bantarjati, Bogor Utara, Kota Bogor, Jawa Barat 16153</p>
                <p>Telp. 0251-8341101 Faksimile 0251-8341101 www.disdik.kotabogor.go.id</p>
            </div>
        @else
            {{-- Check for Custom Kop Surat --}}
            @if(!empty($user->sekolah->kop_surat_path))
                {{-- Use Storage URL for custom image --}}
                <img src="{{ \App\Services\FileUploadService::getStorageUrl($user->sekolah->kop_surat_path) }}" 
                     style="width: 100%; max-height: 150px; object-fit: contain;"
                     onerror="this.style.display='none'">
                {{-- Hide bottom border for custom kop as the image usually contains it --}}
                <style>.kop-surat { border-bottom: none !important; }</style>
            @else
                {{-- Fallback to generated header --}}
                @if(($user->sekolah->status_sp ?? '') === 'Swasta')
                    <!-- KOP SWASTA: Tanpa Logo Pemkot, Hanya Nama Sekolah -->
                    <div class="kop-text" style="padding-top: 10px;">
                        <h1 style="font-size: 16pt; margin-bottom: 5px;">{{ $user->sekolah->nama ?? 'SEKOLAH' }}</h1>
                        <p>{{ $user->sekolah->alamat_lengkap ?? ($user->sekolah->alamat_jalan ?? 'Alamat Sekolah') }}</p>
                    </div>
                @else
                    <!-- KOP NEGERI: Standar Dinas -->
                    <img src="{{ asset('images/logo_Kota_Bogor.png') }}" class="logo-kop">
                    <div class="kop-text">
                        <h1>PEMERINTAH KOTA BOGOR</h1>
                        <h2>DINAS PENDIDIKAN</h2>
                        <h2 style="font-size: 14pt; margin-top: 5px;">{{ $user->sekolah->nama ?? 'SEKOLAH' }}</h2>
                        <p>{{ $user->sekolah->alamat_lengkap ?? ($user->sekolah->alamat_jalan ?? 'Alamat Sekolah') }}</p>
                    </div>
                @endif
            @endif
        @endif
    </div>

    <div class="report-title">LAPORAN REKAPITULASI MUTASI SISWA</div>
    <div class="report-subtitle">
        Periode: {{ \Carbon\Carbon::parse($start_date)->translatedFormat('d F Y') }} s.d. {{ \Carbon\Carbon::parse($end_date)->translatedFormat('d F Y') }}
    </div>

    {{-- 1. REKAPITULASI --}}
    <div class="section" style="margin-bottom: 30px;">
        <h3>A. Rekapitulasi Data</h3>
        <table style="font-size: 8pt; width: 100%;">
            <thead>
                <tr>
                    <th rowspan="2" width="20">No</th>
                    <th rowspan="2" width="130">{{ in_array($user->role, ['Dinas', 'SuperAdmin']) ? 'Nama Sekolah' : 'Bulan' }}</th>
                    <th colspan="4" style="background-color: #cbd5e1;">Data Siswa Awal</th>
                    <th colspan="4" style="background-color: #e0f2fe;">Mutasi Masuk</th>
                    <th colspan="4" style="background-color: #fff7ed;">Mutasi Keluar</th>
                    <th colspan="4" style="background-color: #dcfce7;">Data Siswa Akhir</th>
                </tr>
                <tr>
                    {{-- Awal --}}
                    <th style="background-color: #cbd5e1;">7</th> <th style="background-color: #cbd5e1;">8</th> <th style="background-color: #cbd5e1;">9</th> <th style="background-color: #94a3b8;">Total</th>
                    {{-- Masuk --}}
                    <th style="background-color: #e0f2fe;">7</th> <th style="background-color: #e0f2fe;">8</th> <th style="background-color: #e0f2fe;">9</th> <th style="background-color: #bae6fd;">Total</th>
                    {{-- Keluar --}}
                    <th style="background-color: #fff7ed;">7</th> <th style="background-color: #fff7ed;">8</th> <th style="background-color: #fff7ed;">9</th> <th style="background-color: #fed7aa;">Total</th>
                    {{-- Akhir --}}
                    <th style="background-color: #dcfce7;">7</th> <th style="background-color: #dcfce7;">8</th> <th style="background-color: #dcfce7;">9</th> <th style="background-color: #86efac;">Total</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $tAwalVII_L=0; $tAwalVII_P=0;
                    $tAwalVIII_L=0; $tAwalVIII_P=0;
                    $tAwalIX_L=0; $tAwalIX_P=0;
                    $tAwalTotal=0;

                    $tMasukVII_L=0; $tMasukVII_P=0; 
                    $tMasukVIII_L=0; $tMasukVIII_P=0;
                    $tMasukIX_L=0; $tMasukIX_P=0;
                    $tMasukTotal=0;

                    $tKeluarVII_L=0; $tKeluarVII_P=0;
                    $tKeluarVIII_L=0; $tKeluarVIII_P=0;
                    $tKeluarIX_L=0; $tKeluarIX_P=0;
                    $tKeluarTotal=0;

                    $tAkhirVII_L=0; $tAkhirVII_P=0;
                    $tAkhirVIII_L=0; $tAkhirVIII_P=0;
                    $tAkhirIX_L=0; $tAkhirIX_P=0;
                    $tAkhirTotal=0;
                @endphp
                @forelse($rekapData as $index => $row)
                    @php 
                        // Accumulate Totals - Awal
                        $tAwalVII_L += $row['awal_vii']['l'] ?? 0;
                        $tAwalVII_P += $row['awal_vii']['p'] ?? 0;
                        $tAwalVIII_L += $row['awal_viii']['l'] ?? 0;
                        $tAwalVIII_P += $row['awal_viii']['p'] ?? 0;
                        $tAwalIX_L += $row['awal_ix']['l'] ?? 0;
                        $tAwalIX_P += $row['awal_ix']['p'] ?? 0;
                        $tAwalTotal += $row['awal'];

                        // Accumulate Totals - Masuk
                        $tMasukVII_L += $row['masuk_vii']['l'] ?? 0;
                        $tMasukVII_P += $row['masuk_vii']['p'] ?? 0;
                        $tMasukVIII_L += $row['masuk_viii']['l'] ?? 0;
                        $tMasukVIII_P += $row['masuk_viii']['p'] ?? 0;
                        $tMasukIX_L += $row['masuk_ix']['l'] ?? 0;
                        $tMasukIX_P += $row['masuk_ix']['p'] ?? 0;
                        $tMasukTotal += $row['masuk'];

                        // Accumulate Totals - Keluar
                        $tKeluarVII_L += $row['keluar_vii']['l'] ?? 0;
                        $tKeluarVII_P += $row['keluar_vii']['p'] ?? 0;
                        $tKeluarVIII_L += $row['keluar_viii']['l'] ?? 0;
                        $tKeluarVIII_P += $row['keluar_viii']['p'] ?? 0;
                        $tKeluarIX_L += $row['keluar_ix']['l'] ?? 0;
                        $tKeluarIX_P += $row['keluar_ix']['p'] ?? 0;
                        $tKeluarTotal += $row['keluar'];

                        // Accumulate Totals - Akhir
                        $tAkhirVII_L += $row['akhir_vii']['l'] ?? 0;
                        $tAkhirVII_P += $row['akhir_vii']['p'] ?? 0;
                        $tAkhirVIII_L += $row['akhir_viii']['l'] ?? 0;
                        $tAkhirVIII_P += $row['akhir_viii']['p'] ?? 0;
                        $tAkhirIX_L += $row['akhir_ix']['l'] ?? 0;
                        $tAkhirIX_P += $row['akhir_ix']['p'] ?? 0;
                        $tAkhirTotal += $row['akhir'];
                    @endphp
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $row['nama_sekolah'] ?? $row['periode'] ?? '-' }}</td>
                        
                        {{-- Awal --}}
                        <td class="center bg-slate-50">
                            <div>{{ $row['awal_vii']['t'] ?? 0 }}</div>
                            <div style="font-size: 7pt; color: #666;">L:{{ $row['awal_vii']['l'] ?? 0 }} P:{{ $row['awal_vii']['p'] ?? 0 }}</div>
                        </td>
                        <td class="center bg-slate-50">
                            <div>{{ $row['awal_viii']['t'] ?? 0 }}</div>
                            <div style="font-size: 7pt; color: #666;">L:{{ $row['awal_viii']['l'] ?? 0 }} P:{{ $row['awal_viii']['p'] ?? 0 }}</div>
                        </td>
                        <td class="center bg-slate-50">
                            <div>{{ $row['awal_ix']['t'] ?? 0 }}</div>
                            <div style="font-size: 7pt; color: #666;">L:{{ $row['awal_ix']['l'] ?? 0 }} P:{{ $row['awal_ix']['p'] ?? 0 }}</div>
                        </td>
                        <td class="center bold bg-slate-200">{{ $row['awal'] ?? 0 }}</td>

                        {{-- Masuk --}}
                        <td class="center bg-blue-50">
                            <div>{{ $row['masuk_vii']['t'] }}</div>
                            <div style="font-size: 7pt; color: #666;">L:{{ $row['masuk_vii']['l'] }} P:{{ $row['masuk_vii']['p'] }}</div>
                        </td>
                        <td class="center bg-blue-50">
                            <div>{{ $row['masuk_viii']['t'] }}</div>
                            <div style="font-size: 7pt; color: #666;">L:{{ $row['masuk_viii']['l'] }} P:{{ $row['masuk_viii']['p'] }}</div>
                        </td>
                        <td class="center bg-blue-50">
                            <div>{{ $row['masuk_ix']['t'] }}</div>
                            <div style="font-size: 7pt; color: #666;">L:{{ $row['masuk_ix']['l'] }} P:{{ $row['masuk_ix']['p'] }}</div>
                        </td>
                        <td class="center bold bg-blue-100">{{ $row['masuk'] }}</td>
                        
                        {{-- Keluar --}}
                        <td class="center bg-orange-50">
                            <div>{{ $row['keluar_vii']['t'] }}</div>
                            <div style="font-size: 7pt; color: #666;">L:{{ $row['keluar_vii']['l'] }} P:{{ $row['keluar_vii']['p'] }}</div>
                        </td>
                        <td class="center bg-orange-50">
                            <div>{{ $row['keluar_viii']['t'] }}</div>
                            <div style="font-size: 7pt; color: #666;">L:{{ $row['keluar_viii']['l'] }} P:{{ $row['keluar_viii']['p'] }}</div>
                        </td>
                        <td class="center bg-orange-50">
                            <div>{{ $row['keluar_ix']['t'] }}</div>
                            <div style="font-size: 7pt; color: #666;">L:{{ $row['keluar_ix']['l'] }} P:{{ $row['keluar_ix']['p'] }}</div>
                        </td>
                        <td class="center bold bg-orange-100">{{ $row['keluar'] }}</td>

                        {{-- Akhir --}}
                        <td class="center bg-green-50">
                            <div>{{ $row['akhir_vii']['t'] ?? 0 }}</div>
                            <div style="font-size: 7pt; color: #666;">L:{{ $row['akhir_vii']['l'] ?? 0 }} P:{{ $row['akhir_vii']['p'] ?? 0 }}</div>
                        </td>
                        <td class="center bg-green-50">
                            <div>{{ $row['akhir_viii']['t'] ?? 0 }}</div>
                            <div style="font-size: 7pt; color: #666;">L:{{ $row['akhir_viii']['l'] ?? 0 }} P:{{ $row['akhir_viii']['p'] ?? 0 }}</div>
                        </td>
                        <td class="center bg-green-50">
                            <div>{{ $row['akhir_ix']['t'] ?? 0 }}</div>
                            <div style="font-size: 7pt; color: #666;">L:{{ $row['akhir_ix']['l'] ?? 0 }} P:{{ $row['akhir_ix']['p'] ?? 0 }}</div>
                        </td>
                        <td class="center bold bg-green-100">{{ $row['akhir'] ?? 0 }}</td>
                    </tr>
                @empty
                    <tr><td colspan="18" class="center">Tidak ada data rekapitulasi.</td></tr>
                @endforelse
                <tr style="background-color: #f3f4f6; font-weight: bold;">
                    <td colspan="2" class="center">TOTAL</td>
                    
                    {{-- Awal Totals --}}
                    <td class="center">
                        <div>{{ $tAwalVII_L + $tAwalVII_P }}</div>
                        <div style="font-size: 7pt; font-weight: normal;">L:{{ $tAwalVII_L }} P:{{ $tAwalVII_P }}</div>
                    </td>
                    <td class="center">
                        <div>{{ $tAwalVIII_L + $tAwalVIII_P }}</div>
                        <div style="font-size: 7pt; font-weight: normal;">L:{{ $tAwalVIII_L }} P:{{ $tAwalVIII_P }}</div>
                    </td>
                    <td class="center">
                        <div>{{ $tAwalIX_L + $tAwalIX_P }}</div>
                        <div style="font-size: 7pt; font-weight: normal;">L:{{ $tAwalIX_L }} P:{{ $tAwalIX_P }}</div>
                    </td>
                    <td class="center bg-slate-200">{{ $tAwalTotal }}</td>
                    
                    {{-- Masuk Totals --}}
                    <td class="center">
                        <div>{{ $tMasukVII_L + $tMasukVII_P }}</div>
                        <div style="font-size: 7pt; font-weight: normal;">L:{{ $tMasukVII_L }} P:{{ $tMasukVII_P }}</div>
                    </td>
                    <td class="center">
                        <div>{{ $tMasukVIII_L + $tMasukVIII_P }}</div>
                        <div style="font-size: 7pt; font-weight: normal;">L:{{ $tMasukVIII_L }} P:{{ $tMasukVIII_P }}</div>
                    </td>
                    <td class="center">
                        <div>{{ $tMasukIX_L + $tMasukIX_P }}</div>
                        <div style="font-size: 7pt; font-weight: normal;">L:{{ $tMasukIX_L }} P:{{ $tMasukIX_P }}</div>
                    </td>
                    <td class="center bg-blue-100">{{ $tMasukTotal }}</td>

                    {{-- Keluar Totals --}}
                    <td class="center">
                        <div>{{ $tKeluarVII_L + $tKeluarVII_P }}</div>
                        <div style="font-size: 7pt; font-weight: normal;">L:{{ $tKeluarVII_L }} P:{{ $tKeluarVII_P }}</div>
                    </td>
                    <td class="center">
                        <div>{{ $tKeluarVIII_L + $tKeluarVIII_P }}</div>
                        <div style="font-size: 7pt; font-weight: normal;">L:{{ $tKeluarVIII_L }} P:{{ $tKeluarVIII_P }}</div>
                    </td>
                    <td class="center">
                        <div>{{ $tKeluarIX_L + $tKeluarIX_P }}</div>
                        <div style="font-size: 7pt; font-weight: normal;">L:{{ $tKeluarIX_L }} P:{{ $tKeluarIX_P }}</div>
                    </td>
                    <td class="center bg-orange-100">{{ $tKeluarTotal }}</td>

                    {{-- Akhir Totals --}}
                    <td class="center">
                        <div>{{ $tAkhirVII_L + $tAkhirVII_P }}</div>
                        <div style="font-size: 7pt; font-weight: normal;">L:{{ $tAkhirVII_L }} P:{{ $tAkhirVII_P }}</div>
                    </td>
                    <td class="center">
                        <div>{{ $tAkhirVIII_L + $tAkhirVIII_P }}</div>
                        <div style="font-size: 7pt; font-weight: normal;">L:{{ $tAkhirVIII_L }} P:{{ $tAkhirVIII_P }}</div>
                    </td>
                    <td class="center">
                        <div>{{ $tAkhirIX_L + $tAkhirIX_P }}</div>
                        <div style="font-size: 7pt; font-weight: normal;">L:{{ $tAkhirIX_L }} P:{{ $tAkhirIX_P }}</div>
                    </td>
                    <td class="center bg-green-100">{{ $tAkhirTotal }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- 2. TREN MUTASI --}}
    <div class="section">
        <h3>B. Tren Mutasi</h3>
        <table style="font-size: 8pt; width: 100%;">
            <thead>
                <tr>
                    <th rowspan="2">Periode</th>
                    <th colspan="4" style="background-color: #e0f2fe;">Mutasi Masuk</th>
                    <th colspan="4" style="background-color: #fff7ed;">Mutasi Keluar</th>
                </tr>
                <tr>
                    <th style="background-color: #e0f2fe;">Dalam Kota</th>
                    <th style="background-color: #e0f2fe;">Luar Kota</th>
                    <th style="background-color: #e0f2fe;">Luar Negeri</th>
                    <th style="background-color: #bae6fd;">Total</th>
                    <th style="background-color: #fff7ed;">Dalam Kota</th>
                    <th style="background-color: #fff7ed;">Luar Kota</th>
                    <th style="background-color: #fff7ed;">Luar Negeri</th>
                    <th style="background-color: #fed7aa;">Total</th>
                </tr>
            </thead>
            <tbody>
                @php $hasTrendData = false; @endphp
                @foreach($trendData['labels'] as $idx => $label)
                    @php
                        $md = $trendData['masuk']['dalam'][$idx];
                        $mlk = $trendData['masuk']['luar_kota'][$idx];
                        $mln = $trendData['masuk']['luar_negeri'][$idx];
                        $tm = $md + $mlk + $mln;
                        
                        $kd = $trendData['keluar']['dalam'][$idx];
                        $klk = $trendData['keluar']['luar_kota'][$idx];
                        $kln = $trendData['keluar']['luar_negeri'][$idx];
                        $tk = $kd + $klk + $kln;
                    @endphp
                    
                    @if($tm > 0 || $tk > 0)
                        @php $hasTrendData = true; @endphp
                        <tr>
                            <td>{{ $month ? $label . ' ' . \Carbon\Carbon::createFromDate(null, $month)->translatedFormat('F') : $label }}</td>
                            <td class="center">{{ $md }}</td><td class="center">{{ $mlk }}</td><td class="center">{{ $mln }}</td>
                            <td class="center bold bg-blue-50">{{ $tm }}</td>
                            <td class="center">{{ $kd }}</td><td class="center">{{ $klk }}</td><td class="center">{{ $kln }}</td>
                            <td class="center bold bg-orange-50">{{ $tk }}</td>
                        </tr>
                    @endif
                @endforeach
                
                @if(!$hasTrendData)
                    <tr><td colspan="9" class="center">Tidak ada aktivitas mutasi pada periode ini.</td></tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="page-break"></div>

    {{-- 3. DISTRIBUSI WILAYAH --}}
    <div class="section">
        <h3>C. Distribusi Wilayah/Sekolah (Top 10)</h3>
        <div class="row">
            <div class="col-50">
                <h4>Top 10 Asal Sekolah (Mutasi Masuk)</h4>
                <table>
                    <thead><tr><th width="30">No</th><th>Nama Sekolah Asal</th><th width="70">NPSN</th><th width="50">Jumlah</th></tr></thead>
                    <tbody>
                        @forelse($distribusiData['top_asal'] as $ix => $row)
                            <tr><td class="center">{{$ix+1}}</td><td>{{ $row->nama_sekolah_asal }}</td><td class="center">{{ $row->npsn ?? '-' }}</td><td class="center bold">{{ $row->total }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="center">Data Kosong</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="gap"></div>
            <div class="col-50">
                <h4>Top 10 Tujuan Sekolah (Mutasi Keluar)</h4>
                <table>
                    <thead><tr><th width="30">No</th><th>Nama Sekolah Tujuan</th><th width="70">NPSN</th><th width="50">Jumlah</th></tr></thead>
                    <tbody>
                        @forelse($distribusiData['top_tujuan'] as $ix => $row)
                            <tr><td class="center">{{$ix+1}}</td><td>{{ $row->nama_sekolah_tujuan }}</td><td class="center">{{ $row->npsn ?? '-' }}</td><td class="center bold">{{ $row->total }}</td></tr>
                        @empty
                             <tr><td colspan="4" class="center">Data Kosong</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 4. DEMOGRAFI --}}
    <div class="section">
        <h3>D. Demografi Siswa (Gender & Kelas)</h3>
        <table>
             <thead>
                <tr>
                    <th rowspan="2">Jenis Mutasi</th>
                    <th colspan="3">Jenis Kelamin</th>
                    <th colspan="4">Jenjang Kelas</th>
                    <th rowspan="2">Total Siswa</th>
                </tr>
                <tr>
                    <th>Laki-laki</th><th>Perempuan</th><th>Ratio L:P</th>
                    <th>Kelas 7</th><th>Kelas 8</th><th>Kelas 9</th><th>Ratio Kelas</th>
                </tr>
            </thead>
            <tbody>
                @php 
                    $m = $demografiData['masuk']; 
                    $k = $demografiData['keluar'];
                @endphp
                <tr>
                    <td class="bold">Mutasi Masuk</td>
                    <td class="center">{{ $m->laki }}</td><td class="center">{{ $m->perempuan }}</td>
                    <td class="center">{{ $m->total > 0 ? round(($m->laki/$m->total)*100) . ':' . round(($m->perempuan/$m->total)*100) : '-' }}</td>
                    <td class="center">{{ $m->k7 }}</td><td class="center">{{ $m->k8 }}</td><td class="center">{{ $m->k9 }}</td>
                    <td class="center">
                        @if($m->total > 0)
                            7({{ round(($m->k7/$m->total)*100) }}%) : 8({{ round(($m->k8/$m->total)*100) }}%) : 9({{ round(($m->k9/$m->total)*100) }}%)
                        @else - @endif
                    </td>
                    <td class="center bold">{{ $m->total }}</td>
                </tr>
                 <tr>
                    <td class="bold">Mutasi Keluar</td>
                    <td class="center">{{ $k->laki }}</td><td class="center">{{ $k->perempuan }}</td>
                    <td class="center">{{ $k->total > 0 ? round(($k->laki/$k->total)*100) . ':' . round(($k->perempuan/$k->total)*100) : '-' }}</td>
                     <td class="center">{{ $k->k7 }}</td><td class="center">{{ $k->k8 }}</td><td class="center">{{ $k->k9 }}</td>
                     <td class="center">
                        @if($k->total > 0)
                            7({{ round(($k->k7/$k->total)*100) }}%) : 8({{ round(($k->k8/$k->total)*100) }}%) : 9({{ round(($k->k9/$k->total)*100) }}%)
                        @else - @endif
                    </td>
                    <td class="center bold">{{ $k->total }}</td>
                </tr>
            </tbody>
        </table>
        
        <div style="font-size: 8pt; color: #555; background-color: #f9f9f9; padding: 5px; border: 1px solid #ddd; margin-top: 5px;">
            <strong>Keterangan:</strong><br>
            <span style="display: inline-block; width: 80px;">- Ratio L:P</span> : Perbandingan persentase siswa Laki-laki dan Perempuan (Contoh: 71:29 artinya 71% Laki-laki, 29% Perempuan).<br>
            <span style="display: inline-block; width: 80px;">- Ratio Kelas</span> : Distribusi jumlah siswa per jenjang kelas beserta persentasenya.
        </div>
    </div>
    
    <div class="page-break"></div>

    {{-- 5. DAFTAR NOMINATIF --}}
    <div class="section">
        <h3>E. Daftar Nominatif Mutasi Masuk</h3>
        <table>
            <thead>
                <tr>
                    <th width="30">No</th>
                    <th width="80">Tanggal</th>
                    <th>Nama Siswa</th>
                    <th width="80">NISN</th>
                    <th>Sekolah Asal</th>
                    <th>Tujuan (Sekolah Ini)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($nominatifMasuk as $i => $item)
                    <tr>
                        <td class="center">{{ $i+1 }}</td>
                        <td class="center">{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/y') }}</td>
                        <td>{{ $item->nama_siswa }}</td>
                        <td class="center">{{ $item->nisn }}</td>
                        <td>{{ $item->asal }}</td>
                        <td>{{ $item->tujuan }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="center">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>F. Daftar Nominatif Mutasi Keluar</h3>
        <table>
            <thead>
                 <tr>
                    <th width="30">No</th>
                    <th width="80">Tanggal</th>
                    <th>Nama Siswa</th>
                    <th width="80">NISN</th>
                    <th>Asal (Sekolah Ini)</th>
                    <th>Sekolah Tujuan</th>
                </tr>
            </thead>
             <tbody>
                @forelse($nominatifKeluar as $i => $item)
                    <tr>
                        <td class="center">{{ $i+1 }}</td>
                        <td class="center">{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/y') }}</td>
                        <td>{{ $item->nama_siswa }}</td>
                        <td class="center">{{ $item->nisn }}</td>
                        <td>{{ $item->asal }}</td>
                        <td>{{ $item->tujuan }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="center">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- SIGNATURE --}}
    <div class="ttd-container">
        <div class="ttd-box">
            <p>Kota Bogor, {{ now()->translatedFormat('d F Y') }}</p>
            
            @if(!in_array($user->role, ['Dinas', 'SuperAdmin']) && !empty($user->sekolah->kepala_sekolah_nama))
                <p>Kepala Sekolah,</p>
                <p style="margin-bottom: 60px;"></p> {{-- Space for signature --}}
                <p style="text-decoration: underline; font-weight: bold;">{{ $user->sekolah->kepala_sekolah_nama }}</p>
                @if(!empty($user->sekolah->kepala_sekolah_nip))
                    <p>NIP. {{ $user->sekolah->kepala_sekolah_nip }}</p>
                @endif
            @else
                {{-- Fallback if no Principal data --}}
                <p>Waktu Cetak: {{ now()->translatedFormat('H:i') }} WIB</p>
            @endif
        </div>
    </div>

</body>
</html>