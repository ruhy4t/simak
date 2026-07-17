-- =============================================================
-- SIMAK - Penanda Konfirmasi Data Siswa Awal
-- Target database: MySQL 8+ / MariaDB 10.5+
-- Aman diimpor ulang (idempotent).
--
-- Prasyarat:
-- Tabel `satuan_pendidikan_student_counts` sudah tersedia.
-- Data lama sengaja tidak dikonfirmasi otomatis. Sekolah harus
-- membuka Profil Sekolah dan menyimpan Data Siswa Awal tahun aktif.
-- =============================================================

SET NAMES utf8mb4;

SET @student_counts_table_exists := (
    SELECT COUNT(*)
    FROM `INFORMATION_SCHEMA`.`TABLES`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'satuan_pendidikan_student_counts'
);

SET @confirmed_at_column_exists := (
    SELECT COUNT(*)
    FROM `INFORMATION_SCHEMA`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'satuan_pendidikan_student_counts'
      AND `COLUMN_NAME` = 'confirmed_at'
);

SET @add_confirmed_at_sql := IF(
    @student_counts_table_exists = 0,
    'SELECT ''GAGAL: tabel satuan_pendidikan_student_counts belum tersedia'' AS hasil',
    IF(
        @confirmed_at_column_exists = 0,
        'ALTER TABLE `satuan_pendidikan_student_counts` ADD COLUMN `confirmed_at` TIMESTAMP NULL DEFAULT NULL AFTER `female_count`',
        'SELECT ''OK: kolom confirmed_at sudah tersedia'' AS hasil'
    )
);

PREPARE add_confirmed_at_statement FROM @add_confirmed_at_sql;
EXECUTE add_confirmed_at_statement;
DEALLOCATE PREPARE add_confirmed_at_statement;

-- Verifikasi hasil migrasi.
SELECT
    `TABLE_NAME` AS tabel,
    `COLUMN_NAME` AS kolom,
    `COLUMN_TYPE` AS tipe,
    `IS_NULLABLE` AS dapat_null,
    `COLUMN_DEFAULT` AS nilai_awal
FROM `INFORMATION_SCHEMA`.`COLUMNS`
WHERE `TABLE_SCHEMA` = DATABASE()
  AND `TABLE_NAME` = 'satuan_pendidikan_student_counts'
  AND `COLUMN_NAME` = 'confirmed_at';

-- Rollback manual jika benar-benar diperlukan:
-- ALTER TABLE `satuan_pendidikan_student_counts` DROP COLUMN `confirmed_at`;
