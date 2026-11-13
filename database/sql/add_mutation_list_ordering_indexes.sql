-- Idempotent indexes for school/operator mutation lists (MySQL/TiDB).
SET @simak_schema = DATABASE();

SELECT COUNT(*)
INTO @simak_has_columns
FROM information_schema.columns
WHERE table_schema = @simak_schema
  AND table_name = 'mutasi_masuk'
  AND column_name IN ('npsn_sekolah_tujuan', 'pengusul', 'last_submitted_at', 'id_mutasi');
SELECT COUNT(*)
INTO @simak_has_index
FROM information_schema.statistics
WHERE table_schema = @simak_schema
  AND table_name = 'mutasi_masuk'
  AND index_name = 'idx_mm_school_list';
SET @simak_sql = IF(
    @simak_has_index > 0,
    'SELECT ''idx_mm_school_list sudah tersedia'' AS info',
    IF(
        @simak_has_columns = 4,
        'ALTER TABLE `mutasi_masuk` ADD INDEX `idx_mm_school_list` (`npsn_sekolah_tujuan`, `last_submitted_at`, `id_mutasi`)',
        'SELECT ''Kolom prasyarat idx_mm_school_list belum tersedia'' AS info'
    )
);
PREPARE simak_stmt FROM @simak_sql;
EXECUTE simak_stmt;
DEALLOCATE PREPARE simak_stmt;

SELECT COUNT(*)
INTO @simak_has_index
FROM information_schema.statistics
WHERE table_schema = @simak_schema
  AND table_name = 'mutasi_masuk'
  AND index_name = 'idx_mm_operator_list';
SET @simak_sql = IF(
    @simak_has_index > 0,
    'SELECT ''idx_mm_operator_list sudah tersedia'' AS info',
    IF(
        @simak_has_columns = 4,
        'ALTER TABLE `mutasi_masuk` ADD INDEX `idx_mm_operator_list` (`pengusul`, `last_submitted_at`, `id_mutasi`)',
        'SELECT ''Kolom prasyarat idx_mm_operator_list belum tersedia'' AS info'
    )
);
PREPARE simak_stmt FROM @simak_sql;
EXECUTE simak_stmt;
DEALLOCATE PREPARE simak_stmt;

SELECT COUNT(*)
INTO @simak_has_columns
FROM information_schema.columns
WHERE table_schema = @simak_schema
  AND table_name = 'mutasi_keluar'
  AND column_name IN ('npsn_sekolah_asal', 'pengusul', 'last_submitted_at', 'id_mutasi');
SELECT COUNT(*)
INTO @simak_has_index
FROM information_schema.statistics
WHERE table_schema = @simak_schema
  AND table_name = 'mutasi_keluar'
  AND index_name = 'idx_mk_school_list';
SET @simak_sql = IF(
    @simak_has_index > 0,
    'SELECT ''idx_mk_school_list sudah tersedia'' AS info',
    IF(
        @simak_has_columns = 4,
        'ALTER TABLE `mutasi_keluar` ADD INDEX `idx_mk_school_list` (`npsn_sekolah_asal`, `last_submitted_at`, `id_mutasi`)',
        'SELECT ''Kolom prasyarat idx_mk_school_list belum tersedia'' AS info'
    )
);
PREPARE simak_stmt FROM @simak_sql;
EXECUTE simak_stmt;
DEALLOCATE PREPARE simak_stmt;

SELECT COUNT(*)
INTO @simak_has_index
FROM information_schema.statistics
WHERE table_schema = @simak_schema
  AND table_name = 'mutasi_keluar'
  AND index_name = 'idx_mk_operator_list';
SET @simak_sql = IF(
    @simak_has_index > 0,
    'SELECT ''idx_mk_operator_list sudah tersedia'' AS info',
    IF(
        @simak_has_columns = 4,
        'ALTER TABLE `mutasi_keluar` ADD INDEX `idx_mk_operator_list` (`pengusul`, `last_submitted_at`, `id_mutasi`)',
        'SELECT ''Kolom prasyarat idx_mk_operator_list belum tersedia'' AS info'
    )
);
PREPARE simak_stmt FROM @simak_sql;
EXECUTE simak_stmt;
DEALLOCATE PREPARE simak_stmt;

SHOW INDEX FROM `mutasi_masuk`;
SHOW INDEX FROM `mutasi_keluar`;
