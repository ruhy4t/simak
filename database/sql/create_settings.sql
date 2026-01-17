<<<<<<< HEAD
-- =============================================
-- SIMAK Settings Table - Simplified
-- Only Working Days and Hours
-- =============================================

-- Drop existing table first
DROP TABLE IF EXISTS `settings`;

CREATE TABLE `settings` (
    `key` VARCHAR(100) PRIMARY KEY,
    `value` TEXT NULL,
    `type` VARCHAR(20) DEFAULT 'string',
    `label` VARCHAR(255) NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert settings
INSERT INTO `settings` (`key`, `value`, `type`, `label`, `description`, `created_at`, `updated_at`) VALUES
('allowed_days', '1,2,3,4,5,6', 'json', 'Hari yang Diperbolehkan', 'Hari-hari dimana pengajuan mutasi diizinkan (0=Minggu, 1=Senin, ..., 6=Sabtu)', NOW(), NOW()),
('enable_hour_restriction', '0', 'boolean', 'Aktifkan Pembatasan Jam', 'Jika aktif, pengajuan hanya bisa pada jam yang ditentukan', NOW(), NOW()),
('working_hours_start', '08:00', 'string', 'Jam Mulai', 'Jam mulai operasional', NOW(), NOW()),
('working_hours_end', '16:00', 'string', 'Jam Selesai', 'Jam selesai operasional', NOW(), NOW());
=======
-- =============================================
-- SIMAK Settings Table - Simplified
-- Only Working Days and Hours
-- =============================================

-- Drop existing table first
DROP TABLE IF EXISTS `settings`;

CREATE TABLE `settings` (
    `key` VARCHAR(100) PRIMARY KEY,
    `value` TEXT NULL,
    `type` VARCHAR(20) DEFAULT 'string',
    `label` VARCHAR(255) NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert settings
INSERT INTO `settings` (`key`, `value`, `type`, `label`, `description`, `created_at`, `updated_at`) VALUES
('allowed_days', '1,2,3,4,5,6', 'json', 'Hari yang Diperbolehkan', 'Hari-hari dimana pengajuan mutasi diizinkan (0=Minggu, 1=Senin, ..., 6=Sabtu)', NOW(), NOW()),
('enable_hour_restriction', '0', 'boolean', 'Aktifkan Pembatasan Jam', 'Jika aktif, pengajuan hanya bisa pada jam yang ditentukan', NOW(), NOW()),
('working_hours_start', '08:00', 'string', 'Jam Mulai', 'Jam mulai operasional', NOW(), NOW()),
('working_hours_end', '16:00', 'string', 'Jam Selesai', 'Jam selesai operasional', NOW(), NOW());
>>>>>>> 9466a07 (Upload project pertama kali)
