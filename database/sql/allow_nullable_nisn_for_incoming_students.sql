-- IMPORT LANGSUNG HEIDISQL (MySQL/MariaDB). Pilih database aplikasi dahulu.
-- Aman dijalankan berulang kali secara berurutan, tanpa stored procedure.
-- Tidak menghapus atau mengganti data murid.

SET @incoming_nisn_needs_change = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mutasi_masuk_siswa'
      AND COLUMN_NAME = 'nisn' AND DATA_TYPE = 'varchar'
      AND CHARACTER_MAXIMUM_LENGTH = 10 AND IS_NULLABLE = 'NO'
);
SET @incoming_nisn_sql = IF(@incoming_nisn_needs_change = 1,
    'ALTER TABLE `mutasi_masuk_siswa` MODIFY COLUMN `nisn` VARCHAR(10) NULL DEFAULT NULL',
    'SELECT ''ALTER dilewati: kolom sudah nullable atau struktur perlu diperiksa'' AS informasi'
);
PREPARE incoming_nisn_statement FROM @incoming_nisn_sql;
EXECUTE incoming_nisn_statement;
DEALLOCATE PREPARE incoming_nisn_statement;

-- Catat migrasi hanya jika struktur sebenarnya sudah sesuai.
-- Jika ALTER gagal dan aplikasi melanjutkan skrip, migrasi tidak dicatat.
SET @incoming_nisn_ready = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mutasi_masuk_siswa'
      AND COLUMN_NAME = 'nisn' AND DATA_TYPE = 'varchar'
      AND CHARACTER_MAXIMUM_LENGTH = 10 AND IS_NULLABLE = 'YES'
);
SET @incoming_nisn_batch = (
    SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`
);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_07_000001_allow_nullable_nisn_for_incoming_students', @incoming_nisn_batch
WHERE @incoming_nisn_ready = 1 AND NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_09_07_000001_allow_nullable_nisn_for_incoming_students'
);

SELECT DATABASE() AS database_aktif,
    CASE WHEN @incoming_nisn_ready = 1 AND (
        SELECT COUNT(*) FROM `migrations`
        WHERE `migration` = '2026_09_07_000001_allow_nullable_nisn_for_incoming_students'
    ) = 1
    THEN 'BERHASIL: NISN boleh kosong dan migrasi sudah tercatat'
    ELSE 'PERIKSA: database, struktur tabel, izin akses, atau catatan migrasi tidak sesuai'
    END AS hasil_migrasi;
