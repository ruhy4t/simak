<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Izin Mutasi Masuk</title>
    <style>
        /* --- RESET & GLOBAL --- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12pt;
            line-height: 1.3;
            color: #000;
            background-color: #eee; /* Grey background for screen to see "sheets" */
            padding: 20px 0;
        }

        /* --- PAGE CONTAINERS (Looking like Papers) --- */
        .page {
            background: white;
            position: relative; /* Context for absolute footer/qr */
            margin: 0 auto;
            margin-bottom: 30px; /* Space between sheets on screen */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3); /* Shadow for paper effect */
            page-break-after: always;
            overflow: hidden; /* Prevent spillover */
        }

        /* --- PAGE ORIENTATION RULES (Chrome/Edge/Modern) --- */
        @page {
            size: auto;
            margin: 0mm;
        }

        @page portrait {
            size: A4 portrait;
            margin: 0mm;
        }

        @page landscape {
            size: A4 landscape;
            margin: 0mm;
        }

        /* Portrait Settings */
        .page-portrait {
            width: 210mm;
            height: 297mm;
            padding: 1.5cm 2cm 3cm 2cm; /* Top R B L */
            page: portrait; /* Tell browser to use portrait sheet */
        }

        /* Landscape Settings */
        .page-landscape {
            width: 297mm;
            height: 210mm;
            padding: 2cm 2cm 3cm 2cm; /* Top R B L */
            page: landscape; /* Tell browser to use landscape sheet */
        }

        /* --- MEDIA PRINT --- */
        @media print {
            body {
                background: none;
                padding: 0;
            }

            .page {
                margin: 0;
                box-shadow: none;
                page-break-after: always;
            }

            /* Ensure widths match print expectation */
            .page-portrait { width: 210mm; }
            .page-landscape { width: 297mm; }
        }

        /* --- ABSOLUTE FOOTER & QR (Inside .page) --- */
        .page-footer {
            position: absolute;
            bottom: 1cm; /* Fixed from bottom of SHEET */
            left: 2cm;
            right: 2cm;
            height: 1.5cm;
            /* Ensure it spans correctly */
            width: auto; 
        }

        .footer-line {
            border-top: 1px solid #000;
            margin-bottom: 5px;
            width: 100%; /* Force full width of container */
        }

        .footer-text {
            text-align: center;
            font-size: 9pt;
        }

        .footer-text .quote {
            font-style: italic;
            display: block;
        }

        .footer-text .tagline {
            font-weight: bold;
            display: block;
        }

        .page-qr {
            position: absolute;
            bottom: 2.8cm; /* Raised from 2.3cm to give space */
            left: 2cm;
            width: 95px; /* Matched to PHP generation size */
            z-index: 10;
        }

        /* --- KOP SURAT --- */
        .kop-surat {
            display: flex;
            align-items: center;
            border-bottom: 3px solid #000;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }

        .logo-kop {
            width: 60px;
            height: 75px;
            margin-right: 15px;
        }

        .kop-text {
            flex: 1;
            text-align: center;
        }

        .kop-text h1 {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .kop-text h2 {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .kop-text p {
            font-size: 9pt;
        }

        /* --- CONTENT STYLES --- */
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .info-left table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-left td { vertical-align: top; padding: 1px 0; }
        .col-label { width: 90px; }
        .col-colon { width: 15px; text-align: center; }

        .info-right { text-align: right; }

        .recipient-section { margin-bottom: 30px; }
        .recipient-section p { margin-bottom: 2px; }
        .bold-city { font-weight: bold; margin-left: 60px; margin-top: 3px; }

        .content-section { text-align: justify; margin-bottom: 15px; }
        .content-section p { text-indent: 40px; margin-bottom: 5px; }

        .catatan-section { margin-bottom: 10px; }
        .catatan-section ol { margin-left: 20px; }
        .catatan-section li { margin-bottom: 2px; }

        .closing-text { text-indent: 40px; margin-bottom: 10px; }

        /* --- TTD --- */
        .ttd-container {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
        }
        .ttd-box { width: 250px; text-align: left; }
        .ttd-box table { width: 100%; }
        .spacer-ttd { height: 60px; }
        .officer-name { font-weight: bold; }

        /* --- LAMPIRAN TABLES --- */
        .lampiran-header { margin-bottom: 15px; }
        .lampiran-title { text-align: center; font-weight: bold; text-transform: uppercase; margin-bottom: 15px; }

        .table-siswa { width: 100%; border-collapse: collapse; font-size: 11pt; }
        .table-siswa th, .table-siswa td { border: 1px solid #000; padding: 5px; vertical-align: middle; }
        .table-siswa th { text-align: center; font-weight: bold; background: #fff; }
        .center { text-align: center; }

    </style>
</head>

<body>

    {{-- PHP DATA PREPARATION (ROBUST) --}}
    @php
        // Prepare Data
        $idMutasi = $mutasi->id_mutasi ?? '-';
        $tglUsulan = isset($mutasi->tanggal_pengajuan) ? \Carbon\Carbon::parse($mutasi->tanggal_pengajuan)->format('d/m/Y') : '-';
        $tglTtd = isset($mutasi->tanggal_surat_izin) ? \Carbon\Carbon::parse($mutasi->tanggal_surat_izin)->format('d/m/Y') : now()->format('d/m/Y');
        $namaPejabat = $penandatangan?->nama ?? '-';
        
        // SAFETY: Convert Siswa List to Array of Arrays to prevent "Call to member function on array" 
        // or "Cannot use object as array" errors.
        // SAFETY: Convert Siswa List to Array of Arrays.
        // PdfService returns Collection of stdClass. ->toArray() keeps items as objects!
        // We MUST cast each item to (array) manually to support $siswa['key'] syntax.
        $siswaListArr = [];
        if (isset($siswaList)) {
            foreach ($siswaList as $s) {
                $siswaListArr[] = (array) $s;
            }
        }
        
        $jumlahSiswa = count($siswaListArr);

        // Name Logic for QR
        $namaSiswaList = array_map(fn($s) => $s['nama_siswa'] ?? $s['nama_murid'] ?? '-', $siswaListArr);
        if (count($namaSiswaList) > 2) {
            $namaSiswaStr = implode(', ', array_slice($namaSiswaList, 0, 2)) . ", dkk ({$jumlahSiswa})";
        } else {
            $namaSiswaStr = implode(', ', $namaSiswaList);
        }

        // Entities Decode
        $decodedNamaSiswa = html_entity_decode($namaSiswaStr);
        $decodedNamaPejabat = html_entity_decode($namaPejabat);

        // QR String
        $qrData = "ID:{$idMutasi} | Siswa:{$decodedNamaSiswa} | Tgl:{$tglTtd} | TTD:{$decodedNamaPejabat}";
        $disdikLogo = null; // DISABLED: Logo removed per user request (stability fix)
        
        // Generate QR Once (Increased size for readability)
        $qrCodeHtml = \App\Helpers\QrCodeHelper::generateWithLogo($qrData, 95, $disdikLogo);
    @endphp

    {{-- ========================================================================================= --}}
    {{-- PAGE 1: SURAT UTAMA (PORTRAIT) --}}
    {{-- ========================================================================================= --}}
    <div class="page page-portrait">
        
        {{-- HEADER KOP --}}
        <div class="kop-surat">
            <img src="{{ asset('images/logo_Kota_Bogor.png') }}" alt="Logo" class="logo-kop">
            <div class="kop-text">
                <h1>PEMERINTAH KOTA BOGOR</h1>
                <h2>DINAS PENDIDIKAN</h2>
                <p>Jl. Padjajaran No. 125, Bantarjati, Bogor Utara, Kota Bogor, Jawa Barat 16153</p>
                <p>Telp. 0251-8341101 Faksimile 0251-8341101<br>www.disdik.kotabogor.go.id</p>
            </div>
        </div>

        {{-- INFO --}}
        <div class="info-section">
            <div class="info-left">
                <table>
                    <tr>
                        <td class="col-label">Nomor</td><td class="col-colon">:</td>
                        <td>{!! html_entity_decode($mutasi->nomor_surat_izin ?? '-') !!}</td>
                    </tr>
                    <tr>
                        <td class="col-label">Sifat</td><td class="col-colon">:</td>
                        <td>Biasa</td>
                    </tr>
                    <tr>
                        <td class="col-label">Lampiran</td><td class="col-colon">:</td>
                        <td>1 (satu) berkas</td>
                    </tr>
                    <tr>
                        <td class="col-label">Hal</td><td class="col-colon">:</td>
                        <td><strong>Izin Mutasi Siswa</strong></td>
                    </tr>
                </table>
            </div>
            <div class="info-right">
                Bogor, {{ isset($mutasi->tanggal_surat_izin) ? \Carbon\Carbon::parse($mutasi->tanggal_surat_izin)->translatedFormat('j F Y') : now()->translatedFormat('j F Y') }}
            </div>
        </div>

        {{-- RECIPIENT (SPLIT 50:50 - Content Left, Empty Right) --}}
        <div class="recipient-section" style="display: flex; flex-direction: column;">
            <div style="width: 50%;"> 
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 30px; vertical-align: top; padding:0;">Yth.</td>
                        <td style="vertical-align: top; padding:0;">
                            Kepala {!! html_entity_decode($mutasi->sekolah_tujuan ?? '-') !!}<br>
                            {!! html_entity_decode($mutasi->alamat_sekolah_tujuan ?? '-') !!}
                        </td>
                    </tr>
                </table>
                <div style="margin-top: 1.5em;">di Bogor</div>
            </div>
        </div>

        {{-- CONTENT --}}
        <div class="content-section">
            <p>
                Berdasarkan surat dari Kepala {!! html_entity_decode($mutasi->sekolah_tujuan ?? '-') !!} nomor :
                {!! html_entity_decode($mutasi->nomor_surat_permohonan ?? '-') !!} tanggal
                {{ isset($mutasi->tanggal_surat_permohonan) ? \Carbon\Carbon::parse($mutasi->tanggal_surat_permohonan)->translatedFormat('j F Y') : '-' }}
                perihal Permohonan Izin Mutasi Masuk, dengan ini memberikan izin untuk menerima siswa mutasi masuk
                sebagaimana terlampir.
            </p>
        </div>

        <div class="catatan-section">
            <p><strong>Dengan catatan :</strong></p>
            <ol>
                <li>proses perpindahan siswa berpedoman kepada ketentuan yang berlaku;</li>
                <li>Kuota siswa per rombel tidak melebihi ketentuan yang sudah ditetapkan;</li>
                <li>Satuan Pendidikan wajib memperbaharui data di Dapodik.</li>
            </ol>
        </div>

        <p class="closing-text">Demikian surat ini kami buat untuk dipergunakan sebagaimana mestinya.</p>

        {{-- TTD PEJABAT --}}
        <div class="ttd-container" style="margin-top: 30px;">
            <div class="ttd-box">
                <table>
                    <tr><td style="width: 30px;">a.n.</td><td>Kepala,</td></tr>
                    <tr><td></td><td>Sekretaris</td></tr>
                    <tr><td>u.b.</td><td>Kepala Bidang SMP</td></tr>
                    <tr><td colspan="2" class="spacer-ttd"></td></tr>
                    <tr><td></td><td class="officer-name">{!! html_entity_decode($penandatangan?->nama ?? '-') !!}</td></tr>
                    <tr><td></td><td>{!! html_entity_decode($penandatangan?->pangkat_golongan ?? '-') !!}</td></tr>
                    <tr><td></td><td>NIP. {!! html_entity_decode($penandatangan?->nip ?? '-') !!}</td></tr>
                </table>
            </div>
        </div>

        {{-- FOOTER & QR (ABSOLUTE PER PAGE) --}}
        <div class="page-qr">{!! $qrCodeHtml !!}</div>
        <div class="page-footer">
            <div class="footer-line"></div>
            <div class="footer-text">
                <span class="quote">"dinu kiwari ngancik nu bihari, seja ayeuna sampeureun jaga"</span>
                <span class="tagline">Bogor Beres, Bogor Cerdas</span>
            </div>
        </div>
    </div> 
    {{-- END PAGE 1 --}}


    {{-- ========================================================================================= --}}
    {{-- PAGE 2+: LAMPIRAN (LANDSCAPE) - MANUAL CHUNKING --}}
    {{-- ========================================================================================= --}}
    @if($jumlahSiswa > 0)
        @php
            // CHUNK SIZE: Reduced to 6 to prevent overlap with Footer/Signature due to larger font (11pt/12pt)
            $chunks = array_chunk($siswaListArr, 6);
        @endphp

        @foreach($chunks as $pageIndex => $chunk)
            <div class="page page-landscape">
                <div class="lampiran-header">
                    <p><strong>LAMPIRAN</strong></p>
                    <table style="width: auto;">
                        <tr>
                            <td style="width: 70px;">Nomor</td><td style="width: 15px;">:</td>
                            <td>{!! html_entity_decode($mutasi->nomor_surat_izin ?? '-') !!}</td>
                        </tr>
                        <tr>
                            <td>Tanggal</td><td>:</td>
                            <td>{{ isset($mutasi->tanggal_surat_izin) ? \Carbon\Carbon::parse($mutasi->tanggal_surat_izin)->translatedFormat('j F Y') : now()->translatedFormat('j F Y') }}</td>
                        </tr>
                    </table>
                </div>

                <div class="lampiran-title">
                    DAFTAR SISWA MUTASI MASUK<br>
                    KE {!! html_entity_decode($mutasi->sekolah_tujuan ?? '-') !!}
                </div>

                <table class="table-siswa">
                    <thead>
                        <tr>
                            <th width="30">No</th>
                            <th>Nama Siswa</th>
                            <th width="100">NISN</th>
                            <th width="30">L/P</th>
                            <th width="150">Tempat, Tanggal Lahir</th>
                            <th width="40">Kelas</th>
                            <th>Sekolah Asal</th>
                            <th width="80">NPSN Asal</th>
                            <th width="100">Tgl Diterima</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($chunk as $i => $siswa)
                            @php $globalIndex = ($pageIndex * 6) + $i + 1; @endphp
                            <tr>
                                <td class="center">{{ $globalIndex }}</td>
                                <td><strong>{!! html_entity_decode($siswa['nama_siswa'] ?? $siswa['nama_murid'] ?? '-') !!}</strong></td>
                                <td class="center">{{ $siswa['nisn'] ?? '-' }}</td>
                                <td class="center">{{ $siswa['jenis_kelamin'] ?? '-' }}</td>
                                <td>
                                    {!! html_entity_decode($siswa['tempat_lahir'] ?? '-') !!},<br>
                                    {{ isset($siswa['tanggal_lahir']) ? \Carbon\Carbon::parse($siswa['tanggal_lahir'])->translatedFormat('j F Y') : '-' }}
                                </td>
                                <td class="center">{{ $siswa['kelas'] ?? '-' }}</td>
                                <td>{!! html_entity_decode($siswa['sekolah_asal'] ?? $siswa['nama_sekolah_asal'] ?? '-') !!}</td>
                                <td class="center">{{ $siswa['npsn_asal'] ?? $siswa['npsn_sekolah_asal'] ?? '-' }}</td>
                                <td class="center">
                                    {{ isset($siswa['tanggal_diterima']) ? \Carbon\Carbon::parse($siswa['tanggal_diterima'])->translatedFormat('j F Y') : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- TTD only on the very last chunk --}}
                @if($loop->last)
                    <div class="ttd-container">
                        <div class="ttd-box">
                            <table>
                                <tr><td style="width: 30px;">a.n.</td><td>Kepala,</td></tr>
                                <tr><td></td><td>Sekretaris</td></tr>
                                <tr><td>u.b.</td><td>Kepala Bidang SMP</td></tr>
                                <tr><td colspan="2" class="spacer-ttd"></td></tr>
                                <tr><td></td><td class="officer-name">{!! html_entity_decode($penandatangan?->nama ?? '-') !!}</td></tr>
                                <tr><td></td><td>{!! html_entity_decode($penandatangan?->pangkat_golongan ?? '-') !!}</td></tr>
                                <tr><td></td><td>NIP. {!! html_entity_decode($penandatangan?->nip ?? '-') !!}</td></tr>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- FOOTER & QR (ABSOLUTE PER PAGE) --}}
                <div class="page-qr">{!! $qrCodeHtml !!}</div>
                <div class="page-footer">
                    <div class="footer-line"></div>
                    <div class="footer-text">
                        <span class="quote">"dinu kiwari ngancik nu bihari, seja ayeuna sampeureun jaga"</span>
                        <span class="tagline">Bogor Beres, Bogor Cerdas</span>
                    </div>
                </div>

            </div>
        @endforeach
    @endif

</body></html>