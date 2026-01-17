<?php

namespace Database\Seeders;

use App\Models\SatuanPendidikan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SatuanPendidikanSeeder extends Seeder
{
    /**
     * Data Satuan Pendidikan SMP Kota Bogor
     * Source: https://docs.google.com/spreadsheets/d/1hu23rj39Vhy8UFNjxZx6bcBoJLAUMNCLOK0cevScNOE
     * 
     * Seeder ini akan:
     * 1. Membuat record di tabel satuan_pendidikan (referensi sekolah)
     * 2. Membuat user dengan role 'Sekolah' untuk login
     *    - Username: NPSN
     *    - Password: NPSN + "Fa*"
     */
    public function run(): void
    {
        $data = [
            // NPSN, Nama, BentukPendidikan, StatusSP, AlamatJalan, Kelurahan, Kecamatan, Kota, Provinsi
            ['20220385', 'SMP NEGERI 1 BOGOR', 'SMP', 'Negeri', 'Jl.Ir. H. Djuanda Bogor No 16', 'Paledang', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat'],
            ['20220376', 'SMP NEGERI 2', 'SMP', 'Negeri', 'Jl. Gedong Sawah IV No. 9', 'Pabaton', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat'],
            ['20220377', 'SMP NEGERI 3 BOGOR', 'SMP', 'Negeri', 'Jl Malabar No 06 Bogor', 'Tegallega', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat'],
            ['20220378', 'SMP NEGERI 4 BOGOR', 'SMP', 'Negeri', 'Jl. Kartini No.16', 'Ciwaringin', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat'],
            ['20220379', 'SMP NEGERI 5', 'SMP', 'Negeri', 'Jl. Dadali No.10-A Bogor', 'Tanahsareal', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat'],
            ['20220380', 'SMP NEGERI 6', 'SMP', 'Negeri', 'Jl. Dr. Semeru-Gang Kelor No.4', 'Menteng', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20220381', 'SMP NEGERI 7 BOGOR', 'SMP', 'Negeri', 'Jl. Paledang No 25', 'Paledang', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat'],
            ['20220382', 'SMP NEGERI 8 BOGOR', 'SMP', 'Negeri', 'Jl.Jend.A.Yani No.140 Bogor', 'Tanahsareal', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat'],
            ['20220354', 'SMP NEGERI 9 BOGOR', 'SMP', 'Negeri', 'Jalan Dereded V Nomor 14 Bogor', 'Empang', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['20220386', 'SMP NEGERI 10 BOGOR', 'SMP', 'Negeri', 'JL. KOMPLEK  PERUMDA I  CIPAKU BOGOR', 'Cipaku', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['20220387', 'SMP NEGERI 11 BOGOR', 'SMP', 'Negeri', 'Jl. Sempur No.46', 'Sempur', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat'],
            ['20220388', 'SMP NEGERI 12 BOGOR', 'SMP', 'Negeri', 'Jl. Pemuda No.50 Bogor', 'Tanahsareal', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat'],
            ['20220389', 'SMP NEGERI 13 BOGOR', 'SMP', 'Negeri', 'Jl. Pamoyanan No. 022', 'Ranggamekar', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['20220390', 'SMP NEGERI 14 BOGOR', 'SMP', 'Negeri', 'Jl.Sindang Barang Jero No.6 Situ Gede', 'Situgede', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20220391', 'SMP NEGERI 15 BOGOR', 'SMP', 'Negeri', 'Jl. Mandala No. 50', 'Ciparigi', 'Bogor Utara', 'Kota Bogor', 'Jawa Barat'],
            ['20220392', 'SMP NEGERI 16', 'SMP', 'Negeri', 'Jalan Baru Kayumanis', 'Kayumanis', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat'],
            ['20220384', 'SMP NEGERI 17 BOGOR', 'SMP', 'Negeri', 'Rancamaya', 'Bojongkerta', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['20220383', 'SMP NEGERI 18 KOTA BOGOR', 'SMP', 'Negeri', 'Jl. Jati Luhur Blok H-4 Komplek Baranangsiang Indah Bogor', 'Katulampa', 'Bogor Timur', 'Kota Bogor', 'Jawa Barat'],
            ['20220375', 'SMP NEGERI 19 BOGOR', 'SMP', 'Negeri', 'Jl. Raya Pemda Villa Bogor Indah', 'Kedunghalang', 'Bogor Utara', 'Kota Bogor', 'Jawa Barat'],
            ['20258321', 'SMP NEGERI 20', 'SMP', 'Negeri', 'Jl. Ceremai Ujung No. 1', 'Sempur', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat'],
            ['70045813', 'SEKOLAH MENENGAH PERTAMA NEGERI 21 BOGOR', 'SMP', 'Negeri', 'Komp. Perumahan Taman Griya Kencana', 'Kencana', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat'],
            ['70054147', 'SEKOLAH MENENGAH PERTAMA NEGERI 22 BOGOR', 'SMP', 'Negeri', 'Jl. Baranangsiang  Indah V/14', 'Baranangsiang', 'Bogor Timur', 'Kota Bogor', 'Jawa Barat'],
            ['70054148', 'SEKOLAH MENENGAH PERTAMA NEGERI 23 BOGOR', 'SMP', 'Negeri', 'Jl. Guru Muchtar No. 01 RT 01 RW 16', 'Cimahpar', 'Bogor Utara', 'Kota Bogor', 'Jawa Barat'],
            ['20238489', 'Sekolah Menengah Pertama Bogor Raya', 'SPK SMP', 'Swasta', 'Jl. Danau Bogor Raya', 'Tanahbaru', 'Bogor Utara', 'Kota Bogor', 'Jawa Barat'],
            ['20257153', 'SMP ADVENT BOGOR', 'SMP', 'Swasta', 'Jl. Pajajaran No.39 Sukasari - Bogor Timur', 'Sukasari', 'Bogor Timur', 'Kota Bogor', 'Jawa Barat'],
            ['20220270', 'SMP AL AZHAR PLUS BOGOR', 'SMP', 'Swasta', 'Jl. Sindang Barang No.219', 'Sindangbarang', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20220434', 'SMP AL GHAZALY BOGOR', 'SMP', 'Swasta', 'Jalan  Cempaka Nomor 6 Kotaparis', 'Kebonkelapa', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat'],
            ['20220298', 'SMP AL-FATAH', 'SMP', 'Swasta', 'Jl. KH. Sholeh Iskandar', 'Cibadak', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat'],
            ['20220353', 'SMP AL-IRSYAD BOGOR', 'SMP', 'Swasta', 'Jl. Pekojan No. 08 Kota Bogor', 'Empang', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['20269193', 'SMP AMAL KASIH', 'SMP', 'Swasta', 'JLN DANAU BOGOR RAYA NO 8 RT 01/13 KEL. KATULAMAPA KEC. BOGOR TIMUR', 'Katulampa', 'Bogor Timur', 'Kota Bogor', 'Jawa Barat'],
            ['20220406', 'SMP ANANDA', 'SMP', 'Swasta', 'Jl.Lawanggintung Komp.KPKN No.25 Rt.06/06 Bogor', 'Batutulis', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['20238486', 'SMP BAGUS', 'SMP', 'Swasta', 'Jl.Raya Cibereum Indah No.05', 'Mulyaharja', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['20220408', 'SMP BAHRUL ULUM', 'SMP', 'Swasta', 'Jalan Neglasari I No. 14', 'Cibuluh', 'Bogor Utara', 'Kota Bogor', 'Jawa Barat'],
            ['20220409', 'SMP BHAKTI INSANI BOGOR', 'SMP', 'Swasta', 'Jl. Batutulis No. 5 C Bogor', 'Batutulis', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['20220410', 'SMP BHAKTI TARUNA', 'SMP', 'Swasta', 'Jl.raya Tajur No 427', 'Muarasari', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['20220411', 'SMP BINA BUDI LUHUR BOGOR', 'SMP', 'Swasta', 'Jl. Katulampa No. 110 RT.04 RW.09', 'Katulampa', 'Bogor Timur', 'Kota Bogor', 'Jawa Barat'],
            ['20220412', 'SMP BINA GREHA', 'SMP', 'Swasta', 'Taman Cimanggu', 'Kedungwaringin', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat'],
            ['69991118', 'SMP BINA INSAN MANDIRI', 'SMP', 'Swasta', 'Jl. Ponpes Baron RT. 003 RW. 013', 'Cilendek Barat', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20220413', 'SMP BINA INSANI', 'SMP', 'Swasta', 'Jalan KH. Soleh Iskandar', 'Sukadamai', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat'],
            ['70005524', 'SMP Bina Karya Kreatif', 'SMP', 'Swasta', 'Jl. Jabaru II No. 9 RT 002 RW 005', 'Pasirkuda', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['69775715', 'SMP BINA PUTERA', 'SMP', 'Swasta', 'JL. PENGAIRAN KATULAMPA', 'Katulampa', 'Bogor Timur', 'Kota Bogor', 'Jawa Barat'],
            ['20220405', 'SMP BINA SEJAHTERA', 'SMP', 'Swasta', 'Jl. Ledeng Sindangsari No. 5', 'Kebonkelapa', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat'],
            ['70012869', 'SMP BINTANG PELAJAR ISLAMIC BOARDING SCHOOL', 'SMP', 'Swasta', 'Jl. Cimanggu Permai I RT 004 RW 008', 'Kedungjaya', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat'],
            ['20220404', 'SMP BPK PENABUR BOGOR', 'SMP', 'Swasta', 'Jalan Paledang', 'Paledang', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat'],
            ['70040019', 'SMP BQ ISLAMIC SCHOOL', 'SMP', 'Swasta', 'Jl. Pinus RT. 001 RW. 009', 'Situgede', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20220396', 'SMP BUDI MULIA BOGOR', 'SMP', 'Swasta', 'Jalan Kapten Muslihat Nomor 22', 'Paledang', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat'],
            ['69943608', 'SMP CAHAYA RANCAMAYA', 'SMP', 'Swasta', 'Jl. Rancamaya', 'Bojongkerta', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['69959365', 'SMP DAAR EN NISA ISLAMIC SCHOOL', 'SMP', 'Swasta', 'JL. PERDANA RAYA NO. 30 RT 02 RW 10', 'Kedungbadak', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat'],
            ['20220398', 'SMP DARMA BAKTI', 'SMP', 'Swasta', 'Jl Raya H.E. Sukma Bakomsari No 44', 'Harjasari', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['20238491', 'SMP DASA SEMESTA', 'SMP', 'Swasta', 'Jln Pamoyanan No 06 A', 'Ranggamekar', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['20220400', 'SMP HARAPAN SISWA', 'SMP', 'Swasta', 'Jl. Duta Utama No. 13a', 'Curugmekar', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20238492', 'SMP HARAPAN TARUNA', 'SMP', 'Swasta', 'Jl. Ciranjang I Depan No. 42', 'Pamoyanan', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['70051932', 'SMP HIGHFIELD', 'SMP', 'Swasta', 'Jl. Lawang Gintung No. 55 RT. 002 RW. 006', 'Batutulis', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['70029580', 'SMP INDIKA', 'SMP', 'Swasta', 'Jl Cilubang Nagrak', 'Situgede', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['69978550', 'SMP INFOKOM', 'SMP', 'Swasta', 'JL. Selakopi No. 32 RT. 06 RW. 08', 'Sindangbarang', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20220401', 'SMP INSAN KAMIL', 'SMP', 'Swasta', 'Jl. R. Aria Surialaga', 'Pasirjaya', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20220402', 'SMP ISLAM AL MUSTARIH', 'SMP', 'Swasta', 'Jl. R. ARIA SURIALAGA No. 111 Bogor', 'Pasirkuda', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20238494', 'SMP ISLAM AL-UMM', 'SMP', 'Swasta', 'Jl. Raya Ujung Tol Jagorawi', 'Sindangsari', 'Bogor Timur', 'Kota Bogor', 'Jawa Barat'],
            ['60726275', 'SMP ISLAM DA WATUL QURAN BOGOR', 'SMP', 'Swasta', 'Jl. Ledeng Gunung Batu Bogor', 'Gunungbatu', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['70008403', 'SMP ISLAM TERPADU NURUL FATA', 'SMP', 'Swasta', 'Jl. Pabuaran No. 43 RT. 001 RW. 005', 'Pamoyanan', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['20220403', 'SMP ISLAM YTM 1', 'SMP', 'Swasta', 'Jl. Lodaya 1 No 57', 'Babakan', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat'],
            ['20238495', 'SMP IT ABN', 'SMP', 'Swasta', 'Jl. Raya Taman Cimanggu No. 51', 'Kedungwaringin', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat'],
            ['69880230', 'SMP IT AL-YASMIN', 'SMP', 'Swasta', 'JL. RAYA SINDANG BARANG NO. 16', 'Sindangbarang', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['70051414', 'SMP IT AL-YASMIN 2', 'SMP', 'Swasta', 'Kedung Halang Sentral No. 7', 'Sukaresmi', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat'],
            ['69861146', 'SMP IT AN NABA BOGOR', 'SMP', 'Swasta', 'JALAN CIREMAI UJUNG NO. 53 BANTARJATI', 'Bantarjati', 'Bogor Utara', 'Kota Bogor', 'Jawa Barat'],
            ['70048033', 'SMP IT AR ROHMANIYAH', 'SMP', 'Swasta', 'JL. KH. AHMAD SYA`YANI KP. SUMURWANGI', 'Mekarwangi', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat'],
            ['20267941', 'SMP IT AT TAUFIQ', 'SMP', 'Swasta', 'Jl. Cimanggu Permai I', 'Kedungjaya', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat'],
            ['70008153', 'SMP IT BINA BANGSA SEJAHTERA', 'SMP', 'Swasta', 'Jl. Batuhulung Km 7  RT 003 RW 002', 'Margajaya', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20258515', 'SMP IT INSANTAMA', 'SMP', 'Swasta', 'Jl. Hegarmanah IV No. 47 Rt.01/08 Kel. Gunung Batu Kec. Bogor Barat', 'Gunungbatu', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20268863', 'SMP ITA  el Mamur', 'SMP', 'Swasta', 'Jl. Raya Cimanggu Barata No. 02', 'Kedungbadak', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat'],
            ['20220415', 'SMP KAMANDAKA', 'SMP', 'Swasta', 'Jl. H. Achmad Sobana', 'Tegalgundil', 'Bogor Utara', 'Kota Bogor', 'Jawa Barat'],
            ['20220426', 'SMP KESATUAN', 'SMP', 'Swasta', 'Jl. Pajajaran Kompleks Pulo Armen', 'Baranangsiang', 'Bogor Timur', 'Kota Bogor', 'Jawa Barat'],
            ['20253188', 'SMP KOSGORO KOTA BOGOR', 'SMP', 'Swasta', 'Jl. Pajajaran No.217 A', 'Bantarjati', 'Bogor Utara', 'Kota Bogor', 'Jawa Barat'],
            ['69987584', 'SMP KREATIVA', 'SMP', 'Swasta', 'JL. RAYA MUNJUL RT. 03 RW. 06', 'Kayumanis', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat'],
            ['20220414', 'SMP KRISTEN TUNAS HARAPAN BOGOR', 'SMP', 'Swasta', 'Jl. Pahlawan No. 140 Bogor', 'Empang', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['20220427', 'SMP KRISTEN YPK SATU BAKTI', 'SMP', 'Swasta', 'Jalan Kartini No.3. Rt.1. Rw.2.', 'Ciwaringin', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat'],
            ['20271516', 'SMP KURNIA', 'SMP', 'Swasta', 'Jl. Batara No. 9', 'Ciluar', 'Bogor Utara', 'Kota Bogor', 'Jawa Barat'],
            ['20220428', 'SMP MARDI WALUYA', 'SMP', 'Swasta', 'Jl. Pahlawan No. 96', 'Empang', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['20220429', 'SMP Mardi Yuana Bogor', 'SMP', 'Swasta', 'Jl.Siliwangi No. 50', 'Bondongan', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['69942577', 'SMP MODERN AL- ALAWIYAH', 'SMP', 'Swasta', 'JL. KH. MUHIDIN', 'Kencana', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat'],
            ['20220430', 'SMP MUHAMMADIYAH KOTA BOGOR', 'SMP', 'Swasta', 'Jln. Pahlawan Gang Raden Saleh No.55', 'Empang', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['20268774', 'SMP MUTIARA KARTINI', 'SMP', 'Swasta', 'Cimanggu Kecil Bogor', 'Ciwaringin', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat'],
            ['69965013', 'SMP NURAIDA ISLAMIC BOARDING SCHOOL', 'SMP', 'Swasta', 'Jalan Guru Muhtar II No.1 RT.03/RW.15', 'Cimahpar', 'Bogor Utara', 'Kota Bogor', 'Jawa Barat'],
            ['20271353', 'SMP PANDUTAMA', 'SMP', 'Swasta', 'Jl.panduraya Perum Indraprasta', 'Bantarjati', 'Bogor Utara', 'Kota Bogor', 'Jawa Barat'],
            ['20220425', 'SMP PERSADA', 'SMP', 'Swasta', 'Jl. Tanah Baru No. 23', 'Tanahbaru', 'Bogor Utara', 'Kota Bogor', 'Jawa Barat'],
            ['20220416', 'SMP PERWANIDA BOGOR', 'SMP', 'Swasta', 'Jln Pahlawan No 15 Bogor', 'Empang', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['20220433', 'SMP PESAT', 'SMP', 'Swasta', 'Jl. Poras No. 7 Sindang Barang', 'Loji', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20238500', 'SMP PGRI 1 BOGOR', 'SMP', 'Swasta', 'Jl. PAPANDAYAN NO. 25', 'Babakan', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat'],
            ['20220418', 'SMP PGRI 11', 'SMP', 'Swasta', 'Jl. Rangga Mekar No. 35  Rt. 5 / 5', 'Ranggamekar', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['20238501', 'SMP PGRI 12 BOGOR', 'SMP', 'Swasta', 'Jl. Cilendek Barat', 'Cilendek Barat', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20220419', 'SMP PGRI 13 BOGOR', 'SMP', 'Swasta', 'Sindang Barang', 'Sindangbarang', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20220420', 'SMP PGRI 14', 'SMP', 'Swasta', 'DRAMAGA KAMPUNG MANGGIS', 'Margajaya', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20238502', 'SMP PGRI 15 BOGOR', 'SMP', 'Swasta', 'JL. Parung Banteng', 'Katulampa', 'Bogor Timur', 'Kota Bogor', 'Jawa Barat'],
            ['20220422', 'SMP PGRI 17', 'SMP', 'Swasta', 'Jln.Dekeng No.45', 'Genteng', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['20220423', 'SMP PGRI 2', 'SMP', 'Swasta', 'Jln. Raya Tajur No.24-BIOTROP', 'Pakuan', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['20238503', 'SMP PGRI 3', 'SMP', 'Swasta', 'Jl. Raya Ciomas No. 308 Bogor', 'Pasirmulya', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20220395', 'SMP PGRI 4', 'SMP', 'Swasta', 'Jl.batutulis', 'Batutulis', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['20220394', 'SMP PGRI 5', 'SMP', 'Swasta', 'Jl. Pajajaran (Blk. Komplek BPT) Bogor', 'Babakan', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat'],
            ['20220393', 'SMP PGRI 6', 'SMP', 'Swasta', 'Jl. Pandawa Warung Jambu RT.005 RW.006 Kel. Bantarjati Kec. Bogor Utara', 'Bantarjati', 'Bogor Utara', 'Kota Bogor', 'Jawa Barat'],
            ['20238504', 'SMP PGRI 7 KOTA BOGOR', 'SMP', 'Swasta', 'Jl. Raya Cibuluh Gg. Kondang Rt. 03/08 Kel. Cibuluh Kec. Bogor Utara Kota Bogor', 'Cibuluh', 'Bogor Utara', 'Kota Bogor', 'Jawa Barat'],
            ['20220365', 'SMP PGRI 8', 'SMP', 'Swasta', 'Jl. Raya Semplak-Cemplang No. 276 Kota Bogor', 'Cilendek Barat', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20220366', 'SMP PGRI 9', 'SMP', 'Swasta', 'Jl. Kh. Soleh Iskandar No. 21 Kota Bogor', 'Kedungbadak', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat'],
            ['20238505', 'SMP PLUS YASPERA', 'SMP', 'Swasta', 'Kp. Masjid-bogor', 'Mekarwangi', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat'],
            ['20220424', 'SMP PUI', 'SMP', 'Swasta', 'Jl.pahlawan No.57/b', 'Bondongan', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['20220367', 'SMP REGINA PACIS', 'SMP', 'Swasta', 'Jl. Ir. H. Juanda No. 2 Bogor', 'Pabaton', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat'],
            ['20220368', 'SMP RIMBA TERUNA BOGOR', 'SMP', 'Swasta', 'Jl.Rimba Mulya II RT.02 RW 03', 'Pasirmulya', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20220369', 'SMP SAPTA MARGA', 'SMP', 'Swasta', 'Jalan Lawang Gintung', 'Lawanggintung', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['60726063', 'SMP SEKOLAH ALAM BOGOR', 'SMP', 'Swasta', 'JL. PANGERAN SHOGIRI KAV. AL-HASANAH RT 01/04', 'Tanahbaru', 'Bogor Utara', 'Kota Bogor', 'Jawa Barat'],
            ['70005573', 'SMP SEKOLAH DIAN HARAPAN', 'SMP', 'Swasta', 'Jl. Raya Pajajaran No. 27 RT 001 RW 004', 'Babakan', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat'],
            ['69992568', 'SMP SEKOLAH ISLAM IBNU HAJAR', 'SMP', 'Swasta', 'Jl. Raya Katulampa Parung Banteng RT 003 RW 011', 'Katulampa', 'Bogor Timur', 'Kota Bogor', 'Jawa Barat'],
            ['20220370', 'SMP SILIWANGI', 'SMP', 'Swasta', 'Jl Kol Enjo Marta Disastra III', 'Kedungbadak', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat'],
            ['20220371', 'SMP TAMAN CAHAYA', 'SMP', 'Swasta', 'Jl. Semplak Cijajhe No. 19', 'Semplak', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20220372', 'SMP TAMANSISWA (TAMAN DEWASA)', 'SMP', 'Swasta', 'Jl. Merdeka No. 172', 'Ciwaringin', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat'],
            ['20220364', 'SMP TARUNA ANDIGHA', 'SMP', 'Swasta', 'Jl. Veteran No. 46', 'Kebonkelapa', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat'],
            ['20244215', 'SMP TARUNA BANGSA', 'SMP', 'Swasta', 'Jl.Mekar Jaya BLK Villa Kebun RayaNo.9 Rt.02/04', 'Pasirkuda', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20238512', 'SMP TUNAS INSANI', 'SMP', 'Swasta', 'JL. CURUG INDAH NO. 31 RT. 04 RW. 05', 'Semplak', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20238513', 'SMP WINDIAN NUGRAHA', 'SMP', 'Swasta', 'Jl. Mekar Saluyu No. 16 Komplek Taman Yasmin Sektor 1', 'Cilendek Barat', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20220356', 'SMP YAPIS', 'SMP', 'Swasta', 'Jalan Jend. Ahmad Yani I', 'Tanahsareal', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat'],
            ['20220357', 'SMP YAPSIDA', 'SMP', 'Swasta', 'Jl. Raya Dramaga Caringin Km. 7 Bogor', 'Margajaya', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20220358', 'SMP YASIH', 'SMP', 'Swasta', 'Cikeas Katulampa', 'Baranangsiang', 'Bogor Timur', 'Kota Bogor', 'Jawa Barat'],
            ['69786551', 'SMP YKTB', 'SMP', 'Swasta', 'JL. DR. SEMERU NO. 42', 'Menteng', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20220360', 'SMP YP 17 Bogor', 'SMP', 'Swasta', 'Jl. Gunung Batu Loji No. 144 Rt', 'Loji', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
            ['20220362', 'SMP YP PEMBANGUNAN', 'SMP', 'Swasta', 'Jalan Pajajaran No. 63', 'Bantarjati', 'Bogor Utara', 'Kota Bogor', 'Jawa Barat'],
            ['69892645', 'SMP ZAFIRAH', 'SMP', 'Swasta', 'JL. PARUNG BANTENG RT 01/02', 'Katulampa', 'Bogor Timur', 'Kota Bogor', 'Jawa Barat'],
            ['20220373', 'SMP ZELANI  AL MANSYUR', 'SMP', 'Swasta', 'Jl. Raya Ciawi Km. 09', 'Sindangsari', 'Bogor Timur', 'Kota Bogor', 'Jawa Barat'],
            ['20220374', 'SMP ZELANI  AL-MANSYUR', 'SMP', 'Swasta', 'Jl Semeru No.43', 'Kebonkelapa', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat'],
            ['20253305', 'SMPIT AL BUNYAN', 'SMP', 'Swasta', 'Jl. Ama Sandi RT. 05 RW. 09', 'Cikaret', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat'],
            ['69982666', 'SMPIT AL KHAIRIYAH', 'SMP', 'Swasta', 'Jalan Jagung Ujung No. 14 RT 01 RW 09', 'Baranangsiang', 'Bogor Timur', 'Kota Bogor', 'Jawa Barat'],
            ['69991798', 'SMPIT ZAID BIN TSABIT', 'SMP', 'Swasta', 'Kp. Bojong Neros Jl. Parfi RT 003 RW 007', 'Curug', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat'],
        ];

        $satpenCreated = 0;
        $satpenSkipped = 0;
        $userCreated = 0;
        $userSkipped = 0;

        foreach ($data as $row) {
            $npsn = $row[0];
            $nama = $row[1];
            $bentukPendidikan = $row[2];
            $statusSp = $row[3];
            $alamatJalan = $row[4];
            $kelurahan = $row[5];
            $kecamatan = $row[6];
            $kota = $row[7];
            $provinsi = $row[8];

            // Format alamat lengkap: Alamat, Kel. Kelurahan, Kec. Kecamatan, Kota, Provinsi
            $alamatLengkap = sprintf(
                '%s, Kel. %s, Kec. %s, %s, %s',
                $alamatJalan,
                $kelurahan,
                $kecamatan,
                $kota,
                $provinsi
            );

            // ========== 1. Create Satuan Pendidikan ==========
            if (!SatuanPendidikan::where('npsn', $npsn)->exists()) {
                SatuanPendidikan::create([
                    'npsn' => $npsn,
                    'nama' => $nama,
                    'bentuk_pendidikan' => $bentukPendidikan,
                    'status_sp' => $statusSp,
                    'alamat_jalan' => $alamatJalan,
                    'kelurahan' => $kelurahan,
                    'kecamatan' => $kecamatan,
                    'kota' => $kota,
                    'provinsi' => $provinsi,
                    'alamat_lengkap' => $alamatLengkap,
                    'is_local' => true,
                ]);
                $this->command->info("📚 Satpen: {$npsn} ({$nama})");
                $satpenCreated++;
            } else {
                $satpenSkipped++;
            }

            // ========== 2. Create User for Login ==========
            // Username: NPSN
            // Password: NPSN + "Fa*"
            if (!User::where('username', $npsn)->exists()) {
                User::create([
                    'username' => $npsn,
                    'password' => Hash::make($npsn . 'Fa*'),
                    'role' => 'Sekolah',
                    'nama_pengguna' => 'Operator ' . $nama,
                    'npsn' => $npsn,
                    'nama_sekolah' => $nama,
                    'alamat' => $alamatLengkap,
                    'status' => 'Aktif',
                ]);
                $this->command->info("👤 User: {$npsn} / {$npsn}Fa*");
                $userCreated++;
            } else {
                $userSkipped++;
            }
        }

        $this->command->newLine();
        $this->command->info("════════════════════════════════════════════");
        $this->command->info("📊 SUMMARY");
        $this->command->info("════════════════════════════════════════════");
        $this->command->info("📚 Satuan Pendidikan:");
        $this->command->info("   Total Data   : " . count($data));
        $this->command->info("   Created      : {$satpenCreated}");
        $this->command->info("   Skipped      : {$satpenSkipped}");
        $this->command->newLine();
        $this->command->info("👤 Users (Role: Sekolah):");
        $this->command->info("   Created      : {$userCreated}");
        $this->command->info("   Skipped      : {$userSkipped}");
        $this->command->info("════════════════════════════════════════════");
        $this->command->newLine();
        $this->command->info("📝 FORMAT LOGIN:");
        $this->command->info("   Username: NPSN (contoh: 20220389)");
        $this->command->info("   Password: NPSN + 'Fa*' (contoh: 20220389Fa*)");
        $this->command->info("════════════════════════════════════════════");
    }
}