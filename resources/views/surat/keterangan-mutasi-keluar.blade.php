<!DOCTYPE html>

<html lang="id">



<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Surat Keterangan Mutasi Keluar</title>

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

            line-height: 1.2;

            color: #000;

            background-color: #eee;

        }



        /* --- PAGE SETUP --- */

        @page {

            size: A4 portrait;

            margin: 0;

        }



        /* --- CONTAINER HALAMAN --- */

        .page-portrait {

            width: 210mm;

            height: 297mm;

            padding: 1cm 2cm 3cm 3cm;

            background: white;

            margin: 20px auto;

            position: relative;

            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);

            page-break-after: always;

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

        }



        /* --- FOOTER WITH 3-COLUMN LAYOUT --- */

        .footer-fixed {

            position: absolute;

            bottom: 1cm;

            left: 3cm;

            right: 2cm;

            background: white;

        }







        .footer-content {

            display: flex;

            align-items: center;

            justify-content: space-between;

        }



        .footer-left {

            width: 100px;

            flex-shrink: 0;

        }



        .footer-center {

            flex: 1;

            text-align: center;

            font-size: 9pt;

            padding: 0 10px;

        }



        .footer-right {

            width: 100px;

            flex-shrink: 0;

        }



        .footer-center .quote {

            font-style: italic;

            color: #333;

            margin-bottom: 2px;

            display: block;

        }



        .footer-center .tagline {

            font-weight: bold;

            color: #000;

            display: block;

        }



        .footer-qr img {

            width: 80px;

            height: auto;

        }



        /* --- KOP SURAT --- */

        .kop-surat {

            display: flex;

            align-items: center;

            border-bottom: 3px solid #000;

            padding-bottom: 8px;

            margin-bottom: 8px;

            line-height: 1.2;

        }



        .logo-kop {

            width: 65px;

            height: 80px;

            margin-right: 10px;

        }



        .kop-text {

            flex: 1;

            text-align: center;

            line-height: 1.2;
            /*baru ditambahkan 18122025*/

        }



        .kop-text h1 {

            font-size: 12pt;

            font-weight: bold;

            margin-bottom: 1px;
            /* diubah dari 2 ke 1 */

            text-transform: uppercase;

        }



        .kop-text h2 {

            font-size: 14pt;

            font-weight: bold;

            margin-bottom: 1px;
            /* diubah dari 4 ke 1 */

            text-transform: uppercase;

        }



        .kop-text p {

            font-size: 11pt;

            margin: 0;

        }



        /* --- JUDUL SURAT --- */

        .judul-surat {

            text-align: center;

            margin: 20px 0;

        }



        .judul-surat h3 {

            font-size: 12pt;

            font-weight: bold;

            text-decoration: underline;

            margin-bottom: 2px;
            /* diubah dari 5px ke 2 px */

        }



        .judul-surat .nomor {

            font-size: 12pt;

        }



        /* --- ISI SURAT --- */

        .isi-surat {

            text-align: justify;

        }



        .opening-paragraph {

            text-indent: 50px;

            margin-bottom: 15px;

        }



        .section-title {

            font-size: 12pt;

            font-weight: bold;

            margin: 10px 0 5px 50px;

        }



        .data-table {

            margin: 0 0 10px 50px;

            border-collapse: collapse;

            width: calc(100% - 50px);

        }



        .data-table td {

            padding: 2px 0;

            vertical-align: top;

            font-size: 12pt;

        }



        .data-table td:first-child {

            width: 160px;

        }



        .data-table td:nth-child(2) {

            width: 15px;

            text-align: center;

        }



        .paragraph-full {

            margin: 10px 0;

        }



        .closing-paragraph {

            text-indent: 50px;

            margin: 15px 0;

        }



        /* --- TTD SECTION --- */

        .ttd-section {

            display: flex;

            justify-content: flex-end;

            margin-top: 15px;

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

            height: 60px;

        }
    </style>

</head>



