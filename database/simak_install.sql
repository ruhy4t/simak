-- =====================================================
-- SIMAK Database Installation Script
-- Import via phpMyAdmin -> SQL tab
-- =====================================================

SET FOREIGN_KEY_CHECKS=0;

-- =====================================================
-- Table: users
-- =====================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('SuperAdmin', 'Dinas', 'Sekolah', 'Operator') NOT NULL DEFAULT 'Operator',
    `nama_pengguna` VARCHAR(100) NULL,
    `no_kontak` VARCHAR(20) NULL,
    `npsn` VARCHAR(8) NULL,
    `nama_sekolah` VARCHAR(150) NULL,
    `alamat` TEXT NULL,
    `status` ENUM('Aktif', 'Nonaktif') NOT NULL DEFAULT 'Aktif',
    `failed_login_attempts` INT NOT NULL DEFAULT 0,
    `locked_until` TIMESTAMP NULL,
    `last_login_at` TIMESTAMP NULL,
    `remember_token` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_npsn` (`npsn`),
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: mutasi_keluar
-- =====================================================
CREATE TABLE IF NOT EXISTS `mutasi_keluar` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_mutasi` VARCHAR(50) NOT NULL UNIQUE,
    `tanggal_pengajuan` DATE NOT NULL,
    `nama_murid` VARCHAR(100) NOT NULL,
    `nisn` VARCHAR(10) NOT NULL,
    `nis` VARCHAR(20) NULL,
    `tempat_lahir` VARCHAR(50) NULL,
    `tanggal_lahir` DATE NULL,
    `jenis_kelamin` ENUM('L', 'P') NULL,
    `kelas` VARCHAR(10) NULL,
    `nama_sekolah_asal` VARCHAR(150) NOT NULL,
    `npsn_sekolah_asal` VARCHAR(8) NOT NULL,
    `alamat_sekolah_asal` TEXT NULL,
    `npsn_sekolah_tujuan` VARCHAR(8) NOT NULL,
    `nama_sekolah_tujuan` VARCHAR(150) NOT NULL,
    `alamat_sekolah_tujuan` TEXT NULL,
    `kabupaten_kota_tujuan` VARCHAR(100) NULL,
    `provinsi_tujuan` VARCHAR(100) NULL,
    `alasan_pindah` TEXT NULL,
    `nomor_surat_sekolah` VARCHAR(50) NULL,
    `tanggal_surat_sekolah` DATE NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Menunggu Verifikasi Dinas',
    `pengusul` VARCHAR(50) NOT NULL,
    `file_suket_keluar` VARCHAR(255) NULL,
    `file_suket_dapo` VARCHAR(255) NULL,
    `file_suket_diterima` VARCHAR(255) NULL,
    `file_rapor` VARCHAR(255) NULL,
    `ceklis_surat_sekolah` TINYINT(1) NOT NULL DEFAULT 0,
    `ceklis_berkas_dapodik` TINYINT(1) NOT NULL DEFAULT 0,
    `ceklis_sekolah_tujuan` TINYINT(1) NOT NULL DEFAULT 0,
    `ceklis_rapor` TINYINT(1) NOT NULL DEFAULT 0,
    `catatan_berkas` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_mutasi_status` (`status`),
    INDEX `idx_mutasi_npsn_asal` (`npsn_sekolah_asal`),
    INDEX `idx_mutasi_pengusul` (`pengusul`),
    INDEX `idx_mutasi_tanggal` (`tanggal_pengajuan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: penandatangan
-- =====================================================
CREATE TABLE IF NOT EXISTS `penandatangan` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `nama` VARCHAR(100) NOT NULL,
    `nip` VARCHAR(30) NULL,
    `jabatan` VARCHAR(100) NOT NULL,
    `aktif` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: audit_logs
-- =====================================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `event_type` VARCHAR(50) NOT NULL,
    `username` VARCHAR(50) NULL,
    `ip_address` VARCHAR(45) NULL,
    `details` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_event` (`event_type`),
    INDEX `idx_audit_user` (`username`),
    INDEX `idx_audit_time` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: sessions (for Laravel session driver)
-- =====================================================
CREATE TABLE IF NOT EXISTS `sessions` (
    `id` VARCHAR(255) NOT NULL PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `payload` LONGTEXT NOT NULL,
    `last_activity` INT NOT NULL,
    INDEX `idx_sessions_user` (`user_id`),
    INDEX `idx_sessions_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: cache (for Laravel cache driver)
-- =====================================================
CREATE TABLE IF NOT EXISTS `cache` (
    `key` VARCHAR(255) NOT NULL PRIMARY KEY,
    `value` MEDIUMTEXT NOT NULL,
    `expiration` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
    `key` VARCHAR(255) NOT NULL PRIMARY KEY,
    `owner` VARCHAR(255) NOT NULL,
    `expiration` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: migrations (Laravel tracking)
-- =====================================================
CREATE TABLE IF NOT EXISTS `migrations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `migration` VARCHAR(255) NOT NULL,
    `batch` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert migration records
INSERT INTO `migrations` (`migration`, `batch`) VALUES
('2024_01_01_000001_create_users_table', 1),
('2024_01_01_000002_create_mutasi_keluar_table', 1),
('2024_01_01_000003_create_penandatangan_table', 1);

SET FOREIGN_KEY_CHECKS=1;

-- =====================================================
-- INSERT DEFAULT USERS
-- =====================================================
INSERT INTO `users` (`username`, `password`, `role`, `nama_pengguna`, `status`) VALUES
('admin', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxHBBBAMaKglwLwWKFGGMgQ3TKa', 'SuperAdmin', 'Administrator', 'Aktif'),
('dinas', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dinas', 'Petugas Dinas', 'Aktif'),
('operator1', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operator', 'Operator SDN 1', 'Aktif');

-- =====================================================
-- SELESAI!
-- Login dengan:
--   admin / Admin123!
--   dinas / password
--   operator1 / password
-- =====================================================