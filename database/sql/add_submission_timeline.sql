-- Jalankan file ini SATU KALI hanya jika production tidak menyediakan akses
-- `php artisan migrate --force`. Jika Artisan tersedia, gunakan migration Laravel.

ALTER TABLE `mutasi_keluar`
    ADD COLUMN `last_submitted_at` TIMESTAMP NULL,
    ADD COLUMN `revision_count` INT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE `mutasi_masuk`
    ADD COLUMN `last_submitted_at` TIMESTAMP NULL,
    ADD COLUMN `revision_count` INT UNSIGNED NOT NULL DEFAULT 0;

UPDATE `mutasi_keluar`
SET `last_submitted_at` = COALESCE(`created_at`, `tanggal_pengajuan`)
WHERE `last_submitted_at` IS NULL;

UPDATE `mutasi_masuk`
SET `last_submitted_at` = COALESCE(`created_at`, `tanggal_pengajuan`)
WHERE `last_submitted_at` IS NULL;

CREATE INDEX `idx_keluar_last_submitted`
    ON `mutasi_keluar` (`last_submitted_at`);

CREATE INDEX `idx_masuk_last_submitted`
    ON `mutasi_masuk` (`last_submitted_at`);

SET @submission_timeline_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
VALUES ('2026_07_16_120000_add_submission_timeline_to_mutasi_tables', @submission_timeline_batch);
