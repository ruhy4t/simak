-- =============================================================
-- SIMAK - Penanda Data Akhir yang Dikunci Terlalu Awal
-- Target database: MySQL 8+ / MariaDB 10.5+
-- Aman diimpor ulang (idempotent).
--
-- Cara menjalankan di HeidiSQL/phpMyAdmin:
-- 1. Pilih database SIMAK yang benar.
-- 2. Buat backup database.
-- 3. Impor dan jalankan seluruh file ini.
--
-- Dampak:
-- - Menambah satu kolom boolean berukuran kecil.
-- - Tidak menghapus atau mengubah angka Data Awal/Data Akhir.
-- - Hanya menandai Data Akhir tahun aktif yang dikunci sebelum
--   tanggal akhir tahun pelajaran.
-- =============================================================

SET NAMES utf8mb4;

SET @student_counts_table_exists := (
    SELECT COUNT(*)
    FROM `INFORMATION_SCHEMA`.`TABLES`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'satuan_pendidikan_student_counts'
);

SET @academic_years_table_exists := (
    SELECT COUNT(*)
    FROM `INFORMATION_SCHEMA`.`TABLES`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'academic_years'
);

SET @closing_confirmed_column_exists := (
    SELECT COUNT(*)
    FROM `INFORMATION_SCHEMA`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'satuan_pendidikan_student_counts'
      AND `COLUMN_NAME` = 'closing_confirmed_at'
);

SET @early_lock_column_exists := (
    SELECT COUNT(*)
    FROM `INFORMATION_SCHEMA`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'satuan_pendidikan_student_counts'
      AND `COLUMN_NAME` = 'closing_locked_early'
);

SET @add_early_lock_column_sql := IF(
    @student_counts_table_exists = 0,
    'SELECT ''GAGAL: tabel satuan_pendidikan_student_counts belum tersedia'' AS hasil',
    IF(
        @closing_confirmed_column_exists = 0,
        'SELECT ''GAGAL: kolom closing_confirmed_at belum tersedia'' AS hasil',
        IF(
            @early_lock_column_exists = 0,
            'ALTER TABLE `satuan_pendidikan_student_counts` ADD COLUMN `closing_locked_early` TINYINT(1) NOT NULL DEFAULT 0 AFTER `closing_confirmed_at`',
            'SELECT ''OK: kolom closing_locked_early sudah tersedia'' AS hasil'
        )
    )
);

PREPARE add_early_lock_column_statement FROM @add_early_lock_column_sql;
EXECUTE add_early_lock_column_statement;
DEALLOCATE PREPARE add_early_lock_column_statement;

-- Baca ulang status kolom setelah ALTER TABLE.
SET @early_lock_column_exists := (
    SELECT COUNT(*)
    FROM `INFORMATION_SCHEMA`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'satuan_pendidikan_student_counts'
      AND `COLUMN_NAME` = 'closing_locked_early'
);

-- Tandai hanya data tahun yang saat ini aktif dan dikunci sebelum
-- tanggal penutupannya. Angka siswa tidak disentuh.
SET @mark_early_locks_sql := IF(
    @academic_years_table_exists = 1
        AND @early_lock_column_exists = 1,
    'UPDATE `satuan_pendidikan_student_counts` AS counts
     INNER JOIN `academic_years` AS years
        ON years.id = counts.academic_year_id
     SET counts.closing_locked_early = 1,
         counts.updated_at = counts.updated_at
     WHERE years.is_active = 1
       AND years.ends_on IS NOT NULL
       AND counts.closing_confirmed_at IS NOT NULL
       AND counts.closing_confirmed_at < years.ends_on',
    'SELECT ''GAGAL: tabel academic_years atau kolom closing_locked_early belum tersedia'' AS hasil'
);

PREPARE mark_early_locks_statement FROM @mark_early_locks_sql;
EXECUTE mark_early_locks_statement;
DEALLOCATE PREPARE mark_early_locks_statement;

-- Verifikasi struktur kolom.
SELECT
    `COLUMN_NAME` AS `kolom`,
    `COLUMN_TYPE` AS `tipe`,
    `IS_NULLABLE` AS `dapat_null`,
    `COLUMN_DEFAULT` AS `nilai_awal`
FROM `INFORMATION_SCHEMA`.`COLUMNS`
WHERE `TABLE_SCHEMA` = DATABASE()
  AND `TABLE_NAME` = 'satuan_pendidikan_student_counts'
  AND `COLUMN_NAME` = 'closing_locked_early';

-- Ringkasan data yang ditandai. Tidak menampilkan data pribadi murid.
SET @early_lock_summary_sql := IF(
    @early_lock_column_exists = 1,
    'SELECT
        COUNT(DISTINCT `satuan_pendidikan_id`) AS `jumlah_sekolah_ditandai`,
        COUNT(*) AS `jumlah_baris_ditandai`
     FROM `satuan_pendidikan_student_counts`
     WHERE `closing_locked_early` = 1',
    'SELECT 0 AS jumlah_sekolah_ditandai, 0 AS jumlah_baris_ditandai'
);

PREPARE early_lock_summary_statement FROM @early_lock_summary_sql;
EXECUTE early_lock_summary_statement;
DEALLOCATE PREPARE early_lock_summary_statement;

-- Rollback manual jika benar-benar diperlukan:
-- ALTER TABLE `satuan_pendidikan_student_counts`
--     DROP COLUMN `closing_locked_early`;
