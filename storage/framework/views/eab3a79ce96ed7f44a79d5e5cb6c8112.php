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
            background-color: #eee;
        }

        /* --- PAGE SETUP --- */
        @page {
            size: A4 portrait;
            margin: 0;
        }

        @page landscape-page {
            size: A4 landscape;
            margin: 0;
        }

        /* --- CONTAINER HALAMAN --- */
        .page-portrait {
            width: 210mm;
            height: 297mm;
            padding: 1.5cm 2cm 3cm 3cm;
            background: white;
            margin: 20px auto;
            position: relative;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            page-break-after: always;
        }

        .page-landscape {
            width: 297mm;
            height: 210mm;
            padding: 1.5cm 2cm 2cm 2cm;
            background: white;
            margin: 20px auto;
            position: relative;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            page: landscape-page;
            page-break-before: always;
        }

        /* --- MEDIA PRINT --- */
        @media print {
            body {
                background: none;
            }

            .page-portrait {
                margin: 0;
                box-shadow: none;
                border: none;
                width: 210mm;
                height: 297mm;
            }

            .page-landscape {
                margin: 0;
                box-shadow: none;
                border: none;
                width: 297mm;
                height: 210mm;
            }
        }

        /* --- FOOTER FIXED --- */
        .footer-fixed {
            position: absolute;
            bottom: 1cm;
            left: 3cm;
            right: 2cm;
            text-align: center;
            border-top: 1px solid #999;
            padding-top: 5px;
            font-size: 9pt;
            background: white;
        }

        .footer-fixed .quote {
            font-style: italic;
            color: #333;
            margin-bottom: 2px;
        }

        .footer-fixed .tagline {
            font-weight: bold;
            color: #000;
        }

        /* --- KOP SURAT --- */
        .kop-surat {
            display: flex;
            align-items: center;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
            margin-bottom: 5px;
        }

        .logo-kop {
            width: 65px;
            height: 80px;
            margin-right: 10px;
        }

        .kop-text {
            flex: 1;
            text-align: center;
        }

        .kop-text h1 {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 1px;
            text-transform: uppercase;
        }

        .kop-text h2 {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 1px;
            text-transform: uppercase;
        }

        .kop-text p {
            font-size: 11pt;
            margin: 0;
        }

        /* --- HEADER & ALAMAT --- */
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
        }

        .header-left {
            width: 50%;
        }

        .header-left table {
            border-collapse: collapse;
        }

        .header-left table td {
            vertical-align: top;
            padding-bottom: 2px;
        }

        .col-label {
            width: 90px;
            min-width: 90px;
        }

        .col-colon {
            width: 20px;
            min-width: 20px;
            text-align: center;
        }

        .col-value {
            font-weight: normal;
        }

        /* Header Kanan */
        .header-right {
            width: 50%;
            padding-left: 10px;
        }

        .tgl-surat {
            text-align: right;
            margin-bottom: 15px;
        }

        .tbl-alamat {
            width: 100%;
            border-collapse: collapse;
            border: none;
            text-align: justify;
        }

        .tbl-alamat td {
            vertical-align: top;
            padding: 0;
            border: none;
            text-align: justify;
        }

        .col-yth {
            width: 35px;
        }

        .kota-bawah {
            font-weight: bold;
            display: block;
            margin-top: 5px;
            margin-left: 60px;
        }

        /* --- ISI SURAT --- */
        /* --- ISI SURAT --- */
        .isi-surat {
            text-align: justify;
            line-height: 1.5;
            margin-bottom: 10px;
            margin-left: 0;
            font-size: 12pt;
        }

        .isi-surat p {
            text-indent: 48px;
            margin-bottom: 10px;
        }

        .catatan-box {
            margin-left: 0;
        }

        .catatan-box strong {
            display: block;
            margin-bottom: 5px;
        }

        .catatan-list {
            margin-left: 20px;
        }

        .catatan-list li {
            margin-bottom: 3px;
            padding-left: 5px;
            text-align: justify;
        }

        /* --- TTD SECTION --- */
        .ttd-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .ttd-area {
            width: 50%;
            padding-left: 10px;
        }

        .tbl-ttd {
            width: 100%;
            border-collapse: collapse;
            border: none;
            font-size: 12pt;
        }

        .tbl-ttd td {
            vertical-align: top;
            padding: 0;
            border: none;
        }

        .col-an {
            width: 30px;
        }

        .col-jabatan {
            text-align: left;
        }

        .nama {
            font-weight: bold;
        }

        .spacer-ttd {
            height: 70px;
        }

        /* --- LAMPIRAN --- */
        .tabel-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 11t;
        }

        .tabel-data th,
        .tabel-data td {
            border: 1px solid #000;
            padding: 5px;
        }

        .tabel-data th {
            background: #f0f0f0;
            text-align: center;
        }

        .center {
            text-align: center;
        }

        /* --- QR FIXED --- */
        .qr-fixed {
            position: absolute;
            bottom: 3cm;
            left: 3cm;
            width: 100px;
            text-align: center;
            z-index: 10;
        }
    </style>
