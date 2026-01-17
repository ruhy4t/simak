-- Tambahkan kolom untuk Surat Izin Mutasi
ALTER TABLE `mutasi_keluar` ADD COLUMN `nomor_surat_izin` VARCHAR(50) NULL;
ALTER TABLE `mutasi_keluar` ADD COLUMN `tanggal_surat_izin` DATE NULL;
ALTER TABLE `mutasi_masuk` ADD COLUMN `nomor_surat_izin` VARCHAR(50) NULL;
ALTER TABLE `mutasi_masuk` ADD COLUMN `tanggal_surat_izin` DATE NULL;

-- Tambahkan kolom is_dinas_entry untuk menandai data dari Dinas
ALTER TABLE `mutasi_keluar` ADD COLUMN `is_dinas_entry` TINYINT(1) DEFAULT 0;
ALTER TABLE `mutasi_masuk` ADD COLUMN `is_dinas_entry` TINYINT(1) DEFAULT 0;