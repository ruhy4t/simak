-- =============================================================
-- SIMAK - Migrasi Manual Tahun Pelajaran dan Data Siswa Tahunan
-- Target database: MySQL 8+ / MariaDB 10.5+
-- Dibuat agar aman diimpor ulang (idempotent).
--
-- Sebelum menjalankan:
-- 1. Pilih database SIMAK yang benar.
-- 2. Buat backup database.
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- 1. Referensi tahun pelajaran
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `academic_years` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(9) NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'upcoming',
    `is_active` TINYINT(1) NOT NULL DEFAULT 0,
    `starts_on` DATE NULL,
    `ends_on` DATE NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `academic_years_name_unique` (`name`),
    KEY `academic_years_status_index` (`status`),
    KEY `academic_years_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tambahkan 2025/2026 sebagai tahun awal jika belum tersedia.
-- Jika sudah ada tahun aktif lain, 2025/2026 dibuat sebagai tahun ditutup.
INSERT INTO `academic_years` (
    `name`,
    `status`,
    `is_active`,
    `starts_on`,
    `ends_on`,
    `created_at`,
    `updated_at`
)
SELECT
    '2025/2026',
    CASE
        WHEN EXISTS (SELECT 1 FROM `academic_years` WHERE `is_active` = 1)
            THEN 'closed'
        ELSE 'active'
    END,
    CASE
        WHEN EXISTS (SELECT 1 FROM `academic_years` WHERE `is_active` = 1)
            THEN 0
        ELSE 1
    END,
    '2025-07-01',
    '2026-06-30',
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `academic_years` WHERE `name` = '2025/2026'
);

-- -------------------------------------------------------------
-- 2. Data siswa per sekolah, tahun pelajaran, dan kelas
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `satuan_pendidikan_student_counts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `satuan_pendidikan_id` BIGINT UNSIGNED NOT NULL,
    `academic_year_id` BIGINT UNSIGNED NOT NULL,
    `grade` TINYINT UNSIGNED NOT NULL,
    `male_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `female_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `school_year_grade_unique` (
        `satuan_pendidikan_id`,
        `academic_year_id`,
        `grade`
    ),
    KEY `student_counts_year_grade_index` (`academic_year_id`, `grade`),
    CONSTRAINT `student_counts_school_fk`
        FOREIGN KEY (`satuan_pendidikan_id`)
        REFERENCES `satuan_pendidikan` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `student_counts_academic_year_fk`
        FOREIGN KEY (`academic_year_id`)
        REFERENCES `academic_years` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @academic_year_2025_id := (
    SELECT `id`
    FROM `academic_years`
    WHERE `name` = '2025/2026'
    LIMIT 1
);

-- -------------------------------------------------------------
-- 3. Backfill data lama ke 2025/2026
-- INSERT IGNORE memastikan data tahunan yang sudah ada tidak ditimpa.
-- -------------------------------------------------------------
INSERT IGNORE INTO `satuan_pendidikan_student_counts` (
    `satuan_pendidikan_id`,
    `academic_year_id`,
    `grade`,
    `male_count`,
    `female_count`,
    `created_at`,
    `updated_at`
)
SELECT
    `id`,
    @academic_year_2025_id,
    7,
    COALESCE(`jumlah_siswa_7_l`, 0),
    COALESCE(`jumlah_siswa_7_p`, 0),
    NOW(),
    NOW()
FROM `satuan_pendidikan`

UNION ALL

SELECT
    `id`,
    @academic_year_2025_id,
    8,
    COALESCE(`jumlah_siswa_8_l`, 0),
    COALESCE(`jumlah_siswa_8_p`, 0),
    NOW(),
    NOW()
FROM `satuan_pendidikan`

UNION ALL

SELECT
    `id`,
    @academic_year_2025_id,
    9,
    COALESCE(`jumlah_siswa_9_l`, 0),
    COALESCE(`jumlah_siswa_9_p`, 0),
    NOW(),
    NOW()
FROM `satuan_pendidikan`;

SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------
-- 4. Hasil pemeriksaan
-- Setelah impor berhasil, hasil pertama harus memuat 2025/2026.
-- Jumlah baris data siswa idealnya: jumlah sekolah x 3 kelas.
-- -------------------------------------------------------------
SELECT
    `id`,
    `name`,
    `status`,
    `is_active`,
    `starts_on`,
    `ends_on`
FROM `academic_years`
ORDER BY `name` DESC;

SELECT
    COUNT(*) AS `jumlah_baris_data_siswa_tahunan`,
    COUNT(DISTINCT `satuan_pendidikan_id`) AS `jumlah_sekolah_terpetakan`
FROM `satuan_pendidikan_student_counts`
WHERE `academic_year_id` = @academic_year_2025_id;

-- Selesai. Buka menu Pengaturan SIMAK untuk menambahkan atau
-- mengaktifkan tahun pelajaran berikutnya.
