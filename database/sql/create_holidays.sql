-- Create holidays table for storing holiday dates
CREATE TABLE IF NOT EXISTS `holidays` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `date` DATE NOT NULL UNIQUE,
    `description` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data (optional - delete if not needed)
-- INSERT INTO `holidays` (`date`, `description`, `created_at`, `updated_at`) VALUES
-- ('2025-01-01', 'Tahun Baru 2025', NOW(), NOW()),
-- ('2025-01-29', 'Tahun Baru Imlek 2576', NOW(), NOW()),
-- ('2025-03-29', 'Hari Raya Nyepi', NOW(), NOW()),
-- ('2025-03-31', 'Idul Fitri 1446 H', NOW(), NOW()),
-- ('2025-04-01', 'Idul Fitri 1446 H', NOW(), NOW());
