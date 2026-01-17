-- Optimasi Index Database untuk SIMAK
-- Copy dan jalankan perintah ini di Menu "SQL" pada phpMyAdmin hosting Anda

-- 1. Index untuk Tabel Mutasi Keluar
ALTER TABLE `mutasi_keluar` ADD INDEX `mutasi_keluar_tanggal_pengajuan_index` (`tanggal_pengajuan`);
ALTER TABLE `mutasi_keluar` ADD INDEX `mutasi_keluar_status_index` (`status`);
ALTER TABLE `mutasi_keluar` ADD INDEX `mutasi_keluar_deleted_at_index` (`deleted_at`);
ALTER TABLE `mutasi_keluar` ADD INDEX `mutasi_keluar_npsn_sekolah_asal_index` (`npsn_sekolah_asal`);
ALTER TABLE `mutasi_keluar` ADD INDEX `mutasi_keluar_pengusul_index` (`pengusul`);

-- 2. Index untuk Tabel Mutasi Masuk
ALTER TABLE `mutasi_masuk` ADD INDEX `mutasi_masuk_tanggal_pengajuan_index` (`tanggal_pengajuan`);
ALTER TABLE `mutasi_masuk` ADD INDEX `mutasi_masuk_status_index` (`status`);
ALTER TABLE `mutasi_masuk` ADD INDEX `mutasi_masuk_deleted_at_index` (`deleted_at`);
ALTER TABLE `mutasi_masuk` ADD INDEX `mutasi_masuk_npsn_sekolah_tujuan_index` (`npsn_sekolah_tujuan`);
ALTER TABLE `mutasi_masuk` ADD INDEX `mutasi_masuk_pengusul_index` (`pengusul`);

-- Konfirmasi selesai
SELECT "Optimasi Database Selesai" as status;