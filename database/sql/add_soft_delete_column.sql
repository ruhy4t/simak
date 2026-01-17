<<<<<<< HEAD
-- ============================================
-- FIX: Add deleted_at column to mutasi tables
-- Production hotfix for missing soft delete column
-- Run this in phpMyAdmin
-- ============================================

-- 1. Add deleted_at to mutasi_keluar (if not exists)
SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'mutasi_keluar' 
     AND COLUMN_NAME = 'deleted_at') = 0,
    'ALTER TABLE `mutasi_keluar` ADD `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`',
    'SELECT "Column deleted_at already exists in mutasi_keluar"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Add deleted_at to mutasi_masuk (if not exists)
SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'mutasi_masuk' 
     AND COLUMN_NAME = 'deleted_at') = 0,
    'ALTER TABLE `mutasi_masuk` ADD `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`',
    'SELECT "Column deleted_at already exists in mutasi_masuk"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Add deleted_at to mutasi_masuk_siswa (if not exists)
SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'mutasi_masuk_siswa' 
     AND COLUMN_NAME = 'deleted_at') = 0,
    'ALTER TABLE `mutasi_masuk_siswa` ADD `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`',
    'SELECT "Column deleted_at already exists in mutasi_masuk_siswa"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- DONE! Data mutasi sekarang akan muncul di dashboard dan list
=======
-- ============================================
-- FIX: Add deleted_at column to mutasi tables
-- Production hotfix for missing soft delete column
-- Run this in phpMyAdmin
-- ============================================

-- 1. Add deleted_at to mutasi_keluar (if not exists)
SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'mutasi_keluar' 
     AND COLUMN_NAME = 'deleted_at') = 0,
    'ALTER TABLE `mutasi_keluar` ADD `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`',
    'SELECT "Column deleted_at already exists in mutasi_keluar"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Add deleted_at to mutasi_masuk (if not exists)
SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'mutasi_masuk' 
     AND COLUMN_NAME = 'deleted_at') = 0,
    'ALTER TABLE `mutasi_masuk` ADD `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`',
    'SELECT "Column deleted_at already exists in mutasi_masuk"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Add deleted_at to mutasi_masuk_siswa (if not exists)
SET @query = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'mutasi_masuk_siswa' 
     AND COLUMN_NAME = 'deleted_at') = 0,
    'ALTER TABLE `mutasi_masuk_siswa` ADD `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`',
    'SELECT "Column deleted_at already exists in mutasi_masuk_siswa"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- DONE! Data mutasi sekarang akan muncul di dashboard dan list
>>>>>>> 9466a07 (Upload project pertama kali)