</head>

<body>

    
    <div class="page-portrait">

        <div class="kop-surat">
            <img src="<?php echo e(asset('images/logo_Kota_Bogor.png')); ?>" alt="Logo" class="logo-kop">
            <div class="kop-text">
                <h1>PEMERINTAH KOTA BOGOR</h1>
                <h2>DINAS PENDIDIKAN</h2>
                <p>Jl. Padjajaran No. 125, Bantarjati, Bogor Utara, Kota Bogor, Jawa Barat 16153</p>
                <p>Telp. 0251-8341101 Faksimile 0251-8341101</p>
                <p>www.disdik.kotabogor.go.id</p>
            </div>
        </div>

        
        <div class="header-container">
            <div class="header-left">
                <table>
                    <tr>
                        <td class="col-label">Nomor</td>
                        <td class="col-colon">:</td>
                        <td class="col-value"><?php echo e($mutasi->nomor_surat_izin ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td class="col-label">Sifat</td>
                        <td class="col-colon">:</td>
                        <td class="col-value">Biasa</td>
                    </tr>
                    <tr>
                        <td class="col-label">Lampiran</td>
                        <td class="col-colon">:</td>
                        <td class="col-value">1 (satu) berkas</td>
                    </tr>
                    <tr>
                        <td class="col-label">Hal</td>
                        <td class="col-colon">:</td>
                        <td class="col-value"><strong>Izin Mutasi Siswa</strong></td>
                    </tr>
                </table>
            </div>

            <div class="header-right">
                <div class="tgl-surat">
                    Bogor,
                    <?php echo e(isset($mutasi->tanggal_surat_izin) ? \Carbon\Carbon::parse($mutasi->tanggal_surat_izin)->translatedFormat('j F Y') : now()->translatedFormat('j F Y')); ?>

                </div>
            </div>
        </div>

        
        <div class="address-container"
            style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
            <div class="address-left" style="width: 75%;">
                <table class="tbl-alamat">
                    <tr>
                        <td class="col-yth">Yth.</td>
                        <td>Kepala
                            <?php echo e($mutasi->sekolah_tujuan ?? '-'); ?><br><?php echo e($mutasi->alamat_sekolah_tujuan ?? 'Jl. Alamat Sekolah'); ?><br>di,-
                        </td>
                    </tr>
                    <tr>
                        <td class="col-yth"></td>
                        <td><span class="kota-bawah"><?php echo e($mutasi->kota_sekolah_tujuan ?? 'Bogor'); ?></span></td>
                    </tr>
                </table>
            </div>
            <div class="address-right" style="width: 25%;">
                
            </div>
        </div>

        
        <div class="isi-surat">
            <br>
            <p>
                Berdasarkan surat dari Kepala <?php echo e($mutasi->sekolah_tujuan ?? '-'); ?> nomor :
                <?php echo e($mutasi->nomor_surat_permohonan ?? '-'); ?> tanggal
                <?php echo e(isset($mutasi->tanggal_surat_permohonan) ? \Carbon\Carbon::parse($mutasi->tanggal_surat_permohonan)->translatedFormat('j F Y') : '-'); ?>

                perihal Permohonan Izin Mutasi Masuk, dengan ini memberikan izin untuk menerima siswa mutasi masuk
                sebagaimana terlampir.
            </p>
        </div>

        <div class="catatan-box">
            <strong>Dengan catatan :</strong>
            <ol class="catatan-list">
                <li>proses perpindahan siswa berpedoman kepada ketentuan yang berlaku;</li>
                <li>Kuota siswa per rombel tidak melebihi ketentuan yang sudah ditetapkan;</li>
                <li>Satuan Pendidikan wajib memperbaharui data di Dapodik.</li>
            </ol>
        </div>

        <div class="isi-surat">
            <br>
            <p>Demikian surat ini kami buat untuk dipergunakan sebagaimana mestinya.</p>
        </div><br>

        
        <div class="ttd-section">
            <div class="ttd-area">
                <table class="tbl-ttd">
                    <tr>
                        <td class="col-an">a.n.</td>
                        <td class="col-jabatan">Kepala,</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td class="col-jabatan">Sekretaris</td>
                    </tr>
                    <tr>
                        <td class="col-an">u.b.</td>
                        <td class="col-jabatan">Kepala Bidang SMP</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="spacer-ttd"></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td class="col-jabatan nama"><?php echo e($penandatangan->nama ?? 'Nama Pejabat'); ?></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td class="col-jabatan"><?php echo e($penandatangan->pangkat_golongan ?? 'Pangkat'); ?></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td class="col-jabatan">NIP. <?php echo e($penandatangan->nip ?? '-'); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        
        <div class="qr-fixed">
            <?php
                $idMutasi = $mutasi->id_mutasi ?? '-';
                $tglUsulan = isset($mutasi->tanggal_pengajuan) ? \Carbon\Carbon::parse($mutasi->tanggal_pengajuan)->format('d/m/Y') : '-';
                $jumlahSiswa = isset($siswaList) ? count($siswaList) : 0;
                $namaSiswaList = [];
                if (isset($siswaList) && count($siswaList) > 0) {
                    foreach ($siswaList as $s) {
                        $namaSiswaList[] = $s->nama_siswa ?? $s->nama_murid ?? '-';
                    }
                }
                $namaSiswaStr = implode(', ', $namaSiswaList);
                $tglTtd = isset($mutasi->tanggal_surat_izin) ? \Carbon\Carbon::parse($mutasi->tanggal_surat_izin)->format('d/m/Y') : now()->format('d/m/Y');
                $namaPejabat = $penandatangan->nama ?? '-';
                $qrData = "{$idMutasi}|{$tglUsulan}|JML:{$jumlahSiswa}|{$namaSiswaStr}|TTD:{$tglTtd}|{$namaPejabat}";
                $disdikLogo = asset('images/logo_disdik_kota_bogor.png');
            ?>
            <?php echo \App\Helpers\QrCodeHelper::generateWithLogo($qrData, 100, $disdikLogo); ?>

        </div>

        <div class="footer-fixed">
            <p class="quote">"dinu kiwari ngancik nu bihari, seja ayeuna sampeureun jaga"</p>
            <p class="tagline">Bogor Beres, Bogor Cerdas</p>
        </div>
    </div>


    
    <?php if(isset($siswaList) && count($siswaList) > 0): ?>
        <div class="page-landscape">

            
            <div style="font-family: 'Arial', sans-serif; font-size: 12pt; margin-bottom: 15px;">
                <div style="font-weight: bold; margin-bottom: 5px;">LAMPIRAN</div>
                <table style="width: auto; border-collapse: collapse; border: none;">
                    <tr>
                        <td style="width: 80px; padding: 2px 0;">Nomor</td>
                        <td style="width: 10px; padding: 2px 0;">:</td>
                        <td style="padding: 2px 0;"><?php echo e($mutasi->nomor_surat_izin ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 2px 0;">Tanggal</td>
                        <td style="padding: 2px 0;">:</td>
                        <td style="padding: 2px 0;">
                            <?php echo e(isset($mutasi->tanggal_surat_izin) ? \Carbon\Carbon::parse($mutasi->tanggal_surat_izin)->translatedFormat('j F Y') : now()->translatedFormat('j F Y')); ?>

                        </td>
                    </tr>
                </table>
            </div>

            <div style="text-align: center; font-weight: bold; margin-bottom: 15px; text-transform: uppercase;">
                DAFTAR SISWA MUTASI MASUK<br>
                KE <?php echo e($mutasi->sekolah_tujuan ?? 'SEKOLAH TUJUAN'); ?>

            </div>
            

            <table class="tabel-data">
                <thead>
                    <tr>
                        <th width="40">No</th>
                        <th>Nama Siswa</th>
                        <th width="90">NISN</th>
                        <th width="40">L/P</th>
                        <th>Tempat, Tanggal Lahir</th>
                        <th width="50">Kelas</th>
                        <th>Sekolah Asal</th>
                        <th width="90">NPSN Asal</th>
                        <th>Tgl Diterima</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $siswaList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $siswa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="center"><?php echo e($index + 1); ?></td>
                            <td><strong><?php echo e($siswa->nama_siswa ?? $siswa->nama_murid ?? '-'); ?></strong></td>
                            <td class="center"><?php echo e($siswa->nisn ?? '-'); ?></td>
                            <td class="center"><?php echo e($siswa->jenis_kelamin ?? '-'); ?></td>
                            <td><?php echo e($siswa->tempat_lahir ?? '-'); ?>,
                                <?php echo e(isset($siswa->tanggal_lahir) ? \Carbon\Carbon::parse($siswa->tanggal_lahir)->translatedFormat('j F Y') : '-'); ?>

                            </td>
                            <td class="center"><?php echo e($siswa->kelas ?? '-'); ?></td>
                            <td><?php echo e($siswa->sekolah_asal ?? $siswa->nama_sekolah_asal ?? '-'); ?></td>
                            <td class="center"><?php echo e($siswa->npsn_asal ?? $siswa->npsn_sekolah_asal ?? '-'); ?></td>
                            <td class="center">
                                <?php echo e(isset($siswa->tanggal_diterima) ? \Carbon\Carbon::parse($siswa->tanggal_diterima)->translatedFormat('j F Y') : '-'); ?>

                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table><br>

            
            <div class="ttd-section">
                <div class="ttd-area">
                    <table class="tbl-ttd">
                        <tr>
                            <td class="col-an">a.n.</td>
                            <td class="col-jabatan">Kepala,</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="col-jabatan">Sekretaris</td>
                        </tr>
                        <tr>
                            <td class="col-an">u.b.</td>
                            <td class="col-jabatan">Kepala Bidang SMP</td>
                        </tr>
                        <tr>
                            <td colspan="2" class="spacer-ttd"></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="col-jabatan nama"><?php echo e($penandatangan->nama ?? 'Nama Pejabat'); ?></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="col-jabatan"><?php echo e($penandatangan->pangkat_golongan ?? 'Pangkat'); ?></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="col-jabatan">NIP. <?php echo e($penandatangan->nip ?? '-'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            
            <div class="qr-fixed">
                <?php echo \App\Helpers\QrCodeHelper::generateWithLogo($qrData, 100, $disdikLogo); ?>

            </div>

            <div class="footer-fixed">
                <p class="quote">"dinu kiwari ngancik nu bihari, seja ayeuna sampeureun jaga"</p>
                <p class="tagline">Bogor Beres, Bogor Cerdas</p>
            </div>
        </div>
    <?php endif; ?>

</body>

</html><?php /**PATH D:\antigravity\p_mutasi\LARAVEL_SIMAK\resources\views\surat\izin-mutasi-masuk.blade.php ENDPATH**/ ?>