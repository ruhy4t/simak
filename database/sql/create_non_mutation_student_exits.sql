-- =============================================================
-- SIMAK 2.2.0 - Pendataan Murid Keluar Non-Mutasi per Individu
-- Target database: MySQL 8+ / MariaDB 10.5+
-- Aman diimpor ulang.
--
-- Sebelum menjalankan di HeidiSQL:
-- 1. Pilih database SIMAK yang benar.
-- 2. Buat backup database.
-- 3. Pastikan tabel satuan_pendidikan, academic_years, dan users tersedia.
-- 4. Jalankan seluruh skrip ini.
-- =============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `non_mutation_student_exits` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `satuan_pendidikan_id` BIGINT UNSIGNED NOT NULL,
    `academic_year_id` BIGINT UNSIGNED NOT NULL,
    `student_name` VARCHAR(150) NOT NULL,
    `nisn` VARCHAR(10) NOT NULL,
    `grade` TINYINT UNSIGNED NOT NULL,
    `gender` CHAR(1) NOT NULL,
    `exit_date` DATE NOT NULL,
    `reason` VARCHAR(50) NOT NULL DEFAULT 'Tanpa keterangan',
    `notes` TEXT NULL,
    `reported_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `non_mutation_exit_school_year_nisn_unique` (
        `satuan_pendidikan_id`, `academic_year_id`, `nisn`
    ),
    KEY `non_mutation_exit_year_grade_index` (`academic_year_id`, `grade`),
    KEY `non_mutation_exit_school_index` (`satuan_pendidikan_id`),
    KEY `non_mutation_exit_date_index` (`exit_date`),
    KEY `non_mutation_exit_reported_by_index` (`reported_by`),
    CONSTRAINT `non_mutation_exit_school_fk`
        FOREIGN KEY (`satuan_pendidikan_id`)
        REFERENCES `satuan_pendidikan` (`id`) ON DELETE CASCADE,
    CONSTRAINT `non_mutation_exit_academic_year_fk`
        FOREIGN KEY (`academic_year_id`)
        REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
    CONSTRAINT `non_mutation_exit_reported_by_fk`
        FOREIGN KEY (`reported_by`)
        REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `non_mutation_exit_grade_check` CHECK (`grade` IN (7, 8, 9)),
    CONSTRAINT `non_mutation_exit_gender_check` CHECK (`gender` IN ('L', 'P'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Upgrade aman jika tabel sempat dibuat memakai versi awal yang
-- masih mengizinkan NISN kosong. ALTER hanya dijalankan jika tidak
-- ada baris lama dengan NISN NULL atau kosong.
SET @empty_nisn_count := (
    SELECT COUNT(*)
    FROM `non_mutation_student_exits`
    WHERE `nisn` IS NULL OR TRIM(`nisn`) = ''
);

SET @nisn_is_nullable := (
    SELECT `IS_NULLABLE`
    FROM `INFORMATION_SCHEMA`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'non_mutation_student_exits'
      AND `COLUMN_NAME` = 'nisn'
    LIMIT 1
);

SET @require_nisn_sql := IF(
    @empty_nisn_count > 0,
    'SELECT ''GAGAL: lengkapi NISN pada data lama yang masih kosong, lalu jalankan ulang skrip ini'' AS hasil',
    IF(
        @nisn_is_nullable = 'YES',
        'ALTER TABLE `non_mutation_student_exits` MODIFY COLUMN `nisn` VARCHAR(10) NOT NULL',
        'SELECT ''OK: NISN sudah wajib diisi'' AS hasil'
    )
);

PREPARE require_nisn_statement FROM @require_nisn_sql;
EXECUTE require_nisn_statement;
DEALLOCATE PREPARE require_nisn_statement;

-- Verifikasi tabel dan kolom NISN. IS_NULLABLE harus bernilai NO.
SELECT
    `COLUMN_NAME` AS `kolom`,
    `COLUMN_TYPE` AS `tipe`,
    `IS_NULLABLE` AS `dapat_null`,
    `COLUMN_DEFAULT` AS `nilai_awal`
FROM `INFORMATION_SCHEMA`.`COLUMNS`
WHERE `TABLE_SCHEMA` = DATABASE()
  AND `TABLE_NAME` = 'non_mutation_student_exits'
ORDER BY `ORDINAL_POSITION`;

-- Data langsung aktif setelah disimpan sekolah. Tidak ada proses
-- verifikasi atau persetujuan Dinas. Dinas hanya dapat melihat data.

-- Rollback manual jika benar-benar diperlukan:
-- DROP TABLE `non_mutation_student_exits`;