<body>



    {{-- HALAMAN 1: PORTRAIT --}}

    <div class="page-portrait">



        <div class="kop-surat">

            <img src="{{ asset('images/logo_Kota_Bogor.png') }}" alt="Logo" class="logo-kop">

            <div class="kop-text">

                <h1>PEMERINTAH KOTA BOGOR</h1>

                <h2>DINAS PENDIDIKAN</h2>

                <p>Jl. Padjajaran No. 125, Bantarjati, Bogor Utara, Kota Bogor, Jawa Barat 16153</p>

                <p>Telp. 0251-8341101 Faksimile 0251-8341101</p>

                <p>www.disdik.kotabogor.go.id</p>

            </div>

        </div>



        {{-- Judul Surat --}}

        <div class="judul-surat">

            <h3>SURAT KETERANGAN</h3>

            <p class="nomor">Nomor : {!! html_entity_decode($mutasi->nomor_surat_izin ?? '______') !!}</p>

        </div>



        {{-- Isi Surat --}}

        <div class="isi-surat">

            <p class="opening-paragraph">

                Berdasarkan Surat Keterangan Pindah dari Kepala {!! html_entity_decode($mutasi->sekolah_asal ?? '-') !!}

                Nomor : {{ $mutasi->nomor_surat_keluar ?? '-' }}

                tanggal

                {{ isset($mutasi->tanggal_surat_keluar) ? \Carbon\Carbon::parse($mutasi->tanggal_surat_keluar)->translatedFormat('j F Y') : '-' }},

                Kepala Dinas Pendidikan Kota Bogor menerangkan bahwa :

            </p>



            <p class="section-title">DATA SISWA :</p>



            <table class="data-table">

                <tr>

                    <td>Nama</td>

                    <td>:</td>

                    <td><strong>{!! html_entity_decode($mutasi->nama_siswa ?? '-') !!}</strong></td>

                </tr>

                <tr>

                    <td>Tempat, Tanggal Lahir</td>

                    <td>:</td>

                    <td>{!! html_entity_decode($mutasi->tempat_lahir ?? '-') !!},

                        {{ isset($mutasi->tanggal_lahir) ? \Carbon\Carbon::parse($mutasi->tanggal_lahir)->translatedFormat('j F Y') : '-'}}

                    </td>

                </tr>

                <tr>

                    <td>Jenis Kelamin</td>

                    <td>:</td>

                    <td>{{ $mutasi->jenis_kelamin == 'L' ? 'Laki-laki' : ($mutasi->jenis_kelamin == 'P' ? 'Perempuan' : '-') }}

                    </td>

                </tr>

                <tr>

                    <td>Kelas</td>

                    <td>:</td>

                    <td>{{ $mutasi->kelas ?? '-' }}</td>

                </tr>

                <tr>

                    <td>NIS/NISN</td>

                    <td>:</td>

                    <td>{{ $mutasi->nis ?? '-' }}/{{ $mutasi->nisn ?? '-' }}</td>

                </tr>

                <tr>

                    <td>Asal Sekolah</td>

                    <td>:</td>

                    <td>{!! html_entity_decode($mutasi->sekolah_asal ?? '-') !!}</td>

                </tr>

                <tr>

                    <td>Alamat Sekolah</td>

                    <td>:</td>

                    <td>{!! html_entity_decode($mutasi->alamat_sekolah_asal ?? '-') !!}</td>

                </tr>

            </table>



            <p class="paragraph-full">Bermaksud pindah dan melanjutkan sekolah di ;</p>



            <p class="section-title">DATA SEKOLAH TUJUAN :</p>



            <table class="data-table">

                <tr>

                    <td>NPSN Sekolah Tujuan</td>

                    <td>:</td>

                    <td>{{ $mutasi->npsn_tujuan ?? '-' }}</td>

                </tr>

                <tr>

                    <td>Nama Sekolah Tujuan</td>

                    <td>:</td>

                    <td>{!! html_entity_decode($mutasi->sekolah_tujuan ?? '-') !!}</td>

                </tr>

                <tr>

                    <td>Kota/Kabupaten</td>

                    <td>:</td>

                    <td>{{ str_replace('Kabupaten', 'Kab.', $mutasi->kota_tujuan ?? '-') }}</td>

                </tr>

                <tr>

                    <td>Provinsi</td>

                    <td>:</td>

                    <td>{{ $mutasi->provinsi_tujuan ?? '-' }}</td>

                </tr>

                <tr>

                    <td>Alasan mutasi/pindah</td>

                    <td>:</td>

                    <td>{!! html_entity_decode($mutasi->alasan_pindah ?? '-') !!}</td>

                </tr>

            </table>



            <p class="closing-paragraph">Demikian Surat Keterangan ini kami buat untuk dipergunakan sebagaimana

                mestinya.

            </p>

        </div>



        {{-- TTD SECTION (kanan) --}}

        @if(optional($penandatangan)->gambar_ttd)
            {{-- SIGNATURE IMAGE: Right-aligned, proportional (~1/3 page width) --}}
            <div class="ttd-section">
                <div class="ttd-area" style="width: 60%;">
                    <p style="margin-bottom: 20px; text-align: center;">Bogor,
                        {{ isset($mutasi->tanggal_surat_izin) ? \Carbon\Carbon::parse($mutasi->tanggal_surat_izin)->translatedFormat('j F Y') : now()->translatedFormat('j F Y') }}
                    </p>
                    <img src="{{ url('dokumen/lihat/' . $penandatangan->gambar_ttd) }}" alt="Tanda Tangan"
                        style="width: 100%; height: auto; display: block;" onerror="this.style.display='none'">
                </div>
            </div>
        @else
            {{-- ORIGINAL TTD (manual signature area) --}}
            <div class="ttd-section">

                <div class="ttd-area">

                    <table class="tbl-ttd">

                        <tr><br>

                            <td colspan="2">Bogor,

                                {{ isset($mutasi->tanggal_surat_izin) ? \Carbon\Carbon::parse($mutasi->tanggal_surat_izin)->translatedFormat('j F Y') : now()->translatedFormat('j F Y') }}

                            </td>

                        </tr>

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

                            <td class="col-jabatan nama">
                                {!! html_entity_decode(optional($penandatangan)->nama ?? 'Nama Pejabat') !!}
                            </td>

                        </tr>

                        <tr>

                            <td></td>

                            <td class="col-jabatan">
                                {!! html_entity_decode(optional($penandatangan)->pangkat_golongan ?? 'Pangkat') !!}
                            </td>

                        </tr>

                        <tr>

                            <td></td>

                            <td class="col-jabatan">NIP. {!! html_entity_decode(optional($penandatangan)->nip ?? '-') !!}
                            </td>

                        </tr>

                    </table>

                </div>

            </div>
        @endif





    </div>



</body>



</html>