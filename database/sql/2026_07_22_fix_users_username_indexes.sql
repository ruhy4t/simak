-- SIMAK: izinkan username yang sama pada NPSN berbeda.
-- Target: MySQL / MariaDB.
-- Script ini idempotent dan aman dijalankan kembali.

SET @simak_schema = DATABASE();

-- Hapus semua UNIQUE index lama yang hanya mencakup kolom username,
-- termasuk index bernama `username` maupun `users_username_unique`.
SELECT GROUP_CONCAT(
    CONCAT('DROP INDEX `', REPLACE(single_indexes.index_name, '`', '``'), '`')
    SEPARATOR ', '
)
INTO @simak_drop_global_username
FROM (
    SELECT index_name
    FROM information_schema.statistics
    WHERE table_schema = @simak_schema
      AND table_name = 'users'
      AND non_unique = 0
      AND index_name <> 'PRIMARY'
    GROUP BY index_name
    HAVING COUNT(*) = 1
       AND MAX(column_name = 'username') = 1
) AS single_indexes;

SET @simak_sql = IF(
    @simak_drop_global_username IS NULL,
    'SELECT ''Unique index global username sudah tidak ada'' AS info',
    CONCAT('ALTER TABLE `users` ', @simak_drop_global_username)
);

PREPARE simak_statement FROM @simak_sql;
EXECUTE simak_statement;
DEALLOCATE PREPARE simak_statement;

-- Tambahkan UNIQUE index gabungan bila belum tersedia.
SELECT COUNT(*)
INTO @simak_has_scoped_username
FROM (
    SELECT index_name
    FROM information_schema.statistics
    WHERE table_schema = @simak_schema
      AND table_name = 'users'
      AND non_unique = 0
      AND index_name <> 'PRIMARY'
    GROUP BY index_name
    HAVING GROUP_CONCAT(column_name ORDER BY seq_in_index SEPARATOR ',') = 'username,npsn'
) AS scoped_indexes;

SET @simak_sql = IF(
    @simak_has_scoped_username > 0,
    'SELECT ''Unique index username + NPSN sudah tersedia'' AS info',
    'ALTER TABLE `users` ADD UNIQUE INDEX `users_username_npsn_unique` (`username`, `npsn`)'
);

PREPARE simak_statement FROM @simak_sql;
EXECUTE simak_statement;
DEALLOCATE PREPARE simak_statement;

-- Tampilkan index akhir untuk verifikasi setelah import.
SHOW INDEX FROM `users`;
