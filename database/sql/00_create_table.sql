-- ============================================
-- SIMAK - COMPLETE PRODUCTION IMPORT
-- Jalankan di phpMyAdmin production
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
  KEY `satuan_pendidikan_is_local_index` (`is_local`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Add migration record
INSERT INTO `migrations` (`migration`, `batch`) VALUES
('2024_01_01_000004_create_satuan_pendidikan_table', 2)
ON DUPLICATE KEY UPDATE `migration` = VALUES(`migration`);

-- DONE! Sekarang jalankan import_satpen_part1.sql, part2.sql, part3.sql
-- Kemudian jalankan import_users_from_satpen.sql
-- Terakhir, update password via artisan tinker