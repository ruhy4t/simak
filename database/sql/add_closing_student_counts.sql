-- =============================================================
-- SIMAK - Data Akhir Aktual dan Rekonsiliasi Kohort Agregat
-- Target database: MySQL 8+ / MariaDB 10.5+
-- Aman diimpor ulang (idempotent).
--
-- Cara menjalankan di HeidiSQL:
-- 1. Pilih database SIMAK yang benar.
-- 2. Buat backup database.
-- 3. Buka tab Query, muat file ini, lalu jalankan seluruh skrip.
--
-- Prasyarat:
-- Tabel `satuan_pendidikan_student_counts` dan kolom
-- `confirmed_at` sudah tersedia.
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

-- 1. Jumlah siswa laki-laki pada akhir tahun.
SET @closing_male_column_exists := (
    SELECT COUNT(*)
    FROM `INFORMATION_SCHEMA`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'satuan_pendidikan_student_counts'
      AND `COLUMN_NAME` = 'closing_male_count'
);

SET @add_closing_male_sql := IF(
    @student_counts_table_exists = 0,
    'SELECT ''GAGAL: tabel satuan_pendidikan_student_counts belum tersedia'' AS hasil',
    IF(
        @confirmed_at_column_exists = 0,
        'SELECT ''GAGAL: kolom confirmed_at belum tersedia. Jalankan add_student_counts_confirmation.sql lebih dahulu'' AS hasil',
        IF(
            @closing_male_column_exists = 0,
            'ALTER TABLE `satuan_pendidikan_student_counts` ADD COLUMN `closing_male_count` INT UNSIGNED NULL DEFAULT NULL AFTER `confirmed_at`',
            'SELECT ''OK: kolom closing_male_count sudah tersedia'' AS hasil'
        )
    )
);

PREPARE add_closing_male_statement FROM @add_closing_male_sql;
EXECUTE add_closing_male_statement;
DEALLOCATE PREPARE add_closing_male_statement;

-- 2. Jumlah siswa perempuan pada akhir tahun.
SET @closing_female_column_exists := (
    SELECT COUNT(*)
    FROM `INFORMATION_SCHEMA`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'satuan_pendidikan_student_counts'
      AND `COLUMN_NAME` = 'closing_female_count'
);

SET @add_closing_female_sql := IF(
    @closing_male_column_exists = 0
        AND NOT EXISTS (
            SELECT 1
            FROM `INFORMATION_SCHEMA`.`COLUMNS`
            WHERE `TABLE_SCHEMA` = DATABASE()
              AND `TABLE_NAME` = 'satuan_pendidikan_student_counts'
              AND `COLUMN_NAME` = 'closing_male_count'
        ),
    'SELECT ''GAGAL: kolom closing_male_count belum tersedia'' AS hasil',
    IF(
        @closing_female_column_exists = 0,
        'ALTER TABLE `satuan_pendidikan_student_counts` ADD COLUMN `closing_female_count` INT UNSIGNED NULL DEFAULT NULL AFTER `closing_male_count`',
        'SELECT ''OK: kolom closing_female_count sudah tersedia'' AS hasil'
    )
);

PREPARE add_closing_female_statement FROM @add_closing_female_sql;
EXECUTE add_closing_female_statement;
DEALLOCATE PREPARE add_closing_female_statement;

-- 3. Waktu konfirmasi sekaligus penanda bahwa data sudah dikunci.
SET @closing_confirmed_column_exists := (
    SELECT COUNT(*)
    FROM `INFORMATION_SCHEMA`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'satuan_pendidikan_student_counts'
      AND `COLUMN_NAME` = 'closing_confirmed_at'
);

SET @add_closing_confirmed_sql := IF(
    NOT EXISTS (
        SELECT 1
        FROM `INFORMATION_SCHEMA`.`COLUMNS`
        WHERE `TABLE_SCHEMA` = DATABASE()
          AND `TABLE_NAME` = 'satuan_pendidikan_student_counts'
          AND `COLUMN_NAME` = 'closing_female_count'
    ),
    'SELECT ''GAGAL: kolom closing_female_count belum tersedia'' AS hasil',
    IF(
        @closing_confirmed_column_exists = 0,
        'ALTER TABLE `satuan_pendidikan_student_counts` ADD COLUMN `closing_confirmed_at` TIMESTAMP NULL DEFAULT NULL AFTER `closing_female_count`',
        'SELECT ''OK: kolom closing_confirmed_at sudah tersedia'' AS hasil'
    )
);

PREPARE add_closing_confirmed_statement FROM @add_closing_confirmed_sql;
EXECUTE add_closing_confirmed_statement;
DEALLOCATE PREPARE add_closing_confirmed_statement;

-- Verifikasi hasil. Ketiga kolom harus tampil.
SELECT
    `COLUMN_NAME` AS `kolom`,
    `COLUMN_TYPE` AS `tipe`,
    `IS_NULLABLE` AS `dapat_null`,
    `COLUMN_DEFAULT` AS `nilai_awal`
FROM `INFORMATION_SCHEMA`.`COLUMNS`
WHERE `TABLE_SCHEMA` = DATABASE()
  AND `TABLE_NAME` = 'satuan_pendidikan_student_counts'
  AND `COLUMN_NAME` IN (
      'closing_male_count',
      'closing_female_count',
      'closing_confirmed_at'
  )
ORDER BY `ORDINAL_POSITION`;

-- Catatan perhitungan aplikasi:
-- perkiraan_akhir = data_awal + mutasi_masuk_selesai
--                    - mutasi_keluar_selesai
--                    - siswa_keluar_non_mutasi_tercatat
-- selisih_keluar_belum_terdata = GREATEST(0, perkiraan_akhir - data_akhir_aktual)
--
-- Data individual keluar non-mutasi disimpan pada tabel
-- `non_mutation_student_exits`. Selisih yang belum terdata tetap dihitung
-- dinamis dan tidak disimpan sebagai kolom terpisah.

-- Rollback manual jika benar-benar diperlukan dan belum ada data penting:
-- ALTER TABLE `satuan_pendidikan_student_counts`
--     DROP COLUMN `closing_confirmed_at`,
--     DROP COLUMN `closing_female_count`,
--     DROP COLUMN `closing_male_count`;
