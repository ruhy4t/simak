<<<<<<< HEAD
-- ============================================
-- SIMAK - Import Satuan Pendidikan + Users
-- Generated: 2024-12-06
-- ============================================

-- 1. CREATE TABLE satuan_pendidikan
CREATE TABLE IF NOT EXISTS `satuan_pendidikan` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `npsn` varchar(8) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `bentuk_pendidikan` varchar(20) DEFAULT 'SMP',
  `status_sp` enum('Negeri','Swasta') DEFAULT 'Swasta',
  `alamat_jalan` text DEFAULT NULL,
  `kelurahan` varchar(50) DEFAULT NULL,
  `kecamatan` varchar(50) DEFAULT NULL,
  `kota` varchar(50) DEFAULT 'Kota Bogor',
  `provinsi` varchar(50) DEFAULT 'Jawa Barat',
  `alamat_lengkap` text DEFAULT NULL,
  `is_local` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `satuan_pendidikan_npsn_unique` (`npsn`),
  KEY `satuan_pendidikan_nama_index` (`nama`),
  KEY `satuan_pendidikan_kota_provinsi_index` (`kota`,`provinsi`),
  KEY `satuan_pendidikan_is_local_index` (`is_local`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. INSERT Satuan Pendidikan (127 records)
INSERT INTO `satuan_pendidikan` (`npsn`, `nama`, `bentuk_pendidikan`, `status_sp`, `alamat_jalan`, `kelurahan`, `kecamatan`, `kota`, `provinsi`, `alamat_lengkap`, `is_local`, `created_at`, `updated_at`) VALUES
('20220385', 'SMP NEGERI 1 BOGOR', 'SMP', 'Negeri', 'Jl.Ir. H. Djuanda Bogor No 16', 'Paledang', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat', 'Jl.Ir. H. Djuanda Bogor No 16, Kel. Paledang, Kec. Bogor Tengah, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220376', 'SMP NEGERI 2', 'SMP', 'Negeri', 'Jl. Gedong Sawah IV No. 9', 'Pabaton', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat', 'Jl. Gedong Sawah IV No. 9, Kel. Pabaton, Kec. Bogor Tengah, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220377', 'SMP NEGERI 3 BOGOR', 'SMP', 'Negeri', 'Jl Malabar No 06 Bogor', 'Tegallega', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat', 'Jl Malabar No 06 Bogor, Kel. Tegallega, Kec. Bogor Tengah, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220378', 'SMP NEGERI 4 BOGOR', 'SMP', 'Negeri', 'Jl. Kartini No.16', 'Ciwaringin', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat', 'Jl. Kartini No.16, Kel. Ciwaringin, Kec. Bogor Tengah, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220379', 'SMP NEGERI 5', 'SMP', 'Negeri', 'Jl. Dadali No.10-A Bogor', 'Tanahsareal', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat', 'Jl. Dadali No.10-A Bogor, Kel. Tanahsareal, Kec. Tanah Sareal, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220380', 'SMP NEGERI 6', 'SMP', 'Negeri', 'Jl. Dr. Semeru-Gang Kelor No.4', 'Menteng', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat', 'Jl. Dr. Semeru-Gang Kelor No.4, Kel. Menteng, Kec. Bogor Barat, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220381', 'SMP NEGERI 7 BOGOR', 'SMP', 'Negeri', 'Jl. Paledang No 25', 'Paledang', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat', 'Jl. Paledang No 25, Kel. Paledang, Kec. Bogor Tengah, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220382', 'SMP NEGERI 8 BOGOR', 'SMP', 'Negeri', 'Jl.Jend.A.Yani No.140 Bogor', 'Tanahsareal', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat', 'Jl.Jend.A.Yani No.140 Bogor, Kel. Tanahsareal, Kec. Tanah Sareal, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220354', 'SMP NEGERI 9 BOGOR', 'SMP', 'Negeri', 'Jalan Dereded V Nomor 14 Bogor', 'Empang', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat', 'Jalan Dereded V Nomor 14 Bogor, Kel. Empang, Kec. Bogor Selatan, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220386', 'SMP NEGERI 10 BOGOR', 'SMP', 'Negeri', 'JL. KOMPLEK PERUMDA I CIPAKU BOGOR', 'Cipaku', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat', 'JL. KOMPLEK PERUMDA I CIPAKU BOGOR, Kel. Cipaku, Kec. Bogor Selatan, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220387', 'SMP NEGERI 11 BOGOR', 'SMP', 'Negeri', 'Jl. Sempur No.46', 'Sempur', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat', 'Jl. Sempur No.46, Kel. Sempur, Kec. Bogor Tengah, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220388', 'SMP NEGERI 12 BOGOR', 'SMP', 'Negeri', 'Jl. Pemuda No.50 Bogor', 'Tanahsareal', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat', 'Jl. Pemuda No.50 Bogor, Kel. Tanahsareal, Kec. Tanah Sareal, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220389', 'SMP NEGERI 13 BOGOR', 'SMP', 'Negeri', 'Jl. Pamoyanan No. 022', 'Ranggamekar', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat', 'Jl. Pamoyanan No. 022, Kel. Ranggamekar, Kec. Bogor Selatan, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220390', 'SMP NEGERI 14 BOGOR', 'SMP', 'Negeri', 'Jl.Sindang Barang Jero No.6 Situ Gede', 'Situgede', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat', 'Jl.Sindang Barang Jero No.6 Situ Gede, Kel. Situgede, Kec. Bogor Barat, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220391', 'SMP NEGERI 15 BOGOR', 'SMP', 'Negeri', 'Jl. Mandala No. 50', 'Ciparigi', 'Bogor Utara', 'Kota Bogor', 'Jawa Barat', 'Jl. Mandala No. 50, Kel. Ciparigi, Kec. Bogor Utara, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220392', 'SMP NEGERI 16', 'SMP', 'Negeri', 'Jalan Baru Kayumanis', 'Kayumanis', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat', 'Jalan Baru Kayumanis, Kel. Kayumanis, Kec. Tanah Sareal, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220384', 'SMP NEGERI 17 BOGOR', 'SMP', 'Negeri', 'Rancamaya', 'Bojongkerta', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat', 'Rancamaya, Kel. Bojongkerta, Kec. Bogor Selatan, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220383', 'SMP NEGERI 18 KOTA BOGOR', 'SMP', 'Negeri', 'Jl. Jati Luhur Blok H-4 Komplek Baranangsiang Indah Bogor', 'Katulampa', 'Bogor Timur', 'Kota Bogor', 'Jawa Barat', 'Jl. Jati Luhur Blok H-4 Komplek Baranangsiang Indah Bogor, Kel. Katulampa, Kec. Bogor Timur, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220375', 'SMP NEGERI 19 BOGOR', 'SMP', 'Negeri', 'Jl. Raya Pemda Villa Bogor Indah', 'Kedunghalang', 'Bogor Utara', 'Kota Bogor', 'Jawa Barat', 'Jl. Raya Pemda Villa Bogor Indah, Kel. Kedunghalang, Kec. Bogor Utara, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20258321', 'SMP NEGERI 20', 'SMP', 'Negeri', 'Jl. Ceremai Ujung No. 1', 'Sempur', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat', 'Jl. Ceremai Ujung No. 1, Kel. Sempur, Kec. Bogor Tengah, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('70045813', 'SEKOLAH MENENGAH PERTAMA NEGERI 21 BOGOR', 'SMP', 'Negeri', 'Komp. Perumahan Taman Griya Kencana', 'Kencana', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat', 'Komp. Perumahan Taman Griya Kencana, Kel. Kencana, Kec. Tanah Sareal, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('70054147', 'SEKOLAH MENENGAH PERTAMA NEGERI 22 BOGOR', 'SMP', 'Negeri', 'Jl. Baranangsiang Indah V/14', 'Baranangsiang', 'Bogor Timur', 'Kota Bogor', 'Jawa Barat', 'Jl. Baranangsiang Indah V/14, Kel. Baranangsiang, Kec. Bogor Timur, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('70054148', 'SEKOLAH MENENGAH PERTAMA NEGERI 23 BOGOR', 'SMP', 'Negeri', 'Jl. Guru Muchtar No. 01 RT 01 RW 16', 'Cimahpar', 'Bogor Utara', 'Kota Bogor', 'Jawa Barat', 'Jl. Guru Muchtar No. 01 RT 01 RW 16, Kel. Cimahpar, Kec. Bogor Utara, Kota Bogor, Jawa Barat', 1, NOW(), NOW());
=======
-- ============================================
-- SIMAK - Import Satuan Pendidikan + Users
-- Generated: 2024-12-06
-- ============================================

-- 1. CREATE TABLE satuan_pendidikan
CREATE TABLE IF NOT EXISTS `satuan_pendidikan` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `npsn` varchar(8) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `bentuk_pendidikan` varchar(20) DEFAULT 'SMP',
  `status_sp` enum('Negeri','Swasta') DEFAULT 'Swasta',
  `alamat_jalan` text DEFAULT NULL,
  `kelurahan` varchar(50) DEFAULT NULL,
  `kecamatan` varchar(50) DEFAULT NULL,
  `kota` varchar(50) DEFAULT 'Kota Bogor',
  `provinsi` varchar(50) DEFAULT 'Jawa Barat',
  `alamat_lengkap` text DEFAULT NULL,
  `is_local` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `satuan_pendidikan_npsn_unique` (`npsn`),
  KEY `satuan_pendidikan_nama_index` (`nama`),
  KEY `satuan_pendidikan_kota_provinsi_index` (`kota`,`provinsi`),
  KEY `satuan_pendidikan_is_local_index` (`is_local`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. INSERT Satuan Pendidikan (127 records)
INSERT INTO `satuan_pendidikan` (`npsn`, `nama`, `bentuk_pendidikan`, `status_sp`, `alamat_jalan`, `kelurahan`, `kecamatan`, `kota`, `provinsi`, `alamat_lengkap`, `is_local`, `created_at`, `updated_at`) VALUES
('20220385', 'SMP NEGERI 1 BOGOR', 'SMP', 'Negeri', 'Jl.Ir. H. Djuanda Bogor No 16', 'Paledang', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat', 'Jl.Ir. H. Djuanda Bogor No 16, Kel. Paledang, Kec. Bogor Tengah, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220376', 'SMP NEGERI 2', 'SMP', 'Negeri', 'Jl. Gedong Sawah IV No. 9', 'Pabaton', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat', 'Jl. Gedong Sawah IV No. 9, Kel. Pabaton, Kec. Bogor Tengah, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220377', 'SMP NEGERI 3 BOGOR', 'SMP', 'Negeri', 'Jl Malabar No 06 Bogor', 'Tegallega', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat', 'Jl Malabar No 06 Bogor, Kel. Tegallega, Kec. Bogor Tengah, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220378', 'SMP NEGERI 4 BOGOR', 'SMP', 'Negeri', 'Jl. Kartini No.16', 'Ciwaringin', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat', 'Jl. Kartini No.16, Kel. Ciwaringin, Kec. Bogor Tengah, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220379', 'SMP NEGERI 5', 'SMP', 'Negeri', 'Jl. Dadali No.10-A Bogor', 'Tanahsareal', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat', 'Jl. Dadali No.10-A Bogor, Kel. Tanahsareal, Kec. Tanah Sareal, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220380', 'SMP NEGERI 6', 'SMP', 'Negeri', 'Jl. Dr. Semeru-Gang Kelor No.4', 'Menteng', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat', 'Jl. Dr. Semeru-Gang Kelor No.4, Kel. Menteng, Kec. Bogor Barat, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220381', 'SMP NEGERI 7 BOGOR', 'SMP', 'Negeri', 'Jl. Paledang No 25', 'Paledang', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat', 'Jl. Paledang No 25, Kel. Paledang, Kec. Bogor Tengah, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220382', 'SMP NEGERI 8 BOGOR', 'SMP', 'Negeri', 'Jl.Jend.A.Yani No.140 Bogor', 'Tanahsareal', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat', 'Jl.Jend.A.Yani No.140 Bogor, Kel. Tanahsareal, Kec. Tanah Sareal, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220354', 'SMP NEGERI 9 BOGOR', 'SMP', 'Negeri', 'Jalan Dereded V Nomor 14 Bogor', 'Empang', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat', 'Jalan Dereded V Nomor 14 Bogor, Kel. Empang, Kec. Bogor Selatan, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220386', 'SMP NEGERI 10 BOGOR', 'SMP', 'Negeri', 'JL. KOMPLEK PERUMDA I CIPAKU BOGOR', 'Cipaku', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat', 'JL. KOMPLEK PERUMDA I CIPAKU BOGOR, Kel. Cipaku, Kec. Bogor Selatan, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220387', 'SMP NEGERI 11 BOGOR', 'SMP', 'Negeri', 'Jl. Sempur No.46', 'Sempur', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat', 'Jl. Sempur No.46, Kel. Sempur, Kec. Bogor Tengah, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220388', 'SMP NEGERI 12 BOGOR', 'SMP', 'Negeri', 'Jl. Pemuda No.50 Bogor', 'Tanahsareal', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat', 'Jl. Pemuda No.50 Bogor, Kel. Tanahsareal, Kec. Tanah Sareal, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220389', 'SMP NEGERI 13 BOGOR', 'SMP', 'Negeri', 'Jl. Pamoyanan No. 022', 'Ranggamekar', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat', 'Jl. Pamoyanan No. 022, Kel. Ranggamekar, Kec. Bogor Selatan, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220390', 'SMP NEGERI 14 BOGOR', 'SMP', 'Negeri', 'Jl.Sindang Barang Jero No.6 Situ Gede', 'Situgede', 'Bogor Barat', 'Kota Bogor', 'Jawa Barat', 'Jl.Sindang Barang Jero No.6 Situ Gede, Kel. Situgede, Kec. Bogor Barat, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220391', 'SMP NEGERI 15 BOGOR', 'SMP', 'Negeri', 'Jl. Mandala No. 50', 'Ciparigi', 'Bogor Utara', 'Kota Bogor', 'Jawa Barat', 'Jl. Mandala No. 50, Kel. Ciparigi, Kec. Bogor Utara, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220392', 'SMP NEGERI 16', 'SMP', 'Negeri', 'Jalan Baru Kayumanis', 'Kayumanis', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat', 'Jalan Baru Kayumanis, Kel. Kayumanis, Kec. Tanah Sareal, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220384', 'SMP NEGERI 17 BOGOR', 'SMP', 'Negeri', 'Rancamaya', 'Bojongkerta', 'Bogor Selatan', 'Kota Bogor', 'Jawa Barat', 'Rancamaya, Kel. Bojongkerta, Kec. Bogor Selatan, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220383', 'SMP NEGERI 18 KOTA BOGOR', 'SMP', 'Negeri', 'Jl. Jati Luhur Blok H-4 Komplek Baranangsiang Indah Bogor', 'Katulampa', 'Bogor Timur', 'Kota Bogor', 'Jawa Barat', 'Jl. Jati Luhur Blok H-4 Komplek Baranangsiang Indah Bogor, Kel. Katulampa, Kec. Bogor Timur, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20220375', 'SMP NEGERI 19 BOGOR', 'SMP', 'Negeri', 'Jl. Raya Pemda Villa Bogor Indah', 'Kedunghalang', 'Bogor Utara', 'Kota Bogor', 'Jawa Barat', 'Jl. Raya Pemda Villa Bogor Indah, Kel. Kedunghalang, Kec. Bogor Utara, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('20258321', 'SMP NEGERI 20', 'SMP', 'Negeri', 'Jl. Ceremai Ujung No. 1', 'Sempur', 'Bogor Tengah', 'Kota Bogor', 'Jawa Barat', 'Jl. Ceremai Ujung No. 1, Kel. Sempur, Kec. Bogor Tengah, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('70045813', 'SEKOLAH MENENGAH PERTAMA NEGERI 21 BOGOR', 'SMP', 'Negeri', 'Komp. Perumahan Taman Griya Kencana', 'Kencana', 'Tanah Sareal', 'Kota Bogor', 'Jawa Barat', 'Komp. Perumahan Taman Griya Kencana, Kel. Kencana, Kec. Tanah Sareal, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('70054147', 'SEKOLAH MENENGAH PERTAMA NEGERI 22 BOGOR', 'SMP', 'Negeri', 'Jl. Baranangsiang Indah V/14', 'Baranangsiang', 'Bogor Timur', 'Kota Bogor', 'Jawa Barat', 'Jl. Baranangsiang Indah V/14, Kel. Baranangsiang, Kec. Bogor Timur, Kota Bogor, Jawa Barat', 1, NOW(), NOW()),
('70054148', 'SEKOLAH MENENGAH PERTAMA NEGERI 23 BOGOR', 'SMP', 'Negeri', 'Jl. Guru Muchtar No. 01 RT 01 RW 16', 'Cimahpar', 'Bogor Utara', 'Kota Bogor', 'Jawa Barat', 'Jl. Guru Muchtar No. 01 RT 01 RW 16, Kel. Cimahpar, Kec. Bogor Utara, Kota Bogor, Jawa Barat', 1, NOW(), NOW());
>>>>>>> 9466a07 (Upload project pertama kali)
