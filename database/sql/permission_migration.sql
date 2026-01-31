-- =====================================================
-- SQL untuk Migrasi Permission System
-- Jalankan di Aiven MySQL Console
-- =====================================================

-- 1. Tabel permissions
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `display_name` varchar(150) NOT NULL,
    `group` varchar(50) NOT NULL,
    `description` text DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `permissions_name_unique` (`name`),
    KEY `permissions_group_index` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel role_permissions (Default per role)
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `role` varchar(50) NOT NULL,
    `permission_id` bigint(20) unsigned NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `role_permissions_role_permission_id_unique` (`role`, `permission_id`),
    KEY `role_permissions_role_index` (`role`),
    KEY `role_permissions_permission_id_foreign` (`permission_id`),
    CONSTRAINT `role_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel user_permissions (Override per user)
CREATE TABLE IF NOT EXISTS `user_permissions` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) unsigned NOT NULL,
    `permission_id` bigint(20) unsigned NOT NULL,
    `is_granted` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_permissions_user_id_permission_id_unique` (`user_id`, `permission_id`),
    KEY `user_permissions_permission_id_foreign` (`permission_id`),
    CONSTRAINT `user_permissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `user_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Insert Data Permissions
-- =====================================================

INSERT INTO `permissions` (`name`, `display_name`, `group`, `created_at`, `updated_at`) VALUES
-- Dashboard
('dashboard.view', 'Akses Dashboard', 'Dashboard', NOW(), NOW()),
-- Mutasi Masuk
('mutasi_masuk.view', 'Lihat Mutasi Masuk', 'Mutasi Masuk', NOW(), NOW()),
('mutasi_masuk.create', 'Buat Pengajuan Mutasi Masuk', 'Mutasi Masuk', NOW(), NOW()),
('mutasi_masuk.verify', 'Verifikasi Mutasi Masuk', 'Mutasi Masuk', NOW(), NOW()),
-- Mutasi Keluar
('mutasi_keluar.view', 'Lihat Mutasi Keluar', 'Mutasi Keluar', NOW(), NOW()),
('mutasi_keluar.create', 'Buat Pengajuan Mutasi Keluar', 'Mutasi Keluar', NOW(), NOW()),
('mutasi_keluar.verify', 'Verifikasi Mutasi Keluar', 'Mutasi Keluar', NOW(), NOW()),
-- Laporan
('laporan.view', 'Lihat Laporan', 'Laporan', NOW(), NOW()),
('laporan.export', 'Export Laporan (Excel/PDF)', 'Laporan', NOW(), NOW()),
('laporan.print', 'Cetak Laporan Komprehensif', 'Laporan', NOW(), NOW()),
-- Pengguna
('pengguna.view', 'Lihat Daftar Pengguna', 'Pengguna', NOW(), NOW()),
('pengguna.create', 'Tambah Pengguna', 'Pengguna', NOW(), NOW()),
('pengguna.edit', 'Edit Pengguna', 'Pengguna', NOW(), NOW()),
('pengguna.delete', 'Hapus Pengguna', 'Pengguna', NOW(), NOW()),
('pengguna.permissions', 'Kelola Permission User', 'Pengguna', NOW(), NOW()),
-- Sekolah
('sekolah.view', 'Lihat Data Sekolah', 'Sekolah', NOW(), NOW()),
('sekolah.edit', 'Edit Profil Sekolah', 'Sekolah', NOW(), NOW()),
-- Admin
('admin.audit_log', 'Lihat Audit Log', 'Admin', NOW(), NOW()),
('admin.settings', 'Kelola Pengaturan Sistem', 'Admin', NOW(), NOW());

-- =====================================================
-- Insert Default Role Permissions
-- =====================================================

-- Dinas defaults
INSERT INTO `role_permissions` (`role`, `permission_id`, `created_at`, `updated_at`)
SELECT 'Dinas', id, NOW(), NOW() FROM `permissions` WHERE `name` IN (
    'dashboard.view', 'mutasi_masuk.view', 'mutasi_masuk.verify',
    'mutasi_keluar.view', 'mutasi_keluar.verify',
    'laporan.view', 'laporan.export', 'laporan.print',
    'pengguna.view', 'pengguna.create', 'pengguna.edit', 'pengguna.delete',
    'sekolah.view', 'admin.audit_log'
);

-- Sekolah defaults
INSERT INTO `role_permissions` (`role`, `permission_id`, `created_at`, `updated_at`)
SELECT 'Sekolah', id, NOW(), NOW() FROM `permissions` WHERE `name` IN (
    'dashboard.view', 'mutasi_masuk.view', 'mutasi_masuk.create',
    'mutasi_keluar.view', 'mutasi_keluar.create',
    'laporan.view', 'laporan.export', 'laporan.print',
    'pengguna.view', 'pengguna.create', 'pengguna.edit',
    'sekolah.view', 'sekolah.edit'
);

-- Operator defaults
INSERT INTO `role_permissions` (`role`, `permission_id`, `created_at`, `updated_at`)
SELECT 'Operator', id, NOW(), NOW() FROM `permissions` WHERE `name` IN (
    'dashboard.view', 'mutasi_masuk.view', 'mutasi_masuk.create',
    'mutasi_keluar.view', 'mutasi_keluar.create',
    'laporan.view', 'sekolah.view'
);

-- =====================================================
-- Verifikasi
-- =====================================================
SELECT 'Permissions created:' as info, COUNT(*) as total FROM permissions;
SELECT 'Role permissions set:' as info, role, COUNT(*) as total FROM role_permissions GROUP BY role;
